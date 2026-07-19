<?php

use App\Models\MonitoredDevice;
use App\Models\User;

it('shows monitoring index for authenticated users', function () {
    $user = User::factory()->create();
    MonitoredDevice::factory()->online()->create(['hostname' => 'pc-visible']);

    $this->actingAs($user)
        ->get('/monitoring')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/index')
            ->has('devices.data', 1)
            ->where('stats.total', 1)
            ->where('stats.online', 1)
            ->has('stats.high_load'));
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

it('shows monitoring device detail', function () {
    $user = User::factory()->create();
    $device = MonitoredDevice::factory()->online()->create(['hostname' => 'pc-detail']);

    $this->actingAs($user)
        ->get("/monitoring/{$device->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/show')
            ->where('device.hostname', 'pc-detail')
            ->has('recentSamples'));
});
