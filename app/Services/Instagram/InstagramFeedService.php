<?php

namespace App\Services\Instagram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InstagramFeedService
{
    private const CACHE_KEY = 'web_official.instagram.feed';

    private const META_CACHE_KEY = 'web_official.instagram.feed.meta';

    public function isConfigured(): bool
    {
        if (! config('services.instagram.enabled')) {
            return false;
        }

        return filled(config('services.instagram.access_token'))
            && filled(config('services.instagram.user_id'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForPublic(?int $limit = null): array
    {
        $payload = $this->getCachedPayload();

        if ($payload === null) {
            return [];
        }

        $posts = $payload['posts'] ?? [];

        if ($limit === null) {
            return $posts;
        }

        return array_slice($posts, 0, min(max($limit, 1), 50));
    }

    /**
     * @return array{profileUrl: string, posts: list<array<string, mixed>>, syncedAt: string|null, source: string}
     */
    public function publicEnvelope(?int $limit = null): array
    {
        $payload = $this->getCachedPayload();
        $posts = $this->listForPublic($limit);

        return [
            'profileUrl' => (string) config('services.instagram.profile_url'),
            'posts' => $posts,
            'syncedAt' => is_array($payload) ? ($payload['syncedAt'] ?? null) : null,
            'source' => is_array($payload) ? ($payload['source'] ?? 'cache') : 'empty',
        ];
    }

    /**
     * @return array{success: bool, count: int, syncedAt: string|null, message: string|null}
     */
    public function syncFromApi(?int $limit = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'count' => 0,
                'syncedAt' => null,
                'message' => 'Instagram feed belum dikonfigurasi.',
            ];
        }

        try {
            $posts = $this->fetchPostsFromGraphApi($limit ?? (int) config('services.instagram.default_limit', 12));
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'count' => 0,
                'syncedAt' => $this->getCachedPayload()['syncedAt'] ?? null,
                'message' => $exception->getMessage(),
            ];
        }

        if ($posts === []) {
            return [
                'success' => false,
                'count' => 0,
                'syncedAt' => $this->getCachedPayload()['syncedAt'] ?? null,
                'message' => 'Instagram API mengembalikan daftar kosong.',
            ];
        }

        $syncedAt = now()->toIso8601String();
        $payload = [
            'profileUrl' => (string) config('services.instagram.profile_url'),
            'posts' => $posts,
            'syncedAt' => $syncedAt,
            'source' => 'instagram_graph_api',
        ];

        Cache::put(self::CACHE_KEY, $payload, now()->addMinutes($this->cacheMinutes()));
        Cache::put(self::META_CACHE_KEY, [
            'syncedAt' => $syncedAt,
            'count' => count($posts),
        ], now()->addMinutes($this->cacheMinutes()));

        return [
            'success' => true,
            'count' => count($posts),
            'syncedAt' => $syncedAt,
            'message' => null,
        ];
    }

    /**
     * @return array{configured: bool, enabled: bool, count: int, syncedAt: string|null, profileUrl: string}
     */
    public function status(): array
    {
        $payload = $this->getCachedPayload();

        return [
            'configured' => $this->isConfigured(),
            'enabled' => (bool) config('services.instagram.enabled'),
            'count' => is_array($payload) ? count($payload['posts'] ?? []) : 0,
            'syncedAt' => is_array($payload) ? ($payload['syncedAt'] ?? null) : null,
            'profileUrl' => (string) config('services.instagram.profile_url'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCachedPayload(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_array($cached)) {
            return null;
        }

        if (($cached['posts'] ?? []) === []) {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::META_CACHE_KEY);

            return null;
        }

        return $cached;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPostsFromGraphApi(int $limit): array
    {
        $version = (string) config('services.instagram.graph_api_version', 'v21.0');
        $userId = (string) config('services.instagram.user_id');
        $token = (string) config('services.instagram.access_token');

        $response = Http::timeout(20)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$userId}/media", [
                'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
                'limit' => min(max($limit, 1), 50),
                'access_token' => $token,
            ]);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new \RuntimeException('Instagram Graph API error: '.$message);
        }

        $items = $response->json('data');

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (mixed $item): ?array => $this->normalizePost(is_array($item) ? $item : []))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function normalizePost(array $item): ?array
    {
        $id = isset($item['id']) ? (string) $item['id'] : '';
        $permalink = isset($item['permalink']) ? (string) $item['permalink'] : '';

        if ($id === '' || $permalink === '') {
            return null;
        }

        $mediaType = strtoupper((string) ($item['media_type'] ?? 'IMAGE'));
        $imageUrl = $this->resolveImageUrl($item, $mediaType);

        if ($imageUrl === null) {
            return null;
        }

        return [
            'id' => $id,
            'mediaType' => $mediaType,
            'imageUrl' => $imageUrl,
            'permalink' => $permalink,
            'caption' => $this->normalizeCaption($item['caption'] ?? null),
            'postedAt' => isset($item['timestamp']) ? (string) $item['timestamp'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveImageUrl(array $item, string $mediaType): ?string
    {
        if ($mediaType === 'VIDEO') {
            $thumbnail = isset($item['thumbnail_url']) ? (string) $item['thumbnail_url'] : '';

            return $thumbnail !== '' ? $thumbnail : null;
        }

        $mediaUrl = isset($item['media_url']) ? (string) $item['media_url'] : '';

        return $mediaUrl !== '' ? $mediaUrl : null;
    }

    private function normalizeCaption(mixed $caption): ?string
    {
        if (! is_string($caption)) {
            return null;
        }

        $trimmed = trim($caption);

        return $trimmed === '' ? null : Str::limit($trimmed, 500);
    }

    private function cacheMinutes(): int
    {
        return max((int) config('services.instagram.cache_minutes', 60), 5);
    }
}
