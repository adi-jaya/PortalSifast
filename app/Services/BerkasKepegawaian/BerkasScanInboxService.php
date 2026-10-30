<?php

namespace App\Services\BerkasKepegawaian;

use App\Models\BerkasScanInbox;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BerkasScanInboxService
{
    public function __construct(private BerkasKepegawaianService $berkasService) {}

    /**
     * @param  array{
     *     suggested_kode?: string|null,
     *     suggested_label?: string|null,
     *     confidence?: float|int|string|null,
     *     ocr_excerpt?: string|null,
     *     ocr_failed?: mixed,
     *     agent_label?: string|null,
     *     original_filename?: string|null
     * }  $meta
     */
    public function storeFromAgent(UploadedFile $file, array $meta): BerkasScanInbox
    {
        $original = (string) ($meta['original_filename'] ?? $file->getClientOriginalName());
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'pdf'));
        $storedName = Str::uuid()->toString().'.'.$extension;
        $storedPath = $file->storeAs('berkas-scan-inbox', $storedName, 'local');

        if (! is_string($storedPath) || $storedPath === '') {
            throw new RuntimeException('Gagal menyimpan file scan ke storage.');
        }

        return BerkasScanInbox::query()->create([
            'original_filename' => $original !== '' ? $original : $storedName,
            'stored_path' => $storedPath,
            'suggested_kode' => $this->nullableString($meta['suggested_kode'] ?? null),
            'suggested_label' => $this->nullableString($meta['suggested_label'] ?? null),
            'confidence' => $this->nullableConfidence($meta['confidence'] ?? null),
            'ocr_excerpt' => $this->nullableString($meta['ocr_excerpt'] ?? null),
            'ocr_failed' => $this->toBool($meta['ocr_failed'] ?? false),
            'status' => BerkasScanInbox::STATUS_PENDING,
            'agent_label' => $this->nullableString($meta['agent_label'] ?? null),
        ]);
    }

    public function confirm(BerkasScanInbox $item, string $nik, string $kodeBerkas, string $tglUploud, User $user): BerkasScanInbox
    {
        if (! $item->isActionable()) {
            throw new InvalidArgumentException('Item inbox tidak dapat dikonfirmasi.');
        }

        $this->findAktifPegawaiOrFail($nik);

        if (! Storage::disk('local')->exists($item->stored_path)) {
            $item->update([
                'status' => BerkasScanInbox::STATUS_FAILED,
                'error_message' => 'File sementara tidak ditemukan di storage.',
            ]);

            throw new RuntimeException('File sementara tidak ditemukan di storage.');
        }

        $absolute = Storage::disk('local')->path($item->stored_path);
        $upload = new UploadedFile(
            $absolute,
            $item->original_filename,
            mime_content_type($absolute) ?: 'application/octet-stream',
            null,
            true,
        );

        try {
            $this->berkasService->upload($nik, $kodeBerkas, $upload, $tglUploud);
        } catch (InvalidArgumentException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'sudah ada')) {
                $item->update([
                    'status' => BerkasScanInbox::STATUS_FAILED,
                    'nik' => $nik,
                    'confirmed_kode' => $kodeBerkas,
                    'confirmed_by' => $user->id,
                    'error_message' => $e->getMessage(),
                ]);

                throw $e;
            }

            try {
                $this->berkasService->replace($nik, $kodeBerkas, $upload, $tglUploud);
            } catch (Throwable $replaceError) {
                $item->update([
                    'status' => BerkasScanInbox::STATUS_FAILED,
                    'nik' => $nik,
                    'confirmed_kode' => $kodeBerkas,
                    'confirmed_by' => $user->id,
                    'error_message' => $replaceError->getMessage(),
                ]);

                throw $replaceError instanceof RuntimeException || $replaceError instanceof InvalidArgumentException
                    ? $replaceError
                    : new RuntimeException($replaceError->getMessage(), 0, $replaceError);
            }
        } catch (Throwable $e) {
            $item->update([
                'status' => BerkasScanInbox::STATUS_FAILED,
                'nik' => $nik,
                'confirmed_kode' => $kodeBerkas,
                'confirmed_by' => $user->id,
                'error_message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException || $e instanceof InvalidArgumentException) {
                throw $e;
            }

            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        Storage::disk('local')->delete($item->stored_path);

        $item->update([
            'status' => BerkasScanInbox::STATUS_CONFIRMED,
            'nik' => $nik,
            'confirmed_kode' => $kodeBerkas,
            'confirmed_by' => $user->id,
            'error_message' => null,
            'stored_path' => '',
        ]);

        return $item->fresh();
    }

    public function reject(BerkasScanInbox $item, User $user): BerkasScanInbox
    {
        if (! $item->isActionable()) {
            throw new InvalidArgumentException('Item inbox tidak dapat ditolak.');
        }

        if ($item->stored_path !== '' && Storage::disk('local')->exists($item->stored_path)) {
            Storage::disk('local')->delete($item->stored_path);
        }

        $item->update([
            'status' => BerkasScanInbox::STATUS_REJECTED,
            'confirmed_by' => $user->id,
            'error_message' => null,
            'stored_path' => '',
        ]);

        return $item->fresh();
    }

    private function findAktifPegawaiOrFail(string $nik): Pegawai
    {
        return Pegawai::query()
            ->where('nik', $nik)
            ->where('stts_aktif', 'AKTIF')
            ->firstOrFail();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableConfidence(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, min(1, (float) $value));
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
