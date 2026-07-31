<?php

use App\Models\MonitoredDevice;
use App\Models\User;

it('marks stale online devices offline when opening monitoring index', function () {
    $user = User::factory()->create();
    $stale = MonitoredDevice::factory()->online()->create([
        'hostname' => 'pc-stale',
        'last_seen_at' => now()->subMinutes(30),
    ]);
    $fresh = MonitoredDevice::factory()->online()->create([
        'hostname' => 'pc-fresh',
        'last_seen_at' => now()->subMinute(),
    ]);

    config(['agent.offline_after_minutes' => 5]);

    $this->actingAs($user)
        ->get('/monitoring')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/index')
            ->where('stats.online', 1)
            ->where('stats.offline', 1));

    expect($stale->fresh()->status)->toBe(MonitoredDevice::STATUS_OFFLINE)
        ->and($fresh->fresh()->status)->toBe(MonitoredDevice::STATUS_ONLINE);
});

it('filters monitoring devices by status', function () {
    $user = User::factory()->create();
    MonitoredDevice::factory()->online()->create(['hostname' => 'pc-on']);
    MonitoredDevice::factory()->create([
        'hostname' => 'pc-off',
        'status' => MonitoredDevice::STATUS_OFFLINE,
    ]);

    $this->actingAs($user)
        ->get('/monitoring?status=offline')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/index')
            ->has('devices.data', 1)
            ->where('devices.data.0.hostname', 'pc-off')
            ->where('filters.status', 'offline'));
});

it('filters monitoring devices by aset link state', function () {
    $user = User::factory()->create();
    MonitoredDevice::factory()->online()->create(['hostname' => 'pc-free', 'aset_id' => null]);

    $this->actingAs($user)
        ->get('/monitoring?aset_link=unlinked')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/index')
            ->has('devices.data', 1)
            ->where('filters.aset_link', 'unlinked'));
});

it('shows monitoring device detail', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create(['hostname' => 'pc-detail']);

    $this->actingAs($user)
        ->get("/monitoring/{$device->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/show')
            ->where('device.hostname', 'pc-detail')
            ->has('recentSamples')
            ->has('linkableAssets'));
});

it('deletes a monitoring device and redirects to index', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create(['hostname' => 'pc-delete-me']);

    $this->actingAs($user)
        ->delete("/monitoring/{$device->id}")
        ->assertRedirect(route('monitoring.index'));

    expect(MonitoredDevice::query()->whereKey($device->id)->exists())->toBeFalse();
});
