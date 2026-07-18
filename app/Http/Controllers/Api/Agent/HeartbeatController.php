<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\HeartbeatAgentRequest;
use App\Models\DeviceMetricSample;
use App\Models\MonitoredDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HeartbeatController extends Controller
{
    public function __invoke(HeartbeatAgentRequest $request): JsonResponse
    {
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

        if (array_key_exists('hostname', $validated) && $validated['hostname'] !== null) {
            $updates['hostname'] = $validated['hostname'];
        }

        if (array_key_exists('ip_address', $validated) && $validated['ip_address'] !== null) {
            $updates['ip_address'] = $validated['ip_address'];
        }

        // Fast path: one UPDATE + one INSERT (no hardware rewrite).
        MonitoredDevice::query()->whereKey($device->id)->update($updates);

        DeviceMetricSample::query()->create([
            'monitored_device_id' => $device->id,
            'cpu_percent' => $validated['cpu_percent'],
            'ram_percent' => $validated['ram_percent'],
            'disk_percent' => $validated['disk_percent'],
            'uptime_seconds' => $validated['uptime_seconds'] ?? null,
            'collected_at' => $collectedAt,
        ]);

        Log::channel('agent')->debug('Agent heartbeat accepted', [
            'request_id' => $requestId,
            'device_uuid' => $device->uuid,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => MonitoredDevice::STATUS_ONLINE,
                'last_seen_at' => $collectedAt->toIso8601String(),
                'request_id' => $requestId,
            ],
        ]);
    }
}
