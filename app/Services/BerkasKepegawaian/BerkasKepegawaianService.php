<?php

namespace App\Services\BerkasKepegawaian;

use App\Models\BerkasPegawai;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class BerkasKepegawaianService
{
    public function __construct(private WebappsBerkasPegawaiClient $client) {}

    public function upload(string $nik, string $kodeBerkas, UploadedFile $file, string $tglUploud): BerkasPegawai
    {
        if ($this->findRow($nik, $kodeBerkas) !== null) {
            throw new InvalidArgumentException('Berkas sudah ada. Gunakan replace untuk mengganti.');
        }

        $filename = $this->buildFilename($nik, $kodeBerkas, $file);
        $relativePath = $this->relativePath($filename);

        $this->client->upload($file, $filename);

        try {
            BerkasPegawai::query()->create([
                'nik' => $nik,
                'tgl_uploud' => $tglUploud,
                'kode_berkas' => $kodeBerkas,
                'berkas' => $relativePath,
            ]);
        } catch (Throwable $e) {
            $this->client->delete($filename);

            throw $e;
        }

        return $this->findRowOrFail($nik, $kodeBerkas);
    }

    public function replace(string $nik, string $kodeBerkas, UploadedFile $file, string $tglUploud): BerkasPegawai
    {
        $existing = $this->findRow($nik, $kodeBerkas);
        if ($existing === null) {
            throw new InvalidArgumentException('Berkas tidak ditemukan untuk diganti.');
        }

        $oldPath = (string) $existing->berkas;
        $oldBasename = basename(str_replace('\\', '/', $oldPath));

        $filename = $this->buildFilename($nik, $kodeBerkas, $file);
        $relativePath = $this->relativePath($filename);

        $this->client->upload($file, $filename);

        BerkasPegawai::query()
            ->where('nik', $nik)
            ->where('kode_berkas', $kodeBerkas)
            ->update([
                'tgl_uploud' => $tglUploud,
                'berkas' => $relativePath,
            ]);

        if ($oldBasename !== '' && $oldBasename !== $filename) {
            try {
                $this->client->delete($oldPath);
            } catch (Throwable $e) {
                Log::warning('Gagal menghapus berkas lama di webapps setelah replace.', [
                    'nik' => $nik,
                    'kode_berkas' => $kodeBerkas,
                    'old_path' => $oldPath,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->findRowOrFail($nik, $kodeBerkas);
    }

    public function delete(string $nik, string $kodeBerkas): void
    {
        $existing = $this->findRow($nik, $kodeBerkas);
        if ($existing === null) {
            throw new InvalidArgumentException('Berkas tidak ditemukan untuk dihapus.');
        }

        $path = (string) $existing->berkas;
        if ($path !== '') {
            $this->client->delete($path);
        }

        BerkasPegawai::query()
            ->where('nik', $nik)
            ->where('kode_berkas', $kodeBerkas)
            ->delete();
    }

    public function buildFilename(string $nik, string $kodeBerkas, UploadedFile $file): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: 'pdf'));
        $raw = sprintf('%s_%s_%s.%s', $nik, $kodeBerkas, now()->format('YmdHis'), $extension);

        return (string) preg_replace('/[^A-Za-z0-9._-]/', '_', $raw);
    }

    public function relativePath(string $filename): string
    {
        return 'pages/berkaspegawai/berkas/'.ltrim(basename(str_replace('\\', '/', $filename)), '/');
    }

    private function findRow(string $nik, string $kodeBerkas): ?BerkasPegawai
    {
        return BerkasPegawai::query()
            ->where('nik', $nik)
            ->where('kode_berkas', $kodeBerkas)
            ->first();
    }

    private function findRowOrFail(string $nik, string $kodeBerkas): BerkasPegawai
    {
        $row = $this->findRow($nik, $kodeBerkas);
        if ($row === null) {
            throw new InvalidArgumentException('Berkas tidak ditemukan setelah operasi.');
        }

        return $row;
    }
}
