<?php

namespace App\Services\BerkasKepegawaian;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WebappsBerkasPegawaiClient
{
    /**
     * @return array{success: bool, filename: string, target?: string, path?: string, message?: string}
     */
    public function upload(UploadedFile $file, string $filename): array
    {
        $url = $this->receiverUrl();

        try {
            $response = Http::timeout($this->timeout())
                ->withHeaders($this->headers())
                ->attach('dokumen', file_get_contents($file->getRealPath()), $filename)
                ->post($url, [
                    'target' => 'berkaspegawai',
                    'filename' => $filename,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gagal menghubungi receiver webapps: '.$e->getMessage(), 0, $e);
        }

        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException((string) ($json['message'] ?? 'Upload ke webapps gagal (HTTP '.$response->status().')'));
        }

        return $json;
    }

    /**
     * @return array{success: bool, filename?: string, message?: string}
     */
    public function delete(string $berkasPathOrFilename): array
    {
        $filename = basename(str_replace('\\', '/', $berkasPathOrFilename));
        $url = $this->receiverUrl();

        try {
            $response = Http::timeout($this->timeout())
                ->withHeaders($this->headers())
                ->asForm()
                ->post($url, [
                    'action' => 'delete',
                    'target' => 'berkaspegawai',
                    'filename' => $filename,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gagal menghubungi receiver webapps: '.$e->getMessage(), 0, $e);
        }

        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['success'] ?? false)) {
            throw new RuntimeException((string) ($json['message'] ?? 'Hapus file di webapps gagal (HTTP '.$response->status().')'));
        }

        return $json;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $token = (string) config('services.berkas_pegawai.receiver_token', '');

        return $token !== '' ? ['X-Webapps-Token' => $token] : [];
    }

    private function receiverUrl(): string
    {
        $url = (string) config('services.berkas_pegawai.receiver_url', '');
        if ($url === '') {
            throw new RuntimeException('WEBAPPS_BERKAS_PEGAWAI_RECEIVER_URL belum dikonfigurasi.');
        }

        return $url;
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.berkas_pegawai.timeout', 30));
    }
}
