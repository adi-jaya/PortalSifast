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
            ->with('hardware:id,monitored_device_id,os,os_version,serial_number')
            ->latest('last_seen_at');

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

        $devices = $query->paginate(20)->withQueryString();

        return Inertia::render('monitoring/index', [
            'devices' => $devices,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'stats' => [
                'total' => MonitoredDevice::query()->count(),
                'online' => MonitoredDevice::query()->where('status', MonitoredDevice::STATUS_ONLINE)->count(),
                'offline' => MonitoredDevice::query()->where('status', MonitoredDevice::STATUS_OFFLINE)->count(),
            ],
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
            ->limit(20)
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
