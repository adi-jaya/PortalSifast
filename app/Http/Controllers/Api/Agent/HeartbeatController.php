<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\HeartbeatAgentRequest;
use App\Models\DeviceMetricSample;
use App\Models\MonitoredDevice;
use App\Services\Agent\AgentDeviceCommandService;
use App\Services\Agent\ProcessThreatEvaluator;
use App\Support\AgentLog;
use Illuminate\Http\JsonResponse;

class HeartbeatController extends Controller
{
    public function __invoke(
        HeartbeatAgentRequest $request,
        AgentDeviceCommandService $commands,
        ProcessThreatEvaluator $threats,
    ): JsonResponse {
        /** @var MonitoredDevice $device */
        $device = $request->attributes->get('monitored_device');
        $requestId = (string) $request->attributes->get('agent_request_id');
        $validated = $request->validated();
        $collectedAt = now();

        $updates = [
            'status' => MonitoredDevice::STATUS_ONLINE,
            'last_seen_at' => $collectedAt,
            'last_cpu_percent' => $validated['cpu_percent'],
            'last_ram_percent' => $validated['ram_percent'],
            'last_disk_percent' => $validated['disk_percent'],
            'uptime_seconds' => $validated['uptime_seconds'] ?? $device->uptime_seconds,
        ];

        foreach (['hostname', 'computer_name', 'ip_address', 'mac_address', 'agent_version'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '') {
                $updates[$field] = $validated[$field];
            }
        }

        if (array_key_exists('critical_software', $validated)) {
            $updates['critical_software'] = $validated['critical_software'];
        }

        if (array_key_exists('usb', $validated) && is_array($validated['usb'])) {
            $updates['usb_inventory'] = $validated['usb'];
        }

        if (array_key_exists('sensors', $validated) && is_array($validated['sensors'])) {
            $updates['sensors'] = $validated['sensors'];
        }

        if (array_key_exists('suspicious_processes', $validated)) {
            $candidates = is_array($validated['suspicious_processes']) ? $validated['suspicious_processes'] : [];
            $updates['suspicious_processes'] = $threats->evaluate($candidates);
            $updates['suspicious_processes_at'] = $collectedAt;
        }

        MonitoredDevice::query()->whereKey($device->id)->update($updates);

        $hardware = $validated['hardware'] ?? null;
        if (is_array($hardware) && $hardware !== []) {
            $hardwareUpdates = [];
            foreach ([
                'manufacturer',
                'model',
                'serial_number',
                'motherboard',
                'bios',
                'domain',
                'username',
            ] as $field) {
                if (array_key_exists($field, $hardware) && $hardware[$field] !== null && $hardware[$field] !== '') {
                    $hardwareUpdates[$field] = $hardware[$field];
                }
            }

            if ($hardwareUpdates !== []) {
                $device->hardware()->updateOrCreate(
                    ['monitored_device_id' => $device->id],
                    $hardwareUpdates
                );
            }
        }

        DeviceMetricSample::query()->create([
            'monitored_device_id' => $device->id,
            'cpu_percent' => $validated['cpu_percent'],
            'ram_percent' => $validated['ram_percent'],
            'disk_percent' => $validated['disk_percent'],
            'uptime_seconds' => $validated['uptime_seconds'] ?? null,
            'collected_at' => $collectedAt,
        ]);

        $pending = $commands->claimPending(
            $device,
            (int) config('agent.command_batch_size', 5),
        );

        AgentLog::debug('Agent heartbeat accepted', [
            'request_id' => $requestId,
            'device_uuid' => $device->uuid,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => MonitoredDevice::STATUS_ONLINE,
                'last_seen_at' => $collectedAt->toIso8601String(),
                'request_id' => $requestId,
                'pending_commands' => $commands->formatForHeartbeat($pending),
            ],
        ]);
    }
}
