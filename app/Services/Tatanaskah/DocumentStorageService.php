<?php

namespace App\Services\Tatanaskah;

use App\Models\Dokumen;
use App\Models\User;
use App\Models\VersiDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DocumentStorageService
{
    public function storePdfOnDokumen(UploadedFile $file, Dokumen $dokumen, User $user): VersiDokumen
    {
        $path = $file->store("tatanaskah/{$dokumen->id}", 'local');

        if ($path === false || $path === '') {
            throw new \RuntimeException('Gagal menyimpan file PDF.');
        }

        $absolute = Storage::disk('local')->path($path);
        $hash = hash_file('sha256', $absolute) ?: '';
        $sizeKb = (int) ceil(($file->getSize() ?: filesize($absolute)) / 1024);

        $versi = VersiDokumen::query()->create([
            'dokumen_id' => $dokumen->id,
            'nomor_versi' => 1,
            'nomor_revisi' => $dokumen->kode_jenis === 'SPO' ? '00' : null,
            'file_asli' => $path,
            'hash_sha256' => $hash,
            'ukuran_kb' => $sizeKb,
            'jumlah_halaman' => 0,
            'dibuat_oleh' => $user->id,
        ]);

        $dokumen->update(['versi_saat_ini_id' => $versi->id]);

        return $versi;
    }

    public function signedUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        if (! Storage::disk('local')->exists($relativePath)) {
            return null;
        }

        return Storage::disk('local')->temporaryUrl(
            $relativePath,
            now()->addMinutes(30),
        );
    }

    public function safeOriginalFilename(UploadedFile $file): string
    {
        $name = str_replace(["\0", "\r", "\n"], '', $file->getClientOriginalName());

        return Str::limit(basename($name), 255, '');
    }
}
