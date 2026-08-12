<?php

use App\Models\AgentDeviceCommand;
use App\Models\MonitoredDevice;
use Database\Factories\MonitoredDeviceFactory;

function heartbeatPayload(): array
{
    return [
        'cpu_percent' => 10,
        'ram_percent' => 20,
        'disk_percent' => 30,
        'uptime_seconds' => 100,
    ];
}

it('returns pending commands on heartbeat and marks them sent', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey();

    $pending = AgentDeviceCommand::factory()->create([
        'monitored_device_id' => $device->id,
        'type' => AgentDeviceCommand::TYPE_LIST_WINDOWS,
        'status' => AgentDeviceCommand::STATUS_PENDING,
    ]);
    AgentDeviceCommand::factory()->create([
        'monitored_device_id' => $device->id,
        'type' => AgentDeviceCommand::TYPE_LIST_WINDOWS,
        'status' => AgentDeviceCommand::STATUS_SUCCEEDED,
    ]);

    $this->withToken($plainKey)
        ->postJson('/api/agent/heartbeat', heartbeatPayload())
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pending_commands.0.id', $pending->id)
        ->assertJsonPath('data.pending_commands.0.type', 'list_windows')
        ->assertJsonCount(1, 'data.pending_commands');

    expect($pending->fresh()->status)->toBe(AgentDeviceCommand::STATUS_SENT)
        ->and($pending->fresh()->sent_at)->not->toBeNull();
});

it('returns an empty pending_commands list when none are queued', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey();

    $this->withToken($plainKey)
        ->postJson('/api/agent/heartbeat', heartbeatPayload())
        ->assertSuccessful()
        ->assertJsonPath('data.pending_commands', []);
});

it('accepts a list_windows result and stores the window snapshot', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey();

    $command = AgentDeviceCommand::factory()->sent()->create([
        'monitored_device_id' => $device->id,
        'type' => AgentDeviceCommand::TYPE_LIST_WINDOWS,
    ]);

    $this->withToken($plainKey)
        ->postJson('/api/agent/commands/result', [
            'command_id' => $command->id,
            'status' => AgentDeviceCommand::STATUS_SUCCEEDED,
            'result' => [
                'windows' => [
                    ['pid' => 4242, 'exe' => 'chrome.exe', 'title' => 'Gmail'],
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.command_id', $command->id)
        ->assertJsonPath('data.status', AgentDeviceCommand::STATUS_SUCCEEDED);

    $device->refresh();

    expect($command->fresh()->status)->toBe(AgentDeviceCommand::STATUS_SUCCEEDED)
        ->and($device->window_snapshot['windows'][0]['exe'] ?? null)->toBe('chrome.exe')
        ->and($device->window_snapshot_at)->not->toBeNull();
});

it('rejects a command result that belongs to another device', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey();
    $other = MonitoredDevice::factory()->create();
    $command = AgentDeviceCommand::factory()->sent()->create([
        'monitored_device_id' => $other->id,
    ]);

    $this->withToken($plainKey)
        ->postJson('/api/agent/commands/result', [
            'command_id' => $command->id,
            'status' => AgentDeviceCommand::STATUS_SUCCEEDED,
            'result' => ['windows' => []],
        ])
        ->assertUnprocessable();
});

it('records a failed kill_pid result without changing the snapshot', function () {
    /** @var MonitoredDeviceFactory $factory */
    $factory = MonitoredDevice::factory();
    [$device, $plainKey] = $factory->createWithApiKey([
        'window_snapshot' => ['windows' => [['pid' => 1, 'exe' => 'notepad.exe', 'title' => 'Untitled']]],
    ]);

    $command = AgentDeviceCommand::factory()->sent()->killPid(4)->create([
        'monitored_device_id' => $device->id,
    ]);

    $this->withToken($plainKey)
        ->postJson('/api/agent/commands/result', [
            'command_id' => $command->id,
            'status' => AgentDeviceCommand::STATUS_FAILED,
            'result' => ['error' => 'blocked', 'exe' => 'lsass.exe'],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.status', AgentDeviceCommand::STATUS_FAILED);

    expect($device->fresh()->window_snapshot['windows'][0]['exe'] ?? null)->toBe('notepad.exe');
});
