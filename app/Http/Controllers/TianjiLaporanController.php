<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportTianjiLaporanRequest;
use App\Models\DeviceMetricSample;
use App\Models\MonitoredDevice;
use App\Services\Monitoring\MarkStaleDevicesOffline;
use App\Services\Tianji\TianjiClient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TianjiLaporanController extends Controller
{
    public function __construct(
        private TianjiClient $tianji,
        private MarkStaleDevicesOffline $markStaleDevicesOffline,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        ($this->markStaleDevicesOffline)();

        $timezone = (string) config('app.timezone');
        $startDate = (string) $request->input('start_date', now($timezone)->startOfMonth()->toDateString());
        $endDate = (string) $request->input('end_date', now($timezone)->toDateString());

        $start = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay();

        if ($start->diffInDays($end->copy()->startOfDay()) > 30) {
            $end = $start->copy()->addDays(30)->endOfDay();
            $endDate = $end->toDateString();
        }

        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
            $endDate = $end->toDateString();
        }

        $tianjiError = null;
        $configured = $this->tianji->isConfigured();
        $tianjiMonitors = [];
        $tianjiEvents = [];

        if (! $configured) {
            $tianjiError = 'Konfigurasi Tianji belum lengkap. Hubungi admin untuk mengisi TIANJI_BASE_URL, TIANJI_API_KEY, dan TIANJI_WORKSPACE_ID.';
        } else {
            try {
                $rawMonitors = $this->tianji->listMonitors();
                $enriched = $this->tianji->enrichMonitorsForDashboard($rawMonitors, $start, $end);

                $tianjiMonitors = collect($enriched)
                    ->map(fn (array $monitor): array => $this->presentDashboardMonitor($monitor))
                    ->values()
                    ->all();

                $tianjiEvents = collect($this->tianji->monitorEvents(null, 20))
                    ->map(function (array $event) use ($timezone): array {
                        return [
                            'id' => (string) ($event['id'] ?? ''),
                            'monitor_id' => (string) ($event['monitorId'] ?? ''),
                            'type' => (string) ($event['type'] ?? ''),
                            'message' => (string) ($event['message'] ?? ''),
                            'created_at' => isset($event['createdAt'])
                                ? Carbon::parse((string) $event['createdAt'])->timezone($timezone)->toIso8601String()
                                : null,
                        ];
                    })
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                $tianjiError = $e->getMessage();
            }
        }

        $agent = $this->agentDashboardPayload();

        $tianjiUpNow = collect($tianjiMonitors)->where('current_status', 'up')->count();
        $tianjiDownNow = collect($tianjiMonitors)->where('current_status', 'down')->count();
        $uptimeValues = collect($tianjiMonitors)
            ->pluck('period_uptime_percent')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        return Inertia::render('infrastruktur/index', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'configured' => $configured,
            'tianji_error' => $tianjiError,
            'summary' => [
                'tianji' => [
                    'total' => count($tianjiMonitors),
                    'up_now' => $tianjiUpNow,
                    'down_now' => $tianjiDownNow,
                    'unknown_now' => max(0, count($tianjiMonitors) - $tianjiUpNow - $tianjiDownNow),
                    'avg_uptime_percent' => $uptimeValues->isNotEmpty()
                        ? round($uptimeValues->avg(), 1)
                        : null,
                ],
                'agent' => $agent['summary'],
            ],
            'tianji' => [
                'monitors' => $tianjiMonitors,
                'events' => $tianjiEvents,
            ],
            'agent' => [
                'devices' => $agent['devices'],
            ],
            'export_urls' => [
                'ringkasan' => route('laporan-tianji.export.ringkasan'),
                'harian' => route('laporan-tianji.export.harian'),
                'gangguan' => route('laporan-tianji.export.gangguan'),
                'agent' => route('laporan-tianji.export.agent'),
                'detail' => route('laporan-tianji.export.detail'),
            ],
        ]);
    }

    public function exportRingkasan(ExportTianjiLaporanRequest $request): StreamedResponse|RedirectResponse
    {
        return $this->streamExport($request, 'ringkasan', function ($handle) use ($request): void {
            fputcsv($handle, [
                'Nama',
                'Tipe',
                'Target',
                'Aktif',
                'Periode mulai',
                'Periode selesai',
                'Total check',
                'Up',
                'Down',
                'Uptime %',
                'Latency rata-rata (ms)',
            ]);

            $start = $request->startDate();
            $end = $request->endDate();
            $startMs = (int) ($start->getTimestampMs());
            $endMs = (int) ($end->getTimestampMs());

            foreach ($this->tianji->listMonitors() as $monitor) {
                $points = $this->tianji->monitorData((string) $monitor['id'], $startMs, $endMs);
                $stats = $this->summarizePoints($points);

                fputcsv($handle, [
                    (string) ($monitor['name'] ?? ''),
                    (string) ($monitor['type'] ?? ''),
                    $this->monitorTarget($monitor),
                    ! empty($monitor['active']) ? 'ya' : 'tidak',
                    $start->toDateString(),
                    $end->toDateString(),
                    $stats['total'],
                    $stats['up'],
                    $stats['down'],
                    $stats['uptime_percent'],
                    $stats['avg_latency_ms'],
                ]);
            }
        });
    }

    public function exportHarian(ExportTianjiLaporanRequest $request): StreamedResponse|RedirectResponse
    {
        return $this->streamExport($request, 'uptime-harian', function ($handle) use ($request): void {
            fputcsv($handle, [
                'Tanggal',
                'Nama',
                'Tipe',
                'Target',
                'Total check',
                'Up',
                'Down',
                'Uptime %',
            ]);

            $startDay = $request->startDate()->toDateString();
            $endDay = $request->endDate()->toDateString();

            foreach ($this->tianji->listMonitors() as $monitor) {
                $name = (string) ($monitor['name'] ?? '');
                $type = (string) ($monitor['type'] ?? '');
                $target = $this->monitorTarget($monitor);

                $days = $this->tianji->publicSummary((string) $monitor['id']);

                usort($days, fn (array $a, array $b) => strcmp((string) ($a['day'] ?? ''), (string) ($b['day'] ?? '')));

                foreach ($days as $day) {
                    $date = (string) ($day['day'] ?? '');

                    if ($date < $startDay || $date > $endDay) {
                        continue;
                    }

                    $totalCount = (int) ($day['totalCount'] ?? 0);
                    $upCount = (int) ($day['upCount'] ?? 0);
                    $downCount = max(0, $totalCount - $upCount);
                    $uptimePercent = $totalCount > 0 ? number_format(($upCount / $totalCount) * 100, 1, '.', '') : '0.0';

                    fputcsv($handle, [
                        $date,
                        $name,
                        $type,
                        $target,
                        $totalCount,
                        $upCount,
                        $downCount,
                        $uptimePercent,
                    ]);
                }
            }
        });
    }

    public function exportGangguan(ExportTianjiLaporanRequest $request): StreamedResponse|RedirectResponse
    {
        return $this->streamExport($request, 'gangguan', function ($handle) use ($request): void {
            fputcsv($handle, [
                'Waktu',
                'Monitor',
                'Tipe event',
                'Pesan',
            ]);

            $timezone = (string) config('app.timezone');
            $startDate = $request->startDate();
            $endDate = $request->endDate();

            $events = $this->tianji->monitorEvents(null, 500);

            usort($events, fn (array $a, array $b) => strcmp((string) ($a['createdAt'] ?? ''), (string) ($b['createdAt'] ?? '')));

            foreach ($events as $event) {
                if (! isset($event['createdAt'])) {
                    continue;
                }

                $eventTime = Carbon::parse((string) $event['createdAt'])->timezone($timezone);

                if ($eventTime->lt($startDate) || $eventTime->gt($endDate)) {
                    continue;
                }

                fputcsv($handle, [
                    $eventTime->format('Y-m-d H:i:s'),
                    (string) ($event['message'] ?? ''),
                    (string) ($event['type'] ?? ''),
                    (string) ($event['message'] ?? ''),
                ]);
            }
        });
    }

    public function exportAgent(Request $request): StreamedResponse
    {
        $agent = $this->agentDashboardPayload();

        $filename = 'rs-agent-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($agent): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Hostname',
                'Nama komputer',
                'IP',
                'OS',
                'Status',
                'Last seen',
                'CPU terakhir %',
                'RAM terakhir %',
                'Disk terakhir %',
                'Avg CPU 7d %',
                'Avg RAM 7d %',
                'Avg Disk 7d %',
                'Uptime (detik)',
                'Kode aset',
            ]);

            $timezone = (string) config('app.timezone');

            foreach ($agent['devices'] as $device) {
                $lastSeen = isset($device['last_seen_at'])
                    ? Carbon::parse((string) $device['last_seen_at'])->timezone($timezone)->format('Y-m-d H:i:s')
                    : '';

                fputcsv($handle, [
                    (string) ($device['hostname'] ?? ''),
                    (string) ($device['computer_name'] ?? ''),
                    (string) ($device['ip_address'] ?? ''),
                    (string) ($device['os'] ?? ''),
                    (string) ($device['status'] ?? ''),
                    $lastSeen,
                    $device['last_cpu_percent'] ?? '',
                    $device['last_ram_percent'] ?? '',
                    $device['last_disk_percent'] ?? '',
                    $device['avg_cpu_7d'] ?? '',
                    $device['avg_ram_7d'] ?? '',
                    $device['avg_disk_7d'] ?? '',
                    $device['uptime_seconds'] ?? '',
                    $device['aset']['kode_aset'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportDetail(ExportTianjiLaporanRequest $request): StreamedResponse|RedirectResponse
    {
        return $this->streamExport($request, 'detail', function ($handle) use ($request): void {
            fputcsv($handle, [
                'Nama monitor',
                'Tipe',
                'Target',
                'Waktu',
                'Status',
                'Latency (ms)',
                'Catatan',
            ]);

            $start = $request->startDate();
            $end = $request->endDate();
            $startMs = (int) ($start->getTimestampMs());
            $endMs = (int) ($end->getTimestampMs());
            $timezone = (string) config('app.timezone');

            foreach ($this->tianji->listMonitors() as $monitor) {
                $name = (string) ($monitor['name'] ?? '');
                $type = (string) ($monitor['type'] ?? '');
                $target = $this->monitorTarget($monitor);
                $points = $this->tianji->monitorData((string) $monitor['id'], $startMs, $endMs);

                usort($points, function (array $a, array $b): int {
                    return strcmp((string) ($a['createdAt'] ?? ''), (string) ($b['createdAt'] ?? ''));
                });

                foreach ($points as $point) {
                    $value = $point['value'] ?? null;
                    $isDown = $value === -1 || $value === -1.0;
                    $createdAt = isset($point['createdAt'])
                        ? Carbon::parse((string) $point['createdAt'])->timezone($timezone)->format('Y-m-d H:i:s')
                        : '';

                    fputcsv($handle, [
                        $name,
                        $type,
                        $target,
                        $createdAt,
                        $isDown ? 'DOWN' : 'UP',
                        (! $isDown && is_numeric($value) && (float) $value > 0) ? $value : '',
                        '',
                    ]);
                }
            }
        });
    }

    /**
     * @return array{
     *     summary: array{total: int, online: int, offline: int, high_load: int},
     *     devices: list<array<string, mixed>>
     * }
     */
    private function agentDashboardPayload(): array
    {
        $devices = MonitoredDevice::query()
            ->with(['aset:id,kode_aset', 'hardware:id,monitored_device_id,os'])
            ->orderByDesc('last_seen_at')
            ->get();

        $deviceIds = $devices->pluck('id');
        $averages = [];

        if ($deviceIds->isNotEmpty()) {
            $averages = DeviceMetricSample::query()
                ->selectRaw('monitored_device_id, AVG(cpu_percent) as avg_cpu, AVG(ram_percent) as avg_ram, AVG(disk_percent) as avg_disk')
                ->whereIn('monitored_device_id', $deviceIds)
                ->where('collected_at', '>=', now()->subDays(7))
                ->groupBy('monitored_device_id')
                ->get()
                ->keyBy('monitored_device_id');
        }

        $online = $devices->where('status', MonitoredDevice::STATUS_ONLINE)->count();
        $highLoad = $devices
            ->where('status', MonitoredDevice::STATUS_ONLINE)
            ->filter(function (MonitoredDevice $device): bool {
                return (float) $device->last_cpu_percent >= 90
                    || (float) $device->last_ram_percent >= 90
                    || (float) $device->last_disk_percent >= 90;
            })
            ->count();

        $rows = $devices->map(function (MonitoredDevice $device) use ($averages): array {
            $avg = $averages[$device->id] ?? null;

            return [
                'id' => $device->id,
                'hostname' => $device->hostname,
                'computer_name' => $device->computer_name,
                'ip_address' => $device->ip_address,
                'status' => $device->status,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'last_cpu_percent' => $device->last_cpu_percent,
                'last_ram_percent' => $device->last_ram_percent,
                'last_disk_percent' => $device->last_disk_percent,
                'uptime_seconds' => $device->uptime_seconds,
                'avg_cpu_7d' => $avg ? round((float) $avg->avg_cpu, 1) : null,
                'avg_ram_7d' => $avg ? round((float) $avg->avg_ram, 1) : null,
                'avg_disk_7d' => $avg ? round((float) $avg->avg_disk, 1) : null,
                'aset' => $device->aset ? [
                    'id' => $device->aset->id,
                    'kode_aset' => $device->aset->kode_aset,
                ] : null,
                'os' => $device->hardware?->os,
            ];
        })->values()->all();

        return [
            'summary' => [
                'total' => $devices->count(),
                'online' => $online,
                'offline' => $devices->count() - $online,
                'high_load' => $highLoad,
            ],
            'devices' => $rows,
        ];
    }

    /**
     * @param  callable(resource): void  $writer
     */
    private function streamExport(
        ExportTianjiLaporanRequest $request,
        string $kind,
        callable $writer,
    ): StreamedResponse|RedirectResponse {
        if (! $this->tianji->isConfigured()) {
            return redirect()
                ->route('infrastruktur.index')
                ->with('error', 'Konfigurasi Tianji belum lengkap.');
        }

        try {
            $this->tianji->listMonitors();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('infrastruktur.index', [
                    'start_date' => $request->validated('start_date'),
                    'end_date' => $request->validated('end_date'),
                ])
                ->with('error', $e->getMessage());
        }

        $filename = sprintf(
            'tianji-%s-%s_%s.csv',
            $kind,
            $request->validated('start_date'),
            $request->validated('end_date'),
        );

        return Response::streamDownload(function () use ($writer): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $monitor
     * @return array<string, mixed>
     */
    private function presentDashboardMonitor(array $monitor): array
    {
        return [
            'id' => (string) ($monitor['id'] ?? ''),
            'name' => (string) ($monitor['name'] ?? ''),
            'type' => (string) ($monitor['type'] ?? ''),
            'target' => $this->monitorTarget($monitor),
            'active' => (bool) ($monitor['active'] ?? false),
            'current_status' => (string) ($monitor['current_status'] ?? 'unknown'),
            'current_latency_ms' => $monitor['current_latency_ms'] ?? null,
            'period_total_checks' => (int) ($monitor['period_total_checks'] ?? 0),
            'period_up_checks' => (int) ($monitor['period_up_checks'] ?? 0),
            'period_down_checks' => (int) ($monitor['period_down_checks'] ?? 0),
            'period_uptime_percent' => $monitor['period_uptime_percent'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $monitor
     */
    private function monitorTarget(array $monitor): string
    {
        $payload = $monitor['payload'] ?? [];

        if (! is_array($payload)) {
            return '';
        }

        foreach (['hostname', 'url', 'host', 'ip'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return '';
    }

    /**
     * @param  list<array{value?: int|float|null, createdAt?: string}>  $points
     * @return array{total: int, up: int, down: int, uptime_percent: string, avg_latency_ms: string}
     */
    private function summarizePoints(array $points): array
    {
        $total = count($points);
        $up = 0;
        $down = 0;
        $latencySum = 0.0;
        $latencyCount = 0;

        foreach ($points as $point) {
            $value = $point['value'] ?? null;

            if ($value === -1 || $value === -1.0) {
                $down++;

                continue;
            }

            $up++;

            if (is_numeric($value) && (float) $value > 0) {
                $latencySum += (float) $value;
                $latencyCount++;
            }
        }

        $uptime = $total > 0 ? round(($up / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'up' => $up,
            'down' => $down,
            'uptime_percent' => number_format($uptime, 1, '.', ''),
            'avg_latency_ms' => $latencyCount > 0
                ? number_format($latencySum / $latencyCount, 1, '.', '')
                : '',
        ];
    }
}
