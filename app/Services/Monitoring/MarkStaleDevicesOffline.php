<?php

namespace App\Services\Monitoring;

use App\Models\MonitoredDevice;
use App\Support\AgentLog;

class MarkStaleDevicesOffline
{
    /**
     * Mark online devices offline when last heartbeat is older than the configured threshold.
     */
    public function __invoke(?int $offlineAfterMinutes = null): int
    {
        $minutes = max(1, $offlineAfterMinutes ?? (int) config('agent.offline_after_minutes', 5));
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

        return $updated;
    }
}
