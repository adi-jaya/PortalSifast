<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitoring\LinkMonitoredDeviceAsetRequest;
use App\Models\Aset;
use App\Models\MonitoredDevice;
use App\Services\Monitoring\MarkStaleDevicesOffline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(MonitoredDevice $device, MarkStaleDevicesOffline $markStaleDevicesOffline): Response
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

        return Inertia::render('monitoring/show', [
            'device' => $device,
            'recentSamples' => $recentSamples,
            'linkableAssets' => $this->linkableAssetsFor($device),
        ]);
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
