<?php

use App\Models\MonitoredDevice;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['agent.enrollment_key' => 'test-enrollment-key']);
});

it('registers a new monitored device and returns an api key', function () {
    $uuid = (string) Str::uuid();

    $response = $this->postJson('/api/agent/register', [
        'enrollment_key' => 'test-enrollment-key',
        'uuid' => $uuid,
        'hostname' => 'pc-lab-01',
        'computer_name' => 'PC-LAB-01',
        'ip_address' => '10.10.10.5',
        'mac_address' => 'aa:bb:cc:dd:ee:ff',
        'agent_version' => '0.1.0',
        'hardware' => [
            'os' => 'Linux',
            'os_version' => '6.8',
            'architecture' => 'amd64',
            'cpu_model' => 'Test CPU',
            'cpu_cores' => 4,
            'ram_total_mb' => 8192,
            'disk_total_gb' => 256,
            'serial_number' => 'SN-TEST-001',
        ],
        'sensors' => [
            'supported' => true,
            'readings' => [
                ['name' => 'ThermalZone\\_TZ0', 'temperature_c' => 45.5],
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['device_id', 'uuid', 'api_key', 'request_id'],
        ]);

    $device = MonitoredDevice::query()->where('uuid', $uuid)->first();

    expect($device)->not->toBeNull()
        ->and($device->hostname)->toBe('pc-lab-01')
        ->and($device->hardware)->not->toBeNull()
        ->and($device->hardware->serial_number)->toBe('SN-TEST-001')
        ->and($device->api_key_prefix)->toHaveLength(8)
        ->and($device->sensors['supported'] ?? null)->toBeTrue()
        ->and($device->sensors['readings'][0]['temperature_c'] ?? null)->toBe(45.5);
});

it('rejects register with invalid enrollment key', function () {
    $this->postJson('/api/agent/register', [
        'enrollment_key' => 'wrong-key',
        'uuid' => (string) Str::uuid(),
        'hostname' => 'pc-x',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'invalid_enrollment_key');
});
