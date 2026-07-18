<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\RegisterAgentRequest;
use App\Models\Aset;
use App\Models\MonitoredDevice;
use App\Services\Agent\AgentApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __construct(private AgentApiKeyService $apiKeys) {}

    public function __invoke(RegisterAgentRequest $request): JsonResponse
    {
        $requestId = (string) ($request->headers->get('X-Request-Id') ?: Str::uuid());

        Log::shareContext([
            'request_id' => $requestId,
            'route' => 'agent.register',
            'device_uuid' => $request->validated('uuid'),
        ]);

        if (! hash_equals((string) config('agent.enrollment_key'), (string) $request->validated('enrollment_key'))) {
            Log::channel('agent')->warning('Agent register rejected: invalid enrollment key', [
                'request_id' => $requestId,
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'invalid_enrollment_key',
                    'message' => 'Enrollment key tidak valid.',
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        $validated = $request->validated();
        $hardware = $validated['hardware'] ?? [];
        $plainKey = $this->apiKeys->generatePlainKey();

        $device = DB::transaction(function () use ($validated, $hardware, $plainKey): MonitoredDevice {
            /** @var MonitoredDevice $device */
            $device = MonitoredDevice::query()->updateOrCreate(
                ['uuid' => $validated['uuid']],
                [
                    'hostname' => $validated['hostname'] ?? null,
                    'computer_name' => $validated['computer_name'] ?? null,
                    'ip_address' => $validated['ip_address'] ?? null,
                    'mac_address' => $validated['mac_address'] ?? null,
                    'api_key_prefix' => $this->apiKeys->prefix($plainKey),
                    'api_key_hash' => $this->apiKeys->hash($plainKey),
                    'agent_version' => $validated['agent_version'] ?? null,
                    'status' => MonitoredDevice::STATUS_ONLINE,
                    'last_seen_at' => now(),
                    'aset_id' => $this->resolveAsetId($hardware['serial_number'] ?? null),
                ]
            );

            $device->hardware()->updateOrCreate(
                ['monitored_device_id' => $device->id],
                [
                    'os' => $hardware['os'] ?? null,
                    'os_version' => $hardware['os_version'] ?? null,
                    'architecture' => $hardware['architecture'] ?? null,
                    'cpu_model' => $hardware['cpu_model'] ?? null,
                    'cpu_cores' => $hardware['cpu_cores'] ?? null,
                    'ram_total_mb' => $hardware['ram_total_mb'] ?? null,
                    'disk_total_gb' => $hardware['disk_total_gb'] ?? null,
                    'manufacturer' => $hardware['manufacturer'] ?? null,
                    'model' => $hardware['model'] ?? null,
                    'serial_number' => $hardware['serial_number'] ?? null,
                    'motherboard' => $hardware['motherboard'] ?? null,
                    'bios' => $hardware['bios'] ?? null,
                    'boot_time' => $hardware['boot_time'] ?? null,
                    'timezone' => $hardware['timezone'] ?? null,
                    'domain' => $hardware['domain'] ?? null,
                    'username' => $hardware['username'] ?? null,
                ]
            );

            return $device;
        });

        Log::channel('agent')->info('Agent registered', [
            'request_id' => $requestId,
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $device->id,
                'uuid' => $device->uuid,
                'api_key' => $plainKey,
                'request_id' => $requestId,
            ],
        ], 201);
    }

    private function resolveAsetId(?string $serialNumber): ?int
    {
        if ($serialNumber === null || $serialNumber === '') {
            return null;
        }

        return Aset::query()
            ->where('no_seri', $serialNumber)
            ->value('id');
    }
}
