<?php

namespace App\Services\Tianji;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TianjiClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listMonitors(): array
    {
        $payload = $this->getJson($this->workspacePath('monitor/all'));

        if (! is_array($payload)) {
            throw new RuntimeException('Respons daftar monitor Tianji tidak valid.');
        }

        /** @var list<array<string, mixed>> $payload */
        return array_values($payload);
    }

    /**
     * @return list<array{value: int|float|null, createdAt: string}>
     */
    public function monitorData(string $monitorId, int $startAtMs, int $endAtMs): array
    {
        $payload = $this->getJson(
            $this->workspacePath('monitor/'.$monitorId.'/data'),
            [
                'startAt' => $startAtMs,
                'endAt' => $endAtMs,
            ],
        );

        if (! is_array($payload)) {
            throw new RuntimeException('Respons data monitor Tianji tidak valid.');
        }

        /** @var list<array{value: int|float|null, createdAt: string}> $payload */
        return array_values($payload);
    }

    /**
     * @return list<array{day: string, totalCount: int, upCount: int, upRate: float|int}>
     */
    public function publicSummary(string $monitorId): array
    {
        $payload = $this->getJson($this->workspacePath('monitor/'.$monitorId.'/publicSummary'));

        if (! is_array($payload)) {
            return [];
        }

        /** @var list<array{day: string, totalCount: int, upCount: int, upRate: float|int}> $payload */
        return array_values($payload);
    }

    /**
     * @return list<array{value: int|float|null, createdAt: string}>
     */
    public function recentData(string $monitorId, int $take = 1): array
    {
        $payload = $this->getJson(
            $this->workspacePath('monitor/'.$monitorId.'/recentData'),
            ['take' => $take],
        );

        if (! is_array($payload)) {
            return [];
        }

        /** @var list<array{value: int|float|null, createdAt: string}> $payload */
        return array_values($payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function monitorEvents(?string $monitorId = null, int $limit = 20): array
    {
        $query = ['limit' => $limit];

        if ($monitorId !== null) {
            $query['monitorId'] = $monitorId;
        }

        $payload = $this->getJson($this->workspacePath('monitor/events'), $query);

        if (! is_array($payload)) {
            return [];
        }

        /** @var list<array<string, mixed>> $payload */
        return array_values($payload);
    }

    /**
     * Enrich monitors with current status + period uptime using concurrent HTTP.
     *
     * @param  list<array<string, mixed>>  $monitors
     * @return list<array<string, mixed>>
     */
    public function enrichMonitorsForDashboard(array $monitors, CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        if ($monitors === []) {
            return [];
        }

        $this->assertConfigured();

        $baseUrl = rtrim((string) config('services.tianji.base_url'), '/');
        $token = (string) config('services.tianji.api_key');
        $timeout = (int) config('services.tianji.timeout', 30);
        $startDay = $startDate->toDateString();
        $endDay = $endDate->toDateString();

        $responses = Http::pool(function (Pool $pool) use ($monitors, $baseUrl, $token, $timeout): array {
            $requests = [];

            foreach ($monitors as $monitor) {
                $id = (string) ($monitor['id'] ?? '');

                if ($id === '') {
                    continue;
                }

                $requests[$id.':summary'] = $pool
                    ->as($id.':summary')
                    ->baseUrl($baseUrl)
                    ->withToken($token)
                    ->acceptJson()
                    ->timeout($timeout)
                    ->get('/open/'.$this->workspacePath('monitor/'.$id.'/publicSummary'));

                $requests[$id.':recent'] = $pool
                    ->as($id.':recent')
                    ->baseUrl($baseUrl)
                    ->withToken($token)
                    ->acceptJson()
                    ->timeout($timeout)
                    ->get('/open/'.$this->workspacePath('monitor/'.$id.'/recentData'), ['take' => 1]);
            }

            return $requests;
        });

        $enriched = [];

        foreach ($monitors as $monitor) {
            $id = (string) ($monitor['id'] ?? '');
            $summaryResponse = $responses[$id.':summary'] ?? null;
            $recentResponse = $responses[$id.':recent'] ?? null;

            $summaryDays = (is_object($summaryResponse) && method_exists($summaryResponse, 'successful') && $summaryResponse->successful())
                ? (array) $summaryResponse->json()
                : [];
            $recentPoints = (is_object($recentResponse) && method_exists($recentResponse, 'successful') && $recentResponse->successful())
                ? (array) $recentResponse->json()
                : [];

            $period = $this->aggregateSummaryForPeriod($summaryDays, $startDay, $endDay);
            $latest = $recentPoints[0] ?? null;
            $latestValue = is_array($latest) ? ($latest['value'] ?? null) : null;
            $isDown = $latestValue === -1 || $latestValue === -1.0;

            $enriched[] = array_merge($monitor, [
                'current_status' => $latest === null ? 'unknown' : ($isDown ? 'down' : 'up'),
                'current_latency_ms' => (! $isDown && is_numeric($latestValue) && (float) $latestValue > 0)
                    ? round((float) $latestValue, 1)
                    : null,
                'period_total_checks' => $period['total'],
                'period_up_checks' => $period['up'],
                'period_down_checks' => $period['down'],
                'period_uptime_percent' => $period['uptime_percent'],
            ]);
        }

        return $enriched;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.tianji.base_url'))
            && filled(config('services.tianji.api_key'))
            && filled(config('services.tianji.workspace_id'));
    }

    /**
     * @param  list<array<string, mixed>>  $days
     * @return array{total: int, up: int, down: int, uptime_percent: float|null}
     */
    public function aggregateSummaryForPeriod(array $days, string $startDay, string $endDay): array
    {
        $total = 0;
        $up = 0;

        foreach ($days as $day) {
            if (! is_array($day)) {
                continue;
            }

            $date = (string) ($day['day'] ?? '');

            if ($date < $startDay || $date > $endDay) {
                continue;
            }

            $dayTotal = (int) ($day['totalCount'] ?? 0);
            $dayUp = (int) ($day['upCount'] ?? 0);
            $total += $dayTotal;
            $up += $dayUp;
        }

        return [
            'total' => $total,
            'up' => $up,
            'down' => max(0, $total - $up),
            'uptime_percent' => $total > 0 ? round(($up / $total) * 100, 1) : null,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function getJson(string $path, array $query = []): mixed
    {
        $this->assertConfigured();

        $baseUrl = rtrim((string) config('services.tianji.base_url'), '/');
        $timeout = (int) config('services.tianji.timeout', 30);

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken((string) config('services.tianji.api_key'))
                ->acceptJson()
                ->timeout($timeout)
                ->get('/open/'.$path, $query)
                ->throw();
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak bisa terhubung ke Tianji. Pastikan server monitoring online di jaringan lokal.', previous: $e);
        } catch (RequestException $e) {
            $status = $e->response?->status();

            throw new RuntimeException(
                $status
                    ? "Tianji mengembalikan error HTTP {$status}."
                    : 'Permintaan ke Tianji gagal.',
                previous: $e,
            );
        }

        return $response->json();
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Konfigurasi Tianji belum lengkap. Periksa TIANJI_BASE_URL, TIANJI_API_KEY, dan TIANJI_WORKSPACE_ID.');
        }
    }

    private function workspacePath(string $suffix): string
    {
        $workspaceId = (string) config('services.tianji.workspace_id');

        return 'workspace/'.$workspaceId.'/'.ltrim($suffix, '/');
    }
}
