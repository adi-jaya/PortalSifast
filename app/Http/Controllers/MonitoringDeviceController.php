<?php

namespace App\Http\Controllers;

use App\Models\MonitoredDevice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringDeviceController extends Controller
{
    public function index(Request $request): Response
    {
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
        ];

        return Inertia::render('monitoring/index', [
            'devices' => $devices,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'sort' => $sort,
            ],
            'stats' => $stats,
        ]);
    }

    public function show(MonitoredDevice $device): Response
    {
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
        ]);
    }
}
