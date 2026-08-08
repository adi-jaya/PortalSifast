<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class WebOfficialMediaStorageService
{
    private const MAX_SIZE_KB = 2048;

    /**
     * @return array{url: string, path: string, original_name: string, mime_type: string, size: int}
     */
    public function store(UploadedFile $file, string $folder = 'informasi'): array
    {
        $folder = $this->sanitizeFolder($folder);
        $filename = Str::uuid()->toString().'.'.$file->guessExtension();
        $path = $file->storeAs("webofficial/{$folder}", $filename, 'public');

        if ($path === false || $path === '') {
            throw new \RuntimeException('Gagal menyimpan file. Periksa folder storage atau jalankan php artisan storage:link.');
        }

        return [
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'original_name' => $this->safeOriginalFilename($file),
            'mime_type' => (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream'),
            'size' => $file->getSize() ?: 0,
        ];
    }

    public function delete(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        if (! str_starts_with($path, 'webofficial/')) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    public static function maxSizeKb(): int
    {
        return self::MAX_SIZE_KB;
    }

    private function sanitizeFolder(string $folder): string
    {
        $folder = Str::slug($folder);

        return in_array($folder, ['informasi', 'kamar-inap', 'promosi', 'poliklinik'], true) ? $folder : 'informasi';
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $name = str_replace(["\0", "\r", "\n"], '', $file->getClientOriginalName());

        return Str::limit(basename($name), 255, '');
    }
}
