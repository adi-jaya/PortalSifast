<?php

namespace App\Console\Commands;

use App\Models\DeviceMetricSample;
use Illuminate\Console\Command;

class PruneDeviceMetricSamplesCommand extends Command
{
    protected $signature = 'monitoring:prune-metric-samples
                            {--days= : Override retention days from config}
                            {--chunk=1000 : Delete batch size}';

    protected $description = 'Delete device metric samples older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('agent.metric_retention_days', 7));
        $chunk = max(100, (int) $this->option('chunk'));

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $threshold = now()->subDays($days);
        $deleted = 0;

        do {
            $ids = DeviceMetricSample::query()
                ->where('collected_at', '<', $threshold)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DeviceMetricSample::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunk);

        if ($deleted > 0) {
            AgentLog::info('Pruned device metric samples', [
                'deleted' => $deleted,
                'retention_days' => $days,
                'threshold' => $threshold->toIso8601String(),
            ]);
        }

        $this->info("Deleted {$deleted} metric sample(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
