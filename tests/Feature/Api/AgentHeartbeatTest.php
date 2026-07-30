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
        'computer_name' => 'UPDATED-HOST',
        'ip_address' => '10.10.10.9',
        'mac_address' => 'aa:bb:cc:dd:ee:ff',
        'agent_version' => '0.2.0',
        'hardware' => [
            'manufacturer' => 'Dell Inc.',
            'model' => 'OptiPlex',
            'motherboard' => 'Dell 0ABC',
            'bios' => 'Dell 1.2.3',
            'domain' => 'WORKGROUP',
            'username' => 'W11OKY\\admin',
        ],
        'critical_software' => [
            ['id' => 'synology_drive', 'name' => 'Synology Drive', 'status' => 'running', 'detail' => 'service'],
            ['id' => 'anydesk', 'name' => 'AnyDesk', 'status' => 'installed'],
            ['id' => 'radmin_server', 'name' => 'Radmin Server', 'status' => 'missing'],
        ],
        'usb' => [
            'ports_total' => 8,
            'ports_used' => 3,
            'ports_empty' => 5,
            'removable_storage_count' => 0,
            'has_removable_storage' => false,
            'printer_count' => 1,
            'estimated' => true,
            'note' => 'estimasi',
            'devices' => [
                ['name' => 'HP Printer', 'kind' => 'printer', 'device_id' => 'USB001'],
            ],
        ],
        'sensors' => [
            'supported' => false,
            'note' => 'Sensor suhu ACPI tidak tersedia di perangkat ini.',
            'readings' => [],
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'online');

    $device->refresh();
    $device->load('hardware');

    expect($device->status)->toBe(MonitoredDevice::STATUS_ONLINE)
        ->and((float) $device->last_cpu_percent)->toBe(12.5)
        ->and($device->hostname)->toBe('updated-host')
        ->and($device->computer_name)->toBe('UPDATED-HOST')
        ->and($device->mac_address)->toBe('aa:bb:cc:dd:ee:ff')
        ->and($device->agent_version)->toBe('0.2.0')
        ->and($device->hardware?->manufacturer)->toBe('Dell Inc.')
        ->and($device->hardware?->username)->toBe('W11OKY\\admin')
        ->and($device->critical_software[0]['status'] ?? null)->toBe('running')
        ->and($device->usb_inventory['ports_empty'] ?? null)->toBe(5)
        ->and($device->usb_inventory['has_removable_storage'] ?? null)->toBeFalse()
        ->and($device->sensors['supported'] ?? null)->toBeFalse()
        ->and($device->sensors['note'] ?? null)->toBe('Sensor suhu ACPI tidak tersedia di perangkat ini.')
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
