<?php

namespace App\Console\Commands;

use App\Services\Monitoring\MarkStaleDevicesOffline;
use Illuminate\Console\Command;

class MarkMonitoredDevicesOfflineCommand extends Command
{
    protected $signature = 'monitoring:mark-offline';

    protected $description = 'Mark monitored devices offline when heartbeat is stale';

    public function handle(MarkStaleDevicesOffline $markStaleDevicesOffline): int
    {
        $minutes = max(1, (int) config('agent.offline_after_minutes', 5));
        $updated = $markStaleDevicesOffline();

        $this->info("Marked {$updated} device(s) offline (threshold {$minutes} minutes).");

        return self::SUCCESS;
    }
}
