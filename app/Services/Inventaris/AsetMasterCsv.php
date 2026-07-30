<?php

namespace App\Services\Inventaris;

use App\Models\AsetAspakAlat;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsetMasterCsv
{
    public const TIPES = ['ruang', 'aspak', 'non_alkes'];

    /**
     * @return list<string>
     */
    public function headers(string $tipe): array
    {
        return match ($tipe) {
            'ruang' => ['kode_ruang', 'nama_ruang'],
            'aspak' => [
                'id_alat_aspak',
                'nama_alat',
                'kode',
                'alat_code',
                'parent_id_alat_aspak',
                'alat_path',
                'alat_ket',
                'sinonim',
                'wajib_kalibrasi',
                'durasi_kalibrasi_hari',
            ],
            'non_alkes' => [
                'id_alat',
                'alat_name',
                'alat_code',
                'parent_id',
                'alat_ket',
                'level',
                'sinonim',
                'kode',
                'deleted',
                'alat_path',
                'kode_kategori',
            ],
            default => throw new \InvalidArgumentException("Tipe tidak dikenal: {$tipe}"),
        };
    }

    /**
     * @return list<list<string>>
     */
    public function exampleRows(string $tipe): array
    {
        return match ($tipe) {
            'ruang' => [
                ['IGD01', 'IGD'],
                ['POL01', 'Poli Umum'],
            ],
            'aspak' => [
                ['9001', 'Infus Pump', 'INFUS01', 'INFUS01', '', '', '', '', '1', '365'],
            ],
            'non_alkes' => [
                ['1002002', 'Laptop', '10.02.002', '0', '', '3', '', '10.02.002', '0', '', ''],
            ],
            default => [],
        };
    }

    public function templateFilename(string $tipe): string
    {
        return "template-import-{$tipe}.csv";
    }

    public function exportFilename(string $tipe): string
    {
        return 'export-'.$tipe.'-'.now()->format('Ymd-His').'.csv';
    }

    public function templateDownload(string $tipe): StreamedResponse
    {
        $this->assertTipe($tipe);
        $headers = $this->headers($tipe);
        $examples = $this->exampleRows($tipe);

        return response()->streamDownload(function () use ($headers, $examples) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($examples as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $this->templateFilename($tipe), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportDownload(string $tipe): StreamedResponse
    {
        $this->assertTipe($tipe);

        return response()->streamDownload(function () use ($tipe) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->headers($tipe));

            match ($tipe) {
                'ruang' => $this->writeRuangExport($handle),
                'aspak' => $this->writeAspakExport($handle),
                'non_alkes' => $this->writeNonAlkesExport($handle),
            };

            fclose($handle);
        }, $this->exportFilename($tipe), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return int Number of rows processed
     */
    public function import(string $tipe, UploadedFile|string $file): int
    {
        $this->assertTipe($tipe);
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if ($path === false || ! is_file($path)) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak dapat dibaca.',
            ]);
        }

        $rows = $this->readCsv($path);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'CSV kosong atau tidak valid.',
            ]);
        }

        return DB::transaction(fn () => match ($tipe) {
            'ruang' => $this->importRuang($rows),
            'aspak' => $this->importAspak($rows),
            'non_alkes' => $this->importNonAlkes($rows),
        });
    }

    /**
     * @return list<array<string, string>>
     */
    public function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && ($data[0] === null || trim((string) $data[0]) === '')) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim((string) ($data[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    public function importRuang(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            $kode = $row['kode_ruang'] ?? '';
            $nama = $row['nama_ruang'] ?? '';
            if ($kode === '' || $nama === '') {
                continue;
            }
            AsetRuang::query()->updateOrCreate(
                ['kode_ruang' => $kode],
                ['nama_ruang' => $nama],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    public function importAspak(array $rows): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $idAspak = $row['id_alat_aspak'] ?? $row['id_alat'] ?? '';
            if ($idAspak === '') {
                continue;
            }

            $nama = $row['nama_alat'] ?? $row['alat_name'] ?? '';
            if ($nama === '') {
                continue;
            }

            $kode = $row['kode'] ?? $row['alat_code'] ?? $row['code_aspak'] ?? null;
            $wajibRaw = $row['wajib_kalibrasi'] ?? $row['wajibkalibrasi'] ?? '0';
            $durasiRaw = $row['durasi_kalibrasi_hari'] ?? $row['durasi'] ?? '';

            AsetAspakAlat::query()->updateOrCreate(
                ['id_alat_aspak' => $idAspak],
                array_filter([
                    'nama_alat' => $nama,
                    'kode' => $kode,
                    'alat_code' => $row['alat_code'] ?? $row['code_aspak'] ?? $kode,
                    'alat_path' => $row['alat_path'] ?? null,
                    'alat_ket' => $row['alat_ket'] ?? null,
                    'sinonim' => $row['sinonim'] ?? null,
                    'wajib_kalibrasi' => in_array($wajibRaw, ['1', 'true', 'yes'], true),
                    'durasi_kalibrasi_hari' => filled($durasiRaw) ? (int) $durasiRaw : 700,
                ], fn ($v) => $v !== null),
            );
            $n++;
        }

        foreach ($rows as $row) {
            $idAspak = $row['id_alat_aspak'] ?? $row['id_alat'] ?? '';
            if ($idAspak === '') {
                continue;
            }

            $parentKey = $row['parent_id_alat_aspak'] ?? $row['parent_id'] ?? '';
            if ($parentKey === '' || $parentKey === '0') {
                AsetAspakAlat::query()->where('id_alat_aspak', $idAspak)->update(['parent_id' => null]);

                continue;
            }

            $parentId = AsetAspakAlat::query()->where('id_alat_aspak', $parentKey)->value('id');
            if ($parentId === null) {
                continue;
            }

            AsetAspakAlat::query()
                ->where('id_alat_aspak', $idAspak)
                ->update(['parent_id' => $parentId]);
        }

        return $n;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    public function importNonAlkes(array $rows): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $idAlat = $row['id_alat'] ?? '';
            if ($idAlat === '') {
                continue;
            }

            $kode = $row['kode'] ?? $row['alat_code'] ?? null;
            $deleted = in_array($row['deleted'] ?? '0', ['1', 'true', 'yes'], true);
            $kategoriId = null;
            if (filled($row['kode_kategori'] ?? null)) {
                $kategoriId = AsetKategori::query()->where('kode_kategori', $row['kode_kategori'])->value('id');
            }

            AsetNonAlkes::query()->updateOrCreate(
                ['id_alat' => $idAlat],
                array_filter([
                    'nama_alat' => $row['alat_name'] ?? $row['nama_alat'] ?? '',
                    'alat_code' => $row['alat_code'] ?? $kode,
                    'kode' => $kode,
                    'level' => filled($row['level'] ?? '') ? (int) $row['level'] : 1,
                    'alat_path' => $row['alat_path'] ?? null,
                    'alat_ket' => $row['alat_ket'] ?? null,
                    'sinonim' => $row['sinonim'] ?? null,
                    'deleted' => $deleted,
                    'aset_kategori_id' => $kategoriId,
                ], fn ($v) => $v !== null),
            );
            $n++;
        }

        foreach ($rows as $row) {
            $idAlat = $row['id_alat'] ?? '';
            if ($idAlat === '') {
                continue;
            }

            $parentKey = $row['parent_id'] ?? '';
            if ($parentKey === '' || $parentKey === '0') {
                AsetNonAlkes::query()->where('id_alat', $idAlat)->update(['parent_id' => null]);

                continue;
            }

            $parentId = AsetNonAlkes::query()->where('id_alat', $parentKey)->value('id');
            if ($parentId === null) {
                continue;
            }

            AsetNonAlkes::query()
                ->where('id_alat', $idAlat)
                ->update(['parent_id' => $parentId]);
        }

        return $n;
    }

    private function assertTipe(string $tipe): void
    {
        if (! in_array($tipe, self::TIPES, true)) {
            abort(404);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writeRuangExport($handle): void
    {
        AsetRuang::query()
            ->orderBy('kode_ruang')
            ->cursor()
            ->each(function (AsetRuang $ruang) use ($handle) {
                fputcsv($handle, [$ruang->kode_ruang, $ruang->nama_ruang]);
            });
    }

    /**
     * @param  resource  $handle
     */
    private function writeAspakExport($handle): void
    {
        AsetAspakAlat::query()
            ->with('parent:id,id_alat_aspak')
            ->orderBy('id')
            ->cursor()
            ->each(function (AsetAspakAlat $item) use ($handle) {
                fputcsv($handle, [
                    $item->id_alat_aspak,
                    $item->nama_alat,
                    $item->kode,
                    $item->alat_code,
                    $item->parent?->id_alat_aspak ?? '',
                    $item->alat_path,
                    $item->alat_ket,
                    $item->sinonim,
                    $item->wajib_kalibrasi ? '1' : '0',
                    (string) ($item->durasi_kalibrasi_hari ?? ''),
                ]);
            });
    }

    /**
     * @param  resource  $handle
     */
    private function writeNonAlkesExport($handle): void
    {
        AsetNonAlkes::query()
            ->with(['parent:id,id_alat', 'kategori:id,kode_kategori'])
            ->orderBy('id')
            ->cursor()
            ->each(function (AsetNonAlkes $item) use ($handle) {
                fputcsv($handle, [
                    $item->id_alat,
                    $item->nama_alat,
                    $item->alat_code,
                    $item->parent?->id_alat ?? '0',
                    $item->alat_ket,
                    (string) ($item->level ?? ''),
                    $item->sinonim,
                    $item->kode,
                    $item->deleted ? '1' : '0',
                    $item->alat_path,
                    $item->kategori?->kode_kategori ?? '',
                ]);
            });
    }
}
