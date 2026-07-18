<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class InventarisPhotoResolver
{
    /**
     * Resolve inventaris photo path into a data URI (base64) for safe browser display.
     * Does not expose the internal SIMRS host to the client.
     */
    public function toDataUri(?string $photo): ?string
    {
        $resolved = $this->resolve($photo);

        if ($resolved === null) {
            return null;
        }

        return 'data:'.$resolved['mime'].';base64,'.base64_encode($resolved['binary']);
    }

    /**
     * @return array{binary: string, mime: string}|null
     */
    public function resolve(?string $photo): ?array
    {
        if ($photo === null || $photo === '') {
            return null;
        }

        if (str_starts_with($photo, 'data:')) {
            if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $photo, $matches)) {
                return null;
            }

            $binary = base64_decode($matches[2], true);

            if ($binary === false || $binary === '') {
                return null;
            }

            return ['binary' => $binary, 'mime' => $matches[1]];
        }

        $binary = $this->readBinary($photo);

        if ($binary === null || $binary === '') {
            return null;
        }

        return [
            'binary' => $binary,
            'mime' => $this->detectMime($binary, $photo),
        ];
    }

    public function isLocalPortalUpload(string $photo): bool
    {
        return str_starts_with($photo, 'inventaris/');
    }

    private function readBinary(string $photo): ?string
    {
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $this->fetchRemote($photo);
        }

        if ($this->isLocalPortalUpload($photo) && Storage::disk('public')->exists($photo)) {
            $contents = Storage::disk('public')->get($photo);

            return is_string($contents) ? $contents : null;
        }

        $baseUrl = config('inventaris.asset_base_url');
        $remoteUrl = $baseUrl.'/'.ltrim(str_replace('\\', '/', $photo), '/');

        return $this->fetchRemote($remoteUrl);
    }

    private function fetchRemote(string $url): ?string
    {
        try {
            $encodedUrl = $this->encodeUrlPath($url);

            $response = Http::timeout(8)
                ->withOptions(['allow_redirects' => true])
                ->get($encodedUrl);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();

            return $body !== '' ? $body : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Encode path segments so filenames with spaces/special chars still resolve on SIMRS.
     */
    private function encodeUrlPath(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return $url;
        }

        $encodedPath = collect(explode('/', $parts['path']))
            ->map(fn (string $segment) => $segment === '' ? '' : rawurlencode(rawurldecode($segment)))
            ->implode('/');

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.$encodedPath.$query;
    }

    private function detectMime(string $binary, string $photo): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($binary);

        if (is_string($detected) && str_starts_with($detected, 'image/')) {
            return $detected;
        }

        $extension = strtolower(pathinfo(parse_url($photo, PHP_URL_PATH) ?: $photo, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => 'image/jpeg',
        };
    }
}
