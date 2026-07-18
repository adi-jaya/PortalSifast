<?php

use App\Models\DeviceMetricSample;
use App\Models\MonitoredDevice;
use Database\Factories\MonitoredDeviceFactory;

it('accepts heartbeat with a valid device api key', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey([
        'status' => MonitoredDevice::STATUS_OFFLINE,
    ]);

    $response = $this->withToken($plainKey)->postJson('/api/agent/heartbeat', [
        'cpu_percent' => 12.5,
        'ram_percent' => 55.2,
        'disk_percent' => 70.1,
        'uptime_seconds' => 1200,
        'hostname' => 'updated-host',
        'ip_address' => '10.10.10.9',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'online');

    $device->refresh();

    expect($device->status)->toBe(MonitoredDevice::STATUS_ONLINE)
        ->and((float) $device->last_cpu_percent)->toBe(12.5)
        ->and($device->hostname)->toBe('updated-host')
        ->and(DeviceMetricSample::query()->where('monitored_device_id', $device->id)->count())->toBe(1);
});

it('rejects heartbeat without bearer token', function () {
    $this->postJson('/api/agent/heartbeat', [
        'cpu_percent' => 1,
        'ram_percent' => 1,
        'disk_percent' => 1,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'agent_unauthorized');
});
