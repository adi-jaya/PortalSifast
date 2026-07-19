<?php

namespace App\Console\Commands;

use App\Models\MonitoredDevice;
use App\Support\AgentLog;
use Illuminate\Console\Command;

class MarkMonitoredDevicesOfflineCommand extends Command
{
    protected $signature = 'monitoring:mark-offline';

    protected $description = 'Mark monitored devices offline when heartbeat is stale';

    public function handle(): int
    {
        $minutes = max(1, (int) config('agent.offline_after_minutes', 5));
        $threshold = now()->subMinutes($minutes);

        $updated = MonitoredDevice::query()
            ->where('status', MonitoredDevice::STATUS_ONLINE)
            ->where(function ($query) use ($threshold): void {
                $query
                    ->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $threshold);
            })
            ->update(['status' => MonitoredDevice::STATUS_OFFLINE]);

        if ($updated > 0) {
            AgentLog::info('Marked monitored devices offline', [
                'count' => $updated,
                'threshold_minutes' => $minutes,
            ]);
        }

        $this->info("Marked {$updated} device(s) offline (threshold {$minutes} minutes).");

        return self::SUCCESS;
    }
}
