<?php

namespace App\Services\Agent;

use App\Models\AgentDeviceCommand;
use App\Models\MonitoredDevice;
use App\Models\User;
use App\Support\AgentLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AgentDeviceCommandService
{
    public function enqueue(MonitoredDevice $device, User $actor, string $type, array $payload = []): AgentDeviceCommand
    {
        if (in_array($type, [
            AgentDeviceCommand::TYPE_LIST_WINDOWS,
            AgentDeviceCommand::TYPE_LIST_PROCESSES,
            AgentDeviceCommand::TYPE_CAPTURE_DESKTOP,
        ], true)) {
            $existing = AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', $type)
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

            $existingKill = AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', AgentDeviceCommand::TYPE_KILL_PID)
                ->whereIn('status', [
                    AgentDeviceCommand::STATUS_PENDING,
                    AgentDeviceCommand::STATUS_SENT,
                ])
                ->where('payload->pid', $pid)
                ->latest('id')
                ->first();

            if ($existingKill !== null) {
                return $existingKill;
            }
        } else {
            $payload = [];
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
            ->map(function (AgentDeviceCommand $command): array {
                $payload = $command->payload;
                if (! is_array($payload) || $payload === [] || array_is_list($payload)) {
                    $payload = new \stdClass;
                }

                return [
                    'id' => $command->id,
                    'type' => $command->type,
                    'payload' => $payload,
                ];
            })
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

        if ($command->type === AgentDeviceCommand::TYPE_LIST_PROCESSES && $status === AgentDeviceCommand::STATUS_SUCCEEDED) {
            $processes = $result['processes'] ?? [];
            if (! is_array($processes)) {
                $processes = [];
            }

            $device->update([
                'process_snapshot' => ['processes' => array_values($processes)],
                'process_snapshot_at' => now(),
            ]);
        }

        if ($command->type === AgentDeviceCommand::TYPE_KILL_PID && $status === AgentDeviceCommand::STATUS_SUCCEEDED) {
            $killedPid = (int) ($command->payload['pid'] ?? ($result['pid'] ?? 0));
            if ($killedPid > 0) {
                $snapshot = $device->window_snapshot ?? ['windows' => []];
                $windows = is_array($snapshot['windows'] ?? null) ? $snapshot['windows'] : [];
                $filtered = array_values(array_filter($windows, fn (array $row): bool => (int) ($row['pid'] ?? 0) !== $killedPid));
                if (count($filtered) !== count($windows)) {
                    $device->update([
                        'window_snapshot' => ['windows' => $filtered],
                        'window_snapshot_at' => now(),
                    ]);
                }

                $procSnap = $device->process_snapshot ?? ['processes' => []];
                $processes = is_array($procSnap['processes'] ?? null) ? $procSnap['processes'] : [];
                $procFiltered = array_values(array_filter($processes, fn (array $row): bool => (int) ($row['pid'] ?? 0) !== $killedPid));
                if (count($procFiltered) !== count($processes)) {
                    $device->update([
                        'process_snapshot' => ['processes' => $procFiltered],
                        'process_snapshot_at' => now(),
                    ]);
                }
            }
        }

        if ($command->type === AgentDeviceCommand::TYPE_CAPTURE_DESKTOP && $status === AgentDeviceCommand::STATUS_SUCCEEDED) {
            $stored = $this->storeDesktopSnapshot($device, $result);
            $command->update([
                'result' => $stored,
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
            ->where('type', '!=', AgentDeviceCommand::TYPE_CAPTURE_DESKTOP)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function storeDesktopSnapshot(MonitoredDevice $device, array $result): array
    {
        $base64 = $result['image_base64'] ?? null;
        if (! is_string($base64) || $base64 === '') {
            throw ValidationException::withMessages([
                'result' => 'Hasil capture_desktop tidak berisi image_base64.',
            ]);
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'result' => 'image_base64 tidak valid.',
            ]);
        }

        if (strlen($binary) > 5_000_000) {
            throw ValidationException::withMessages([
                'result' => 'Snapshot desktop terlalu besar.',
            ]);
        }

        $path = "monitoring/desktops/{$device->id}.jpg";
        Storage::disk('local')->put($path, $binary);

        $device->update([
            'desktop_snapshot_path' => $path,
            'desktop_snapshot_at' => now(),
            'desktop_snapshot_meta' => [
                'format' => (string) ($result['format'] ?? 'jpeg'),
                'width' => (int) ($result['width'] ?? 0),
                'height' => (int) ($result['height'] ?? 0),
                'size_bytes' => strlen($binary),
            ],
        ]);

        unset($result['image_base64']);
        $result['stored'] = true;
        $result['path'] = $path;
        $result['size_bytes'] = strlen($binary);

        return $result;
    }
}
