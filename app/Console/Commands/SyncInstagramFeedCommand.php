<?php

namespace App\Console\Commands;

use App\Services\Instagram\InstagramFeedService;
use Illuminate\Console\Command;

class SyncInstagramFeedCommand extends Command
{
    protected $signature = 'instagram:sync-feed
                            {--limit= : Jumlah postingan yang diambil dari Instagram Graph API}';

    protected $description = 'Sinkronkan feed Instagram RS ke cache PortalSifast untuk website official';

    public function handle(InstagramFeedService $instagramFeedService): int
    {
        if (! $instagramFeedService->isConfigured()) {
            $this->error('Instagram feed belum dikonfigurasi. Set INSTAGRAM_FEED_ENABLED, INSTAGRAM_ACCESS_TOKEN, dan INSTAGRAM_USER_ID di .env.');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : null;

        $result = $instagramFeedService->syncFromApi($limit);

        if (! $result['success']) {
            $this->error($result['message'] ?? 'Sinkronisasi Instagram gagal.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sinkronisasi Instagram berhasil. %d postingan, terakhir sync: %s.',
            $result['count'],
            $result['syncedAt'] ?? '-'
        ));

        return self::SUCCESS;
    }
}
