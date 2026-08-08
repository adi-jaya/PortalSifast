<?php

use App\Models\User;
use App\Services\Instagram\InstagramFeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->pemohon = User::factory()->pemohon()->create();

    Cache::forget('web_official.instagram.feed');
    Cache::forget('web_official.instagram.feed.meta');

    Config::set('services.instagram.enabled', true);
    Config::set('services.instagram.access_token', 'test-token');
    Config::set('services.instagram.user_id', '17841400000000000');
    Config::set('services.instagram.profile_url', 'https://instagram.com/rsasitifatimah');
    Config::set('services.instagram.default_limit', 12);
    Config::set('services.instagram.graph_api_version', 'v21.0');
});

it('returns empty instagram feed when module is disabled', function (): void {
    Config::set('services.instagram.enabled', false);

    $this->getJson('/api/instagram-feed')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.enabled', false)
        ->assertJsonPath('data.posts', []);
});

it('returns cached instagram posts publicly', function (): void {
    Cache::put('web_official.instagram.feed', [
        'profileUrl' => 'https://instagram.com/rsasitifatimah',
        'posts' => [
            [
                'id' => 'post-1',
                'mediaType' => 'IMAGE',
                'imageUrl' => 'https://cdn.example.com/post-1.jpg',
                'permalink' => 'https://instagram.com/p/abc',
                'caption' => 'Promo MCU',
                'postedAt' => '2026-07-01T10:00:00+0000',
            ],
        ],
        'syncedAt' => '2026-07-01T12:00:00+07:00',
        'source' => 'instagram_graph_api',
    ], now()->addHour());

    $this->getJson('/api/instagram-feed')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.posts')
        ->assertJsonPath('data.posts.0.id', 'post-1')
        ->assertJsonPath('data.profileUrl', 'https://instagram.com/rsasitifatimah')
        ->assertJsonPath('meta.syncedAt', '2026-07-01T12:00:00+07:00');
});

it('syncs instagram feed from graph api and stores cache', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => '1789654321',
                    'caption' => 'Promo paket MCU',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://cdn.example.com/mcu.jpg',
                    'permalink' => 'https://instagram.com/p/abc123',
                    'timestamp' => '2026-07-01T10:00:00+0000',
                ],
                [
                    'id' => '1789654322',
                    'caption' => 'Video edukasi',
                    'media_type' => 'VIDEO',
                    'thumbnail_url' => 'https://cdn.example.com/video-thumb.jpg',
                    'permalink' => 'https://instagram.com/p/def456',
                    'timestamp' => '2026-06-28T08:00:00+0000',
                ],
            ],
        ], 200),
    ]);

    $service = app(InstagramFeedService::class);
    $result = $service->syncFromApi(12);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2);

    $this->getJson('/api/instagram-feed')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.posts')
        ->assertJsonPath('data.posts.1.mediaType', 'VIDEO')
        ->assertJsonPath('data.posts.1.imageUrl', 'https://cdn.example.com/video-thumb.jpg');
});

it('admin can read instagram feed status', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/admin/instagram-feed/status')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.configured', true);
});

it('admin can trigger instagram feed sync', function (): void {
    Sanctum::actingAs($this->admin);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => '1789654999',
                    'caption' => 'Info layanan',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://cdn.example.com/info.jpg',
                    'permalink' => 'https://instagram.com/p/sync',
                    'timestamp' => '2026-07-02T10:00:00+0000',
                ],
            ],
        ], 200),
    ]);

    $this->postJson('/api/admin/instagram-feed/sync')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.count', 1);
});

it('non admin cannot access instagram admin endpoints', function (): void {
    Sanctum::actingAs($this->pemohon);

    $this->getJson('/api/admin/instagram-feed/status')->assertForbidden();
    $this->postJson('/api/admin/instagram-feed/sync')->assertForbidden();
});

it('instagram sync command succeeds with fake graph api', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => '1789655000',
                    'caption' => 'Hello RS',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://cdn.example.com/hello.jpg',
                    'permalink' => 'https://instagram.com/p/hello',
                    'timestamp' => '2026-07-03T10:00:00+0000',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('instagram:sync-feed')
        ->assertSuccessful();
});
