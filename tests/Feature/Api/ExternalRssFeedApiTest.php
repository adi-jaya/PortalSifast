<?php

use App\Models\User;
use App\Services\WebOfficial\ExternalRssFeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();

    Cache::forget('web_official.external_rss.feed');

    Config::set('services.external_rss.enabled', true);
    Config::set('services.external_rss.sources', [
        [
            'id' => 'suara-muhammadiyah',
            'name' => 'Suara Muhammadiyah',
            'url' => 'https://web.suaramuhammadiyah.id/feed/',
            'websiteUrl' => 'https://suaramuhammadiyah.id',
        ],
        [
            'id' => 'muhammadiyah',
            'name' => 'Muhammadiyah.or.id',
            'url' => 'https://muhammadiyah.or.id/feed/',
            'websiteUrl' => 'https://muhammadiyah.or.id',
        ],
    ]);
});

function sampleRssXml(string $title, string $link, string $sourceLabel = 'Test'): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
    <title>{$sourceLabel}</title>
    <item>
        <title>{$title}</title>
        <link>{$link}</link>
        <description><![CDATA[<p>Ringkasan berita {$title}</p>]]></description>
        <pubDate>Thu, 02 Jul 2026 15:49:42 +0000</pubDate>
        <category><![CDATA[Berita]]></category>
        <enclosure url="https://cdn.example.com/{$title}.jpg" type="image/jpeg" />
    </item>
</channel>
</rss>
XML;
}

it('returns empty external rss feed when module is disabled', function (): void {
    Config::set('services.external_rss.enabled', false);

    $this->getJson('/api/berita-eksternal')
        ->assertSuccessful()
        ->assertJsonPath('meta.enabled', false)
        ->assertJsonPath('data.items', []);
});

it('returns cached external rss items publicly', function (): void {
    Cache::put('web_official.external_rss.feed', [
        'items' => [
            [
                'id' => 'item-1',
                'sourceId' => 'muhammadiyah',
                'sourceName' => 'Muhammadiyah.or.id',
                'sourceWebsiteUrl' => 'https://muhammadiyah.or.id',
                'title' => 'Berita Contoh',
                'link' => 'https://muhammadiyah.or.id/berita-contoh',
                'excerpt' => 'Ringkasan',
                'imageUrl' => 'https://cdn.example.com/berita.jpg',
                'publishedAt' => '2026-07-02T15:49:42+00:00',
                'categories' => ['Berita'],
            ],
        ],
        'syncedAt' => '2026-07-03T10:00:00+07:00',
        'source' => 'external_rss',
        'sourceStats' => [],
    ], now()->addHour());

    $this->getJson('/api/berita-eksternal')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.title', 'Berita Contoh');
});

it('syncs external rss feeds and merges sources', function (): void {
    Http::fake([
        'web.suaramuhammadiyah.id/*' => Http::response(
            sampleRssXml('Berita Suara', 'https://web.suaramuhammadiyah.id/berita-suara', 'Suara'),
            200,
            ['Content-Type' => 'application/rss+xml'],
        ),
        'muhammadiyah.or.id/*' => Http::response(
            sampleRssXml('Berita Muhammadiyah', 'https://muhammadiyah.or.id/berita-muhammadiyah', 'Muhammadiyah'),
            200,
            ['Content-Type' => 'application/rss+xml'],
        ),
    ]);

    $service = app(ExternalRssFeedService::class);
    $result = $service->syncFromFeeds();

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2);

    $this->getJson('/api/berita-eksternal?source=muhammadiyah')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.sourceId', 'muhammadiyah');
});

it('admin can read external rss status and sync', function (): void {
    Sanctum::actingAs($this->admin);

    Http::fake([
        'web.suaramuhammadiyah.id/*' => Http::response(
            sampleRssXml('Admin Sync Suara', 'https://web.suaramuhammadiyah.id/admin-sync'),
            200,
        ),
        'muhammadiyah.or.id/*' => Http::response(
            sampleRssXml('Admin Sync Muhammadiyah', 'https://muhammadiyah.or.id/admin-sync'),
            200,
        ),
    ]);

    $this->getJson('/api/admin/berita-eksternal/status')
        ->assertSuccessful()
        ->assertJsonPath('data.configured', true);

    $this->postJson('/api/admin/berita-eksternal/sync')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.count', 2);
});

it('non admin cannot access external rss admin endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/berita-eksternal/status')->assertForbidden();
    $this->postJson('/api/admin/berita-eksternal/sync')->assertForbidden();
});

it('external rss sync command succeeds with fake feeds', function (): void {
    Http::fake([
        '*' => Http::response(sampleRssXml('Command Sync', 'https://muhammadiyah.or.id/command-sync'), 200),
    ]);

    $this->artisan('rss:sync-external')->assertSuccessful();
});
