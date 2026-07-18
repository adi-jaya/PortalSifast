<?php

namespace App\Console\Commands;

use App\Services\WebOfficial\ExternalRssFeedService;
use Illuminate\Console\Command;

class SyncExternalRssFeedCommand extends Command
{
    protected $signature = 'rss:sync-external';

    protected $description = 'Sinkronkan berita dari feed RSS eksternal (Muhammadiyah, Suara Muhammadiyah, dll.)';

    public function handle(ExternalRssFeedService $externalRssFeedService): int
    {
        if (! $externalRssFeedService->isEnabled()) {
            $this->error('Feed RSS eksternal nonaktif atau belum dikonfigurasi.');

            return self::FAILURE;
        }

        $result = $externalRssFeedService->syncFromFeeds();

        foreach ($result['sourceStats'] as $sourceId => $stats) {
            if (($stats['error'] ?? null) !== null) {
                $this->warn(sprintf('%s: gagal — %s', $sourceId, $stats['error']));
            } else {
                $this->line(sprintf('%s: %d item', $sourceId, $stats['count']));
            }
        }

        if (! $result['success']) {
            $this->error($result['message'] ?? 'Sinkronisasi RSS gagal.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sinkronisasi RSS berhasil. Total %d berita, terakhir sync: %s.',
            $result['count'],
            $result['syncedAt'] ?? '-'
        ));

        return self::SUCCESS;
    }
}
