<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitoring\EnqueueAgentDeviceCommandRequest;
use App\Http\Requests\Monitoring\LinkMonitoredDeviceAsetRequest;
use App\Models\AgentDeviceCommand;
use App\Models\Aset;
use App\Models\MonitoredDevice;
use App\Models\User;
use App\Services\Agent\AgentDeviceCommandService;
use App\Services\Monitoring\MarkStaleDevicesOffline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringDeviceController extends Controller
{
    public function index(Request $request, MarkStaleDevicesOffline $markStaleDevicesOffline): Response
    {
        // Safety net when Laravel scheduler/cron is not running: refresh stale online → offline on view.
        $markStaleDevicesOffline();

        $query = MonitoredDevice::query()
            ->with([
                'hardware:id,monitored_device_id,os,os_version,serial_number',
                'aset:id,kode_aset,no_seri',
            ]);

        if ($search = trim((string) $request->string('q'))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('hostname', 'like', "%{$search}%")
                    ->orWhere('computer_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, [MonitoredDevice::STATUS_ONLINE, MonitoredDevice::STATUS_OFFLINE], true)) {
                $query->where('status', $status);
            }
        }

        $asetLink = $request->string('aset_link')->toString();
        if ($asetLink === 'linked') {
            $query->whereNotNull('aset_id');
        } elseif ($asetLink === 'unlinked') {
            $query->whereNull('aset_id');
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'cpu' => $query->orderByDesc('last_cpu_percent'),
            'ram' => $query->orderByDesc('last_ram_percent'),
            'disk' => $query->orderByDesc('last_disk_percent'),
            'hostname' => $query->orderBy('hostname'),
            default => $query->latest('last_seen_at'),
        };

        $devices = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => MonitoredDevice::query()->count(),
            'online' => MonitoredDevice::query()->where('status', MonitoredDevice::STATUS_ONLINE)->count(),
            'offline' => MonitoredDevice::query()->where('status', MonitoredDevice::STATUS_OFFLINE)->count(),
            'high_load' => MonitoredDevice::query()
                ->where('status', MonitoredDevice::STATUS_ONLINE)
                ->where(function ($builder): void {
                    $builder
                        ->where('last_cpu_percent', '>=', 90)
                        ->orWhere('last_ram_percent', '>=', 90)
                        ->orWhere('last_disk_percent', '>=', 90);
                })
                ->count(),
            'unlinked' => MonitoredDevice::query()->whereNull('aset_id')->count(),
        ];

        return Inertia::render('monitoring/index', [
            'devices' => $devices,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'sort' => $sort,
                'aset_link' => $asetLink,
            ],
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, MonitoredDevice $device, MarkStaleDevicesOffline $markStaleDevicesOffline): Response
    {
        $markStaleDevicesOffline();
        $device->refresh();

        $device->load([
            'hardware',
            'aset:id,kode_aset,no_seri',
        ]);

        $recentSamples = $device->metricSamples()
            ->orderByDesc('collected_at')
            ->limit(40)
            ->get([
                'id',
                'cpu_percent',
                'ram_percent',
                'disk_percent',
                'uptime_seconds',
                'collected_at',
            ]);

        $canRemoteApps = $request->user()?->isAdmin() ?? false;
        $pending = $canRemoteApps
            ? app(AgentDeviceCommandService::class)->latestOpen($device)
            : null;

        if (! $canRemoteApps) {
            $device->makeHidden([
                'window_snapshot',
                'window_snapshot_at',
                'process_snapshot',
                'process_snapshot_at',
                'suspicious_processes',
                'suspicious_processes_at',
                'desktop_snapshot_path',
                'desktop_snapshot_at',
                'desktop_snapshot_meta',
            ]);
        }

        $lastListCommand = $canRemoteApps
            ? AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->whereIn('type', [
                    AgentDeviceCommand::TYPE_LIST_WINDOWS,
                    AgentDeviceCommand::TYPE_LIST_PROCESSES,
                ])
                ->whereNotNull('finished_at')
                ->latest('id')
                ->first()
            : null;

        $lastKillCommand = $canRemoteApps
            ? AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', AgentDeviceCommand::TYPE_KILL_PID)
                ->whereNotNull('finished_at')
                ->latest('id')
                ->first()
            : null;

        $lastCaptureCommand = $canRemoteApps
            ? AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', AgentDeviceCommand::TYPE_CAPTURE_DESKTOP)
                ->whereNotNull('finished_at')
                ->latest('id')
                ->first()
            : null;

        $pendingCapture = $canRemoteApps
            ? AgentDeviceCommand::query()
                ->where('monitored_device_id', $device->id)
                ->where('type', AgentDeviceCommand::TYPE_CAPTURE_DESKTOP)
                ->whereIn('status', [
                    AgentDeviceCommand::STATUS_PENDING,
                    AgentDeviceCommand::STATUS_SENT,
                ])
                ->latest('id')
                ->first()
            : null;

        $activeDuplicateDevice = null;
        if ($device->status === MonitoredDevice::STATUS_OFFLINE && $device->hostname) {
            $duplicate = MonitoredDevice::query()
                ->where('id', '!=', $device->id)
                ->where('status', MonitoredDevice::STATUS_ONLINE)
                ->where(function ($query) use ($device): void {
                    $query->where('hostname', $device->hostname);
                    if ($device->mac_address) {
                        $query->orWhere('mac_address', $device->mac_address);
                    }
                })
                ->latest('last_seen_at')
                ->first();

            if ($duplicate !== null) {
                $activeDuplicateDevice = [
                    'id' => $duplicate->id,
                    'hostname' => $duplicate->hostname,
                    'last_seen_at' => $duplicate->last_seen_at?->toIso8601String(),
                ];
            }
        }

        return Inertia::render('monitoring/show', [
            'device' => $device,
            'recentSamples' => $recentSamples,
            'linkableAssets' => $this->linkableAssetsFor($device),
            'canRemoteApps' => $canRemoteApps,
            'windowSnapshot' => $canRemoteApps ? $device->window_snapshot : null,
            'windowSnapshotAt' => $canRemoteApps ? $device->window_snapshot_at?->toIso8601String() : null,
            'processSnapshot' => $canRemoteApps ? $device->process_snapshot : null,
            'processSnapshotAt' => $canRemoteApps ? $device->process_snapshot_at?->toIso8601String() : null,
            'suspiciousProcesses' => $canRemoteApps ? ($device->suspicious_processes ?? []) : [],
            'suspiciousProcessesAt' => $canRemoteApps ? $device->suspicious_processes_at?->toIso8601String() : null,
            'desktopSnapshotAt' => $canRemoteApps && $device->desktop_snapshot_path
                ? $device->desktop_snapshot_at?->toIso8601String()
                : null,
            'desktopSnapshotMeta' => $canRemoteApps ? ($device->desktop_snapshot_meta ?? null) : null,
            'desktopSnapshotUrl' => $canRemoteApps && $device->desktop_snapshot_path
                ? route('monitoring.desktop', $device).'?t='.($device->desktop_snapshot_at?->timestamp ?? time())
                : null,
            'pendingRemoteCommand' => $pending === null ? null : [
                'id' => $pending->id,
                'type' => $pending->type,
                'status' => $pending->status,
                'created_at' => $pending->created_at?->toIso8601String(),
            ],
            'pendingCaptureCommand' => $pendingCapture === null ? null : [
                'id' => $pendingCapture->id,
                'status' => $pendingCapture->status,
                'created_at' => $pendingCapture->created_at?->toIso8601String(),
            ],
            'lastListCommand' => $lastListCommand === null ? null : [
                'id' => $lastListCommand->id,
                'type' => $lastListCommand->type,
                'status' => $lastListCommand->status,
                'error' => is_array($lastListCommand->result) ? ($lastListCommand->result['error'] ?? null) : null,
                'finished_at' => $lastListCommand->finished_at?->toIso8601String(),
            ],
            'lastKillCommand' => $lastKillCommand === null ? null : [
                'id' => $lastKillCommand->id,
                'status' => $lastKillCommand->status,
                'pid' => (int) ($lastKillCommand->payload['pid'] ?? ($lastKillCommand->result['pid'] ?? 0)),
                'exe' => (string) ($lastKillCommand->payload['exe'] ?? ($lastKillCommand->result['exe'] ?? '')),
                'error' => is_array($lastKillCommand->result) ? ($lastKillCommand->result['error'] ?? null) : null,
                'finished_at' => $lastKillCommand->finished_at?->toIso8601String(),
            ],
            'lastCaptureCommand' => $lastCaptureCommand === null ? null : [
                'id' => $lastCaptureCommand->id,
                'status' => $lastCaptureCommand->status,
                'error' => is_array($lastCaptureCommand->result) ? ($lastCaptureCommand->result['error'] ?? null) : null,
                'finished_at' => $lastCaptureCommand->finished_at?->toIso8601String(),
            ],
            'activeDuplicateDevice' => $activeDuplicateDevice,
            'agentDefaultInterval' => (int) config('agent.default_interval', 10),
        ]);
    }

    public function desktop(Request $request, MonitoredDevice $device): HttpResponse
    {
        if (! ($request->user()?->isAdmin() ?? false)) {
            abort(403);
        }

        $path = $device->desktop_snapshot_path;
        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $binary = Storage::disk('local')->get($path);

        return response($binary, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=5',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    public function storeCommand(
        EnqueueAgentDeviceCommandRequest $request,
        MonitoredDevice $device,
        AgentDeviceCommandService $commands,
    ): RedirectResponse {
        $validated = $request->validated();
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(403);
        }

        $commands->enqueue(
            $device,
            $actor,
            $validated['type'],
            $validated['type'] === AgentDeviceCommand::TYPE_KILL_PID
                ? [
                    'pid' => $validated['pid'] ?? null,
                    'exe' => $validated['exe'] ?? null,
                ]
                : [],
        );

        $message = match ($validated['type']) {
            AgentDeviceCommand::TYPE_KILL_PID => 'Perintah tutup proses dikirim. Hasil muncul setelah heartbeat agent (~10 detik).',
            AgentDeviceCommand::TYPE_LIST_PROCESSES => 'Meminta daftar proses. Hasil muncul setelah heartbeat agent (~10 detik).',
            AgentDeviceCommand::TYPE_CAPTURE_DESKTOP => 'Meminta snapshot desktop. Hasil muncul setelah heartbeat agent (~10 detik).',
            default => 'Meminta daftar aplikasi terbuka. Hasil muncul setelah heartbeat agent (~10 detik).',
        };

        return redirect()
            ->route('monitoring.show', $device)
            ->with('success', $message);
    }

    public function updateAset(LinkMonitoredDeviceAsetRequest $request, MonitoredDevice $device): RedirectResponse
    {
        $asetId = $request->validated('aset_id');

        $device->update([
            'aset_id' => $asetId === null || $asetId === '' ? null : (int) $asetId,
        ]);

        return redirect()
            ->route('monitoring.show', $device)
            ->with('success', $device->aset_id ? 'Aset berhasil dihubungkan.' : 'Tautan aset dilepas.');
    }

    public function destroy(MonitoredDevice $device): RedirectResponse
    {
        $label = $device->hostname ?: $device->computer_name ?: $device->uuid;

        $device->delete();

        return redirect()
            ->route('monitoring.index')
            ->with('success', "Perangkat {$label} berhasil dihapus.");
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function linkableAssetsFor(MonitoredDevice $device): array
    {
        return Aset::query()
            ->monitorableForAgent()
            ->with([
                'barang:id,nama_barang,id_kategori',
                'ruang:id,nama_ruang',
            ])
            ->where(function ($query) use ($device): void {
                $query
                    ->whereDoesntHave('monitoredDevice')
                    ->orWhere('id', $device->aset_id);
            })
            ->orderBy('kode_aset')
            ->limit(300)
            ->get(['id', 'kode_aset', 'no_seri', 'aset_barang_id', 'aset_ruang_id'])
            ->map(function (Aset $aset): array {
                $parts = array_filter([
                    $aset->kode_aset,
                    $aset->barang?->nama_barang,
                    $aset->ruang?->nama_ruang,
                    $aset->no_seri ? "SN {$aset->no_seri}" : null,
                ]);

                return [
                    'id' => $aset->id,
                    'label' => implode(' · ', $parts),
                ];
            })
            ->values()
            ->all();
    }
}
