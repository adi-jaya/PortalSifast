<?php

use App\Models\DeviceMetricSample;
use App\Models\MonitoredDevice;
use Illuminate\Support\Facades\Artisan;

it('deletes metric samples older than the retention window', function () {
    $device = MonitoredDevice::factory()->create();

    DeviceMetricSample::query()->create([
        'monitored_device_id' => $device->id,
        'cpu_percent' => 10,
        'ram_percent' => 20,
        'disk_percent' => 30,
        'uptime_seconds' => 100,
        'collected_at' => now()->subDays(10),
    ]);

    DeviceMetricSample::query()->create([
        'monitored_device_id' => $device->id,
        'cpu_percent' => 11,
        'ram_percent' => 21,
        'disk_percent' => 31,
        'uptime_seconds' => 200,
        'collected_at' => now()->subDay(),
    ]);

    config(['agent.metric_retention_days' => 7]);

    Artisan::call('monitoring:prune-metric-samples');

    expect(DeviceMetricSample::query()->count())->toBe(1)
        ->and((float) DeviceMetricSample::query()->first()->cpu_percent)->toBe(11.0);
});

it('keeps all samples when nothing is older than retention', function () {
    $device = MonitoredDevice::factory()->create();

    DeviceMetricSample::query()->create([
        'monitored_device_id' => $device->id,
        'cpu_percent' => 5,
        'ram_percent' => 5,
        'disk_percent' => 5,
        'uptime_seconds' => 50,
        'collected_at' => now()->subHours(12),
    ]);

    Artisan::call('monitoring:prune-metric-samples', ['--days' => 7]);

    expect(DeviceMetricSample::query()->count())->toBe(1);
});
