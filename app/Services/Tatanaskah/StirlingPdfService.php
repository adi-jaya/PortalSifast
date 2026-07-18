<?php

namespace App\Services\Tatanaskah;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class StirlingPdfService
{
    public function isConfigured(): bool
    {
        return filled(config('services.stirling_pdf.url'))
            && filled(config('services.stirling_pdf.api_key'));
    }

    public function isCertSigningConfigured(): bool
    {
        if (! $this->isConfigured() || ! (bool) config('services.stirling_pdf.signature.enable_cert_sign', false)) {
            return false;
        }

        $certType = (string) config('services.stirling_pdf.cert.type', 'custom');

        if ($certType === 'server') {
            return true;
        }

        $p12Path = (string) config('services.stirling_pdf.cert.p12_path');

        return $p12Path !== '' && is_file($p12Path) && filled(config('services.stirling_pdf.cert.password'));
    }

    public function hasVisualSignatureImage(): bool
    {
        $path = (string) config('services.stirling_pdf.signature.image_path');

        return $path !== '' && is_file($path);
    }

    public function isHealthy(): bool
    {
        if (! filled(config('services.stirling_pdf.url'))) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->get(rtrim((string) config('services.stirling_pdf.url'), '/').'/api/v1/info/status');

            return $response->successful()
                && ($response->json('status') === 'UP' || $response->json('status') === 'up');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{pageCount: int, sizeKb: int}
     */
    public function getPdfInfo(string $absolutePath): array
    {
        $fallback = static function () use ($absolutePath): array {
            $size = filesize($absolutePath) ?: 0;

            return ['pageCount' => 0, 'sizeKb' => (int) ceil($size / 1024)];
        };

        if (! $this->isConfigured()) {
            return $fallback();
        }

        try {
            $response = $this->client()
                ->attach('fileInput', file_get_contents($absolutePath), basename($absolutePath))
                ->post('/api/v1/security/get-info-on-pdf');

            if (! $response->successful()) {
                return $fallback();
            }

            $data = $response->json();

            return [
                'pageCount' => (int) ($data['numberOfPages'] ?? $data['pageCount'] ?? $data['pages'] ?? 0),
                'sizeKb' => (int) ceil((filesize($absolutePath) ?: 0) / 1024),
            ];
        } catch (\Throwable) {
            return $fallback();
        }
    }

    public function addTextStamp(string $inputAbsolutePath, string $stampText, array $options = []): string
    {
        if (! $this->isConfigured()) {
            return $inputAbsolutePath;
        }

        try {
            $response = $this->client()
                ->attach('fileInput', file_get_contents($inputAbsolutePath), basename($inputAbsolutePath))
                ->post('/api/v1/misc/add-stamp', array_merge([
                    'stampType' => 'text',
                    'stampText' => $stampText,
                    'pageNumbers' => '1',
                    'xPercent' => 60,
                    'yPercent' => 15,
                    'fontSize' => 11,
                    'opacity' => 1,
                    'rotation' => 0,
                ], $options));

            if (! $response->successful()) {
                return $inputAbsolutePath;
            }

            return $this->storeResponsePdf($response->body(), $inputAbsolutePath, 'bernomor');
        } catch (\Throwable) {
            return $inputAbsolutePath;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function addImageStamp(string $inputAbsolutePath, array $options = []): string
    {
        $imagePath = (string) config('services.stirling_pdf.signature.image_path');

        if (! $this->isConfigured() || ! is_file($imagePath)) {
            return $inputAbsolutePath;
        }

        try {
            $response = $this->client()
                ->attach('fileInput', file_get_contents($inputAbsolutePath), basename($inputAbsolutePath))
                ->attach('stampImage', file_get_contents($imagePath), basename($imagePath))
                ->post('/api/v1/misc/add-stamp', array_merge([
                    'stampType' => 'image',
                    'pageNumbers' => '1',
                    'xPercent' => 65,
                    'yPercent' => 82,
                    'opacity' => 1,
                    'rotation' => 0,
                ], $options));

            if (! $response->successful()) {
                Log::warning('Stirling add-stamp image gagal', ['status' => $response->status()]);

                return $inputAbsolutePath;
            }

            return $this->storeResponsePdf($response->body(), $inputAbsolutePath, 'ttd-visual');
        } catch (\Throwable $e) {
            Log::warning('Stirling add-stamp image exception', ['message' => $e->getMessage()]);

            return $inputAbsolutePath;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function certSign(string $inputAbsolutePath, array $options = []): ?string
    {
        if (! $this->isCertSigningConfigured()) {
            return null;
        }

        try {
            $client = $this->client()
                ->attach('fileInput', file_get_contents($inputAbsolutePath), basename($inputAbsolutePath));

            $certType = (string) config('services.stirling_pdf.cert.type', 'custom');
            $payload = [
                'certType' => $certType === 'server' ? 'server' : 'PKCS12',
                'reason' => (string) ($options['reason'] ?? 'Disetujui Portal SIFAST'),
                'location' => (string) ($options['location'] ?? "RSU 'Aisyiyah Siti Fatimah"),
                'showSignature' => ($options['showSignature'] ?? true) ? 'true' : 'false',
                'pageNumber' => (string) ($options['pageNumber'] ?? 1),
            ];

            if ($certType !== 'server') {
                $p12Path = (string) config('services.stirling_pdf.cert.p12_path');
                $client->attach('p12File', file_get_contents($p12Path), basename($p12Path));
                $payload['password'] = (string) config('services.stirling_pdf.cert.password');
            }

            $response = $client->post('/api/v1/security/cert-sign', $payload);

            if (! $response->successful()) {
                Log::error('Stirling cert-sign gagal', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $this->storeResponsePdf($response->body(), $inputAbsolutePath, 'cert-signed');
        } catch (\Throwable $e) {
            Log::error('Stirling cert-sign exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function timestampPdf(string $inputAbsolutePath): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()
                ->attach('fileInput', file_get_contents($inputAbsolutePath), basename($inputAbsolutePath))
                ->post('/api/v1/security/timestamp-pdf');

            if (! $response->successful()) {
                Log::warning('Stirling timestamp-pdf gagal', ['status' => $response->status()]);

                return null;
            }

            return $this->storeResponsePdf($response->body(), $inputAbsolutePath, 'timestamped');
        } catch (\Throwable $e) {
            Log::warning('Stirling timestamp-pdf exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{valid: bool|null, message: string|null, raw: mixed}
     */
    public function validateSignature(string $inputAbsolutePath): array
    {
        if (! $this->isConfigured()) {
            return ['valid' => null, 'message' => null, 'raw' => null];
        }

        try {
            $response = $this->client()
                ->attach('fileInput', file_get_contents($inputAbsolutePath), basename($inputAbsolutePath))
                ->post('/api/v1/security/validate-signature');

            if (! $response->successful()) {
                return ['valid' => null, 'message' => 'Validasi gagal dipanggil', 'raw' => $response->json()];
            }

            $data = $response->json();
            $valid = $data['valid'] ?? $data['isValid'] ?? $data['signatureValid'] ?? null;

            return [
                'valid' => is_bool($valid) ? $valid : null,
                'message' => is_string($data['message'] ?? null) ? $data['message'] : null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return ['valid' => null, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'X-API-KEY' => (string) config('services.stirling_pdf.api_key'),
        ])->timeout((int) config('services.stirling_pdf.timeout', 120))
            ->baseUrl(rtrim((string) config('services.stirling_pdf.url'), '/'));
    }

    private function storeResponsePdf(string $body, string $sourcePath, string $suffix): string
    {
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $relative = 'tatanaskah/processed/'.$baseName."-{$suffix}-".now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($relative, $body);

        return $relative;
    }
}
