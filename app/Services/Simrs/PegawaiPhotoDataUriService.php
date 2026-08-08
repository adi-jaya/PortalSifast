<?php

namespace App\Services\Simrs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class PegawaiPhotoDataUriService
{
    /**
     * @var array<string, string>
     */
    private const EXTENSION_MIME = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    public function photoApiPath(string $kdDokter): string
    {
        return '/api/dokter/'.rawurlencode($kdDokter).'/foto';
    }

    public function photoPathForDokter(string $kdDokter): ?string
    {
        $photo = DB::connection('dbsimrs')
            ->table('pegawai')
            ->where('nik', $kdDokter)
            ->value('photo');

        if (! is_string($photo) || trim($photo) === '') {
            return null;
        }

        return $photo;
    }

    public function toDataUri(?string $photoPath): ?string
    {
        if ($photoPath === null || trim($photoPath) === '') {
            return null;
        }

        $normalizedPath = ltrim(trim($photoPath), '/');
        $cacheKey = $this->dataUriCacheKey($normalizedPath);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($normalizedPath): ?string {
            $binary = $this->fetchBinary($normalizedPath);

            if ($binary === null) {
                return null;
            }

            return 'data:'.$binary['mime'].';base64,'.base64_encode($binary['content']);
        });
    }

    /**
     * @param  list<string|null>  $photoPaths
     * @return array<string, string|null>
     */
    public function toDataUriMany(array $photoPaths): array
    {
        $uniquePaths = array_values(array_unique(array_filter(
            $photoPaths,
            static fn (?string $path): bool => $path !== null && trim($path) !== '',
        )));

        if ($uniquePaths === []) {
            return [];
        }

        $results = [];
        $pending = [];

        foreach ($uniquePaths as $photoPath) {
            $normalizedPath = ltrim(trim($photoPath), '/');
            $cached = Cache::get($this->dataUriCacheKey($normalizedPath));

            if (is_string($cached)) {
                $results[$photoPath] = $cached;
            } else {
                $pending[$photoPath] = $normalizedPath;
            }
        }

        if ($pending !== []) {
            $responses = Http::pool(function ($pool) use ($pending): void {
                foreach ($pending as $photoPath => $normalizedPath) {
                    $pool->as($photoPath)
                        ->timeout(8)
                        ->retry(1, 200)
                        ->get($this->buildPhotoUrl($normalizedPath));
                }
            });

            foreach ($pending as $photoPath => $normalizedPath) {
                $response = $responses[$photoPath] ?? null;
                $binary = $this->binaryFromResponse($normalizedPath, $response);
                $dataUri = $binary === null
                    ? null
                    : 'data:'.$binary['mime'].';base64,'.base64_encode($binary['content']);

                if ($dataUri !== null) {
                    Cache::put($this->dataUriCacheKey($normalizedPath), $dataUri, now()->addHours(12));
                    $this->cacheBinary($normalizedPath, $binary);
                }

                $results[$photoPath] = $dataUri;
            }
        }

        return $results;
    }

    /**
     * @return array{content: string, mime: string}|null
     */
    public function getBinaryForDokter(string $kdDokter): ?array
    {
        $photoPath = $this->photoPathForDokter($kdDokter);

        if ($photoPath === null) {
            return null;
        }

        $normalizedPath = ltrim(trim($photoPath), '/');
        $cached = Cache::get($this->binaryCacheKey($normalizedPath));

        if (is_array($cached) && isset($cached['content'], $cached['mime'])) {
            return [
                'content' => (string) $cached['content'],
                'mime' => (string) $cached['mime'],
            ];
        }

        $binary = $this->fetchBinary($normalizedPath);

        if ($binary === null) {
            return null;
        }

        $this->cacheBinary($normalizedPath, $binary);

        return $binary;
    }

    public function buildPhotoUrl(string $photoPath): string
    {
        $base = rtrim((string) config('services.simrs.pegawai_photo_base_url'), '/');
        $path = ltrim($photoPath, '/');
        $segments = explode('/', $path);

        return $base.'/'.implode('/', array_map(rawurlencode(...), $segments));
    }

    /**
     * @return array{foto: string|null, fotoUrl: string|null}
     */
    public function resolvePhotoFields(
        string $kdDokter,
        ?string $photoPath,
        bool $withFoto,
        array $dataUriCache,
    ): array {
        $hasPhoto = $photoPath !== null && trim($photoPath) !== '';

        return [
            'foto' => $withFoto && $hasPhoto
                ? ($dataUriCache[$photoPath] ?? null)
                : null,
            'fotoUrl' => $hasPhoto ? $this->photoApiPath($kdDokter) : null,
        ];
    }

    /**
     * @return array{content: string, mime: string}|null
     */
    private function fetchBinary(string $normalizedPath): ?array
    {
        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->get($this->buildPhotoUrl($normalizedPath));

            return $this->binaryFromResponse($normalizedPath, $response);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{content: string, mime: string}|null
     */
    private function binaryFromResponse(string $normalizedPath, mixed $response): ?array
    {
        if ($response === null || ! method_exists($response, 'successful') || ! $response->successful()) {
            return null;
        }

        $content = $response->body();

        if ($content === '') {
            return null;
        }

        return [
            'content' => $content,
            'mime' => $this->resolveMimeType($normalizedPath, $response->header('Content-Type')),
        ];
    }

    /**
     * @param  array{content: string, mime: string}  $binary
     */
    private function cacheBinary(string $normalizedPath, array $binary): void
    {
        Cache::put($this->binaryCacheKey($normalizedPath), $binary, now()->addHours(12));
    }

    private function dataUriCacheKey(string $normalizedPath): string
    {
        return 'simrs.pegawai-photo.data-uri.'.sha1($normalizedPath);
    }

    private function binaryCacheKey(string $normalizedPath): string
    {
        return 'simrs.pegawai-photo.binary.'.sha1($normalizedPath);
    }

    private function resolveMimeType(string $photoPath, ?string $contentType): string
    {
        if (is_string($contentType) && str_starts_with($contentType, 'image/')) {
            return strtok($contentType, ';') ?: 'image/jpeg';
        }

        $extension = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));

        return self::EXTENSION_MIME[$extension] ?? 'image/jpeg';
    }
}
