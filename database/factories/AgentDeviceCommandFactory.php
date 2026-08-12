<?php

namespace Database\Factories;

use App\Models\AgentDeviceCommand;
use App\Models\MonitoredDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentDeviceCommand>
 */
class AgentDeviceCommandFactory extends Factory
{
    protected $model = AgentDeviceCommand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitored_device_id' => MonitoredDevice::factory(),
            'type' => AgentDeviceCommand::TYPE_LIST_WINDOWS,
            'payload' => [],
            'status' => AgentDeviceCommand::STATUS_PENDING,
            'result' => null,
            'created_by' => User::factory()->admin(),
            'sent_at' => null,
            'finished_at' => null,
        ];
    }

    public function killPid(int $pid = 4242, string $exe = 'chrome.exe'): static
    {
        return $this->state(fn (): array => [
            'type' => AgentDeviceCommand::TYPE_KILL_PID,
            'payload' => [
                'pid' => $pid,
                'exe' => $exe,
            ],
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => AgentDeviceCommand::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function succeeded(array $result = []): static
    {
        return $this->state(fn (): array => [
            'status' => AgentDeviceCommand::STATUS_SUCCEEDED,
            'result' => $result,
            'sent_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }
}
