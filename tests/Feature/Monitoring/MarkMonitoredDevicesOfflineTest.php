<?php

use App\Models\MonitoredDevice;
use Illuminate\Support\Facades\Artisan;

it('marks stale online devices as offline', function () {
    $stale = MonitoredDevice::factory()->online()->create([
        'last_seen_at' => now()->subMinutes(10),
    ]);
    $fresh = MonitoredDevice::factory()->online()->create([
        'last_seen_at' => now()->subMinute(),
    ]);

    config(['agent.offline_after_minutes' => 5]);

    Artisan::call('monitoring:mark-offline');

    expect($stale->fresh()->status)->toBe(MonitoredDevice::STATUS_OFFLINE)
        ->and($fresh->fresh()->status)->toBe(MonitoredDevice::STATUS_ONLINE);
});
