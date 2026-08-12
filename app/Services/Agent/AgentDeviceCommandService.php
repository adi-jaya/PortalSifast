<?php

namespace App\Services\Agent;

use App\Models\AgentDeviceCommand;
use App\Models\MonitoredDevice;
use App\Models\User;
use App\Support\AgentLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgentDeviceCommandService
{
    public function enqueue(MonitoredDevice $device, User $actor, string $type, array $payload = []): AgentDeviceCommand
    {
        if ($type === AgentDeviceCommand::TYPE_LIST_WINDOWS) {
            $existing = AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', AgentDeviceCommand::TYPE_LIST_WINDOWS)
                ->whereIn('status', [
                    AgentDeviceCommand::STATUS_PENDING,
                    AgentDeviceCommand::STATUS_SENT,
                ])
                ->latest('id')
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        if ($type === AgentDeviceCommand::TYPE_KILL_PID) {
            $pid = (int) ($payload['pid'] ?? 0);
            if ($pid < 1) {
                throw ValidationException::withMessages([
                    'pid' => 'PID wajib diisi.',
                ]);
            }
            $payload = [
                'pid' => $pid,
                'exe' => isset($payload['exe']) ? (string) $payload['exe'] : null,
            ];
        }

        $command = AgentDeviceCommand::query()->create([
            'monitored_device_id' => $device->id,
            'type' => $type,
            'payload' => $payload,
            'status' => AgentDeviceCommand::STATUS_PENDING,
            'created_by' => $actor->id,
        ]);

        AgentLog::info('Agent command enqueued', [
            'command_id' => $command->id,
            'device_uuid' => $device->uuid,
            'type' => $type,
            'created_by' => $actor->id,
            'payload' => $payload,
        ]);

        return $command;
    }

    /**
     * @return Collection<int, AgentDeviceCommand>
     */
    public function claimPending(MonitoredDevice $device, int $limit = 5): Collection
    {
        return DB::transaction(function () use ($device, $limit): Collection {
            $commands = AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('status', AgentDeviceCommand::STATUS_PENDING)
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($commands->isEmpty()) {
                return $commands;
            }

            $now = now();
            AgentDeviceCommand::query()
                ->whereIn('id', $commands->modelKeys())
                ->update([
                    'status' => AgentDeviceCommand::STATUS_SENT,
                    'sent_at' => $now,
                ]);

            return $commands->map(function (AgentDeviceCommand $command) use ($now): AgentDeviceCommand {
                $command->status = AgentDeviceCommand::STATUS_SENT;
                $command->sent_at = $now;

                return $command;
            });
        });
    }

    /**
     * @param  Collection<int, AgentDeviceCommand>  $commands
     * @return list<array{id: int, type: string, payload: array<string, mixed>}>
     */
    public function formatForHeartbeat(Collection $commands): array
    {
        return $commands
            ->map(fn (AgentDeviceCommand $command): array => [
                'id' => $command->id,
                'type' => $command->type,
                'payload' => $command->payload ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function recordResult(MonitoredDevice $device, int $commandId, string $status, array $result): AgentDeviceCommand
    {
        $command = AgentDeviceCommand::query()
            ->whereKey($commandId)
            ->where('monitored_device_id', $device->id)
            ->first();

        if ($command === null) {
            throw ValidationException::withMessages([
                'command_id' => 'Perintah tidak ditemukan untuk perangkat ini.',
            ]);
        }

        $command->update([
            'status' => $status,
            'result' => $result,
            'finished_at' => now(),
        ]);

        if ($command->type === AgentDeviceCommand::TYPE_LIST_WINDOWS && $status === AgentDeviceCommand::STATUS_SUCCEEDED) {
            $windows = $result['windows'] ?? [];
            if (! is_array($windows)) {
                $windows = [];
            }

            $device->update([
                'window_snapshot' => ['windows' => array_values($windows)],
                'window_snapshot_at' => now(),
            ]);
        }

        AgentLog::info('Agent command result recorded', [
            'command_id' => $command->id,
            'device_uuid' => $device->uuid,
            'type' => $command->type,
            'status' => $status,
            'created_by' => $command->created_by,
        ]);

        return $command->fresh();
    }

    public function latestOpen(MonitoredDevice $device): ?AgentDeviceCommand
    {
        return AgentDeviceCommand::query()
            ->where('monitored_device_id', $device->id)
            ->whereIn('status', [
                AgentDeviceCommand::STATUS_PENDING,
                AgentDeviceCommand::STATUS_SENT,
            ])
            ->latest('id')
            ->first();
    }
}
