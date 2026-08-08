<?php

namespace App\Services\Tatanaskah;

use App\Models\Dokumen;
use App\Models\User;
use App\Models\VersiDokumen;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class DocumentSigningService
{
    public function __construct(
        private StirlingPdfService $stirlingService,
    ) {}

    public function isCertSigningEnabled(): bool
    {
        return $this->stirlingService->isCertSigningConfigured();
    }

    /**
     * @return array{cert: bool, image: bool, timestamp: bool}
     */
    public function capabilities(): array
    {
        return [
            'cert' => $this->stirlingService->isCertSigningConfigured(),
            'image' => filled(config('services.stirling_pdf.signature.image_path'))
                && is_file((string) config('services.stirling_pdf.signature.image_path')),
            'timestamp' => (bool) config('services.stirling_pdf.signature.enable_timestamp', true)
                && $this->stirlingService->isConfigured(),
        ];
    }

    public function signOnApproval(Dokumen $dokumen, User $actor): void
    {
        $dokumen->loadMissing(['versiSaatIni', 'jenis']);

        $versi = $dokumen->versiSaatIni;
        if ($versi === null) {
            throw new InvalidArgumentException('Dokumen tidak memiliki file PDF untuk ditandatangani.');
        }

        $sourceRelative = $versi->file_bernomor ?? $versi->file_asli;
        if ($sourceRelative === null || ! Storage::disk('local')->exists($sourceRelative)) {
            throw new InvalidArgumentException('File PDF sumber tidak ditemukan.');
        }

        $absolute = Storage::disk('local')->path($sourceRelative);
        $workingAbsolute = $absolute;

        if ($this->stirlingService->hasVisualSignatureImage()) {
            $stamped = $this->stirlingService->addImageStamp($workingAbsolute, [
                'pageNumbers' => $this->resolveSignPage($versi),
                'xPercent' => (int) config('services.stirling_pdf.signature.x_percent', 65),
                'yPercent' => (int) config('services.stirling_pdf.signature.y_percent', 82),
            ]);
            $workingAbsolute = $this->toAbsolute($stamped, $workingAbsolute);
        }

        if ($this->stirlingService->isCertSigningConfigured()) {
            $signed = $this->stirlingService->certSign($workingAbsolute, [
                'reason' => trim(sprintf(
                    '%s — %s',
                    (string) config('services.stirling_pdf.signature.reason', 'Disetujui Portal SIFAST'),
                    $dokumen->penandatangan_nama ?? $actor->name,
                )),
                'location' => (string) config('services.stirling_pdf.signature.location', "RSU 'Aisyiyah Siti Fatimah"),
                'pageNumber' => $this->resolveSignPageNumber($versi),
                'showSignature' => (bool) config('services.stirling_pdf.signature.show_signature', true),
            ]);

            if ($signed === null) {
                throw new InvalidArgumentException('Gagal menandatangani PDF dengan sertifikat digital. Periksa konfigurasi Stirling-PDF.');
            }

            $workingAbsolute = $this->toAbsolute($signed, $workingAbsolute);

            if ((bool) config('services.stirling_pdf.signature.enable_timestamp', true)) {
                $timestamped = $this->stirlingService->timestampPdf($workingAbsolute);
                if ($timestamped !== null) {
                    $workingAbsolute = $this->toAbsolute($timestamped, $workingAbsolute);
                }
            }

            $validation = $this->stirlingService->validateSignature($workingAbsolute);
            Log::info('TTE validasi dokumen', [
                'dokumen_id' => $dokumen->id,
                'valid' => $validation['valid'] ?? null,
                'message' => $validation['message'] ?? null,
            ]);
        }

        $finalRelative = $this->storeFinalCopy($workingAbsolute, $dokumen, $versi);

        $hash = hash_file('sha256', $workingAbsolute) ?: '';
        $sizeKb = (int) ceil((filesize($workingAbsolute) ?: 0) / 1024);

        $versi->update([
            'file_final' => $finalRelative,
            'hash_sha256' => $hash,
            'ukuran_kb' => $sizeKb,
        ]);
    }

    private function resolveSignPage(VersiDokumen $versi): string
    {
        $page = config('services.stirling_pdf.signature.page');

        if ($page === 'last' && $versi->jumlah_halaman > 0) {
            return (string) $versi->jumlah_halaman;
        }

        if (is_numeric($page)) {
            return (string) $page;
        }

        return '1';
    }

    private function resolveSignPageNumber(VersiDokumen $versi): int
    {
        return max(1, (int) $this->resolveSignPage($versi));
    }

    private function toAbsolute(string $path, string $fallbackAbsolute): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        return $fallbackAbsolute;
    }

    private function storeFinalCopy(string $absolute, Dokumen $dokumen, VersiDokumen $versi): string
    {
        if (! str_starts_with($absolute, '/')) {
            return $absolute;
        }

        $relative = "tatanaskah/{$dokumen->id}/final-v{$versi->nomor_versi}-".now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($relative, file_get_contents($absolute) ?: '');

        return $relative;
    }
}
