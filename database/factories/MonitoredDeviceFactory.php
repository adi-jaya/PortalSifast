<?php

namespace Database\Factories;

use App\Models\MonitoredDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<MonitoredDevice>
 */
class MonitoredDeviceFactory extends Factory
{
    protected $model = MonitoredDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainKey = 'rsag_'.Str::random(40);
        $prefix = substr($plainKey, 0, 8);

        return [
            'uuid' => (string) Str::uuid(),
            'hostname' => fake()->unique()->domainWord().'-pc',
            'computer_name' => fake()->domainWord().'-PC',
            'ip_address' => fake()->ipv4(),
            'mac_address' => fake()->macAddress(),
            'api_key_prefix' => $prefix,
            'api_key_hash' => Hash::make($plainKey),
            'agent_version' => '0.1.0',
            'status' => MonitoredDevice::STATUS_OFFLINE,
            'last_seen_at' => null,
            'last_cpu_percent' => null,
            'last_ram_percent' => null,
            'last_disk_percent' => null,
            'uptime_seconds' => null,
            'aset_id' => null,
        ];
    }

    public function online(): static
    {
        return $this->state(fn (): array => [
            'status' => MonitoredDevice::STATUS_ONLINE,
            'last_seen_at' => now(),
            'last_cpu_percent' => fake()->randomFloat(2, 1, 40),
            'last_ram_percent' => fake()->randomFloat(2, 20, 80),
            'last_disk_percent' => fake()->randomFloat(2, 30, 90),
            'uptime_seconds' => fake()->numberBetween(60, 86400),
        ]);
    }

    /**
     * Attach a known plain API key for tests (stored on the model as `plain_api_key` after create via afterMaking is awkward —
     * use withApiKey() and read from state via create then Hash::check).
     *
     * @return array{0: MonitoredDevice, 1: string}
     */
    public function createWithApiKey(array $attributes = []): array
    {
        $plainKey = 'rsag_'.Str::random(40);

        $device = $this->create(array_merge([
            'api_key_prefix' => substr($plainKey, 0, 8),
            'api_key_hash' => Hash::make($plainKey),
        ], $attributes));

        return [$device, $plainKey];
    }
}
