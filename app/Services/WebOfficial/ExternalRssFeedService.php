<?php

namespace App\Services\WebOfficial;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExternalRssFeedService
{
    private const CACHE_KEY = 'web_official.external_rss.feed';

    public function isEnabled(): bool
    {
        return (bool) config('services.external_rss.enabled', true)
            && $this->sources() !== [];
    }

    /**
     * @return list<array{id: string, name: string, url: string, websiteUrl: string}>
     */
    public function sources(): array
    {
        $sources = config('services.external_rss.sources', []);

        if (! is_array($sources)) {
            return [];
        }

        return collect($sources)
            ->filter(fn (mixed $source): bool => is_array($source) && filled($source['id'] ?? null) && filled($source['url'] ?? null))
            ->map(fn (array $source): array => [
                'id' => (string) $source['id'],
                'name' => (string) ($source['name'] ?? $source['id']),
                'url' => (string) $source['url'],
                'websiteUrl' => (string) ($source['websiteUrl'] ?? $source['url']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForPublic(?int $limit = null, ?string $sourceId = null): array
    {
        $payload = $this->getCachedPayload();
        $items = is_array($payload) ? ($payload['items'] ?? []) : [];

        if ($sourceId !== null && $sourceId !== '') {
            $items = array_values(array_filter(
                $items,
                fn (array $item): bool => ($item['sourceId'] ?? '') === $sourceId,
            ));
        }

        if ($limit === null) {
            return $items;
        }

        return array_slice($items, 0, min(max($limit, 1), 50));
    }

    /**
     * @return array{sources: list<array<string, string>>, items: list<array<string, mixed>>, syncedAt: string|null, source: string}
     */
    public function publicEnvelope(?int $limit = null, ?string $sourceId = null): array
    {
        $payload = $this->getCachedPayload();

        return [
            'sources' => collect($this->sources())->map(fn (array $source): array => [
                'id' => $source['id'],
                'name' => $source['name'],
                'websiteUrl' => $source['websiteUrl'],
            ])->all(),
            'items' => $this->listForPublic($limit, $sourceId),
            'syncedAt' => is_array($payload) ? ($payload['syncedAt'] ?? null) : null,
            'source' => is_array($payload) ? ($payload['source'] ?? 'cache') : 'empty',
        ];
    }

    /**
     * @return array{configured: bool, enabled: bool, count: int, syncedAt: string|null, sources: list<array<string, mixed>>}
     */
    public function status(): array
    {
        $payload = $this->getCachedPayload();
        $perSource = is_array($payload) ? ($payload['sourceStats'] ?? []) : [];

        return [
            'configured' => $this->sources() !== [],
            'enabled' => $this->isEnabled(),
            'count' => is_array($payload) ? count($payload['items'] ?? []) : 0,
            'syncedAt' => is_array($payload) ? ($payload['syncedAt'] ?? null) : null,
            'sources' => collect($this->sources())
                ->map(fn (array $source): array => [
                    'id' => $source['id'],
                    'name' => $source['name'],
                    'url' => $source['url'],
                    'websiteUrl' => $source['websiteUrl'],
                    'itemCount' => (int) ($perSource[$source['id']]['count'] ?? 0),
                    'lastError' => $perSource[$source['id']]['error'] ?? null,
                ])
                ->all(),
        ];
    }

    /**
     * @return array{success: bool, count: int, syncedAt: string|null, message: string|null, sourceStats: array<string, array{count: int, error: string|null}>}
     */
    public function syncFromFeeds(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'count' => 0,
                'syncedAt' => $this->getCachedPayload()['syncedAt'] ?? null,
                'message' => 'Feed RSS eksternal belum dikonfigurasi atau nonaktif.',
                'sourceStats' => [],
            ];
        }

        $items = [];
        $sourceStats = [];

        foreach ($this->sources() as $source) {
            try {
                $parsed = $this->fetchFeedItems($source);
                $items = array_merge($items, $parsed);
                $sourceStats[$source['id']] = [
                    'count' => count($parsed),
                    'error' => null,
                ];
            } catch (\Throwable $exception) {
                $sourceStats[$source['id']] = [
                    'count' => 0,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        if ($items === []) {
            return [
                'success' => false,
                'count' => 0,
                'syncedAt' => $this->getCachedPayload()['syncedAt'] ?? null,
                'message' => 'Semua sumber RSS gagal diambil atau tidak mengembalikan item.',
                'sourceStats' => $sourceStats,
            ];
        }

        $items = $this->sortAndDedupeItems($items);
        $syncedAt = now()->toIso8601String();

        Cache::put(self::CACHE_KEY, [
            'items' => $items,
            'syncedAt' => $syncedAt,
            'source' => 'external_rss',
            'sourceStats' => $sourceStats,
        ], now()->addMinutes($this->cacheMinutes()));

        return [
            'success' => true,
            'count' => count($items),
            'syncedAt' => $syncedAt,
            'message' => null,
            'sourceStats' => $sourceStats,
        ];
    }

    /**
     * @param  array{id: string, name: string, url: string, websiteUrl: string}  $source
     * @return list<array<string, mixed>>
     */
    private function fetchFeedItems(array $source): array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'PortalSifast-RSS/1.0',
                'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
            ])
            ->get($source['url']);

        if (! $response->successful()) {
            throw new \RuntimeException(sprintf(
                'RSS %s mengembalikan HTTP %s.',
                $source['name'],
                $response->status(),
            ));
        }

        $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            throw new \RuntimeException(sprintf('RSS %s tidak valid.', $source['name']));
        }

        $channel = $xml->channel ?? $xml;
        $items = [];

        foreach ($channel->item ?? [] as $item) {
            if (! $item instanceof \SimpleXMLElement) {
                continue;
            }

            $normalized = $this->normalizeItem($item, $source);

            if ($normalized !== null) {
                $items[] = $normalized;
            }
        }

        return $items;
    }

    /**
     * @param  array{id: string, name: string, url: string, websiteUrl: string}  $source
     * @return array<string, mixed>|null
     */
    private function normalizeItem(\SimpleXMLElement $item, array $source): ?array
    {
        $title = $this->cleanText((string) ($item->title ?? ''));
        $link = trim((string) ($item->link ?? ''));

        if ($title === '' || $link === '') {
            return null;
        }

        $description = $this->cleanText((string) ($item->description ?? ''));
        $content = $this->elementContent($item, 'content', 'encoded')
            ?? $this->elementContent($item, 'content')
            ?? $description;

        $publishedAt = $this->normalizeDate((string) ($item->pubDate ?? ''));

        return [
            'id' => hash('sha256', $source['id'].'|'.$link),
            'sourceId' => $source['id'],
            'sourceName' => $source['name'],
            'sourceWebsiteUrl' => $source['websiteUrl'],
            'title' => $title,
            'link' => $link,
            'excerpt' => $this->buildExcerpt($content !== '' ? $content : $description),
            'imageUrl' => $this->resolveImageUrl($item, $content),
            'publishedAt' => $publishedAt,
            'categories' => $this->extractCategories($item),
        ];
    }

    private function elementContent(\SimpleXMLElement $item, string $namespace, ?string $localName = null): ?string
    {
        if ($localName === null) {
            $value = trim((string) ($item->{$namespace} ?? ''));

            return $value !== '' ? $value : null;
        }

        $namespaces = $item->getNameSpaces(true);
        $prefix = $namespaces[$namespace] ?? null;

        if ($prefix === null) {
            return null;
        }

        $children = $item->children($prefix);
        $value = trim((string) ($children->{$localName} ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * @return list<string>
     */
    private function extractCategories(\SimpleXMLElement $item): array
    {
        return collect($item->category ?? [])
            ->map(fn (\SimpleXMLElement $category): string => $this->cleanText((string) $category))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveImageUrl(\SimpleXMLElement $item, string $content): ?string
    {
        $enclosureUrl = trim((string) ($item->enclosure['url'] ?? ''));
        if ($enclosureUrl !== '' && str_starts_with(strtolower((string) ($item->enclosure['type'] ?? '')), 'image/')) {
            return $enclosureUrl;
        }

        $mediaThumbnail = $this->mediaElement($item, 'thumbnail');
        if ($mediaThumbnail !== null) {
            return $mediaThumbnail;
        }

        $mediaContent = $this->mediaElement($item, 'content');
        if ($mediaContent !== null) {
            return $mediaContent;
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }

    private function mediaElement(\SimpleXMLElement $item, string $localName): ?string
    {
        $namespaces = $item->getNameSpaces(true);
        $mediaNamespace = $namespaces['media'] ?? null;

        if ($mediaNamespace === null) {
            return null;
        }

        $media = $item->children($mediaNamespace);
        $element = $media->{$localName} ?? null;

        if ($element === null) {
            return null;
        }

        $url = trim((string) ($element['url'] ?? ''));

        return $url !== '' ? $url : null;
    }

    private function buildExcerpt(string $content): ?string
    {
        $plain = trim(strip_tags(html_entity_decode($content, ENT_QUOTES | ENT_HTML5)));

        if ($plain === '') {
            return null;
        }

        return Str::limit(preg_replace('/\s+/u', ' ', $plain) ?? $plain, 220);
    }

    private function cleanText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }

    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function sortAndDedupeItems(array $items): array
    {
        return collect($items)
            ->unique('link')
            ->sortByDesc(fn (array $item): int => strtotime((string) ($item['publishedAt'] ?? '')) ?: 0)
            ->values()
            ->all();
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

        if (($cached['items'] ?? []) === []) {
            Cache::forget(self::CACHE_KEY);

            return null;
        }

        return $cached;
    }

    private function cacheMinutes(): int
    {
        return max((int) config('services.external_rss.cache_minutes', 60), 5);
    }
}
