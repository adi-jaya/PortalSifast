<?php

namespace App\Console\Commands;

use App\Models\AsetAspakAlat;
use App\Models\AsetBarang;
use App\Models\AsetDistributor;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetNonAlkes;
use App\Models\AsetProdusen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAsetMasterCommand extends Command
{
    protected $signature = 'aset:import-master {file : Path CSV} {--tipe= : kategori|jenis|merk|produsen|distributor|barang|aspak|non_alkes}';

    protected $description = 'Import master aset portal dari CSV';

    public function handle(): int
    {
        $tipe = (string) $this->option('tipe');
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);
        if ($rows === []) {
            $this->error('CSV kosong atau tidak valid.');

            return self::FAILURE;
        }

        $count = DB::transaction(function () use ($tipe, $rows) {
            return match ($tipe) {
                'kategori' => $this->importKategori($rows),
                'jenis' => $this->importJenis($rows),
                'merk' => $this->importMerk($rows),
                'produsen' => $this->importProdusen($rows),
                'distributor' => $this->importDistributor($rows),
                'barang' => $this->importBarang($rows),
                'aspak' => $this->importAspak($rows),
                'non_alkes' => $this->importNonAlkes($rows),
                default => throw new \InvalidArgumentException('Tipe tidak dikenal. Gunakan: kategori|jenis|merk|produsen|distributor|barang|aspak|non_alkes'),
            };
        });

        $this->info("Import {$tipe} selesai: {$count} baris.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
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

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && ($data[0] === null || $data[0] === '')) {
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
     * @param  array<int, array<string, string>>  $rows
     */
    private function importKategori(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetKategori::query()->updateOrCreate(
                ['kode_kategori' => $row['kode_kategori']],
                ['nama_kategori' => $row['nama_kategori']],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importJenis(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetJenis::query()->updateOrCreate(
                ['kode_jenis' => $row['kode_jenis']],
                ['nama_jenis' => $row['nama_jenis']],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importMerk(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetMerk::query()->updateOrCreate(
                ['kode_merk' => $row['kode_merk']],
                ['nama_merk' => $row['nama_merk']],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importProdusen(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetProdusen::query()->updateOrCreate(
                ['kode_produsen' => $row['kode_produsen']],
                ['nama_produsen' => $row['nama_produsen']],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importDistributor(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetDistributor::query()->updateOrCreate(
                ['kode_distributor' => $row['kode_distributor']],
                ['nama_distributor' => $row['nama_distributor']],
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importBarang(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            AsetBarang::query()->updateOrCreate(
                ['kode_barang' => $row['kode_barang']],
                array_filter([
                    'nama_barang' => $row['nama_barang'],
                    'aset_kategori_id' => $this->resolveId(AsetKategori::class, 'kode_kategori', $row['kode_kategori'] ?? null),
                    'aset_jenis_id' => $this->resolveId(AsetJenis::class, 'kode_jenis', $row['kode_jenis'] ?? null),
                    'aset_merk_id' => $this->resolveId(AsetMerk::class, 'kode_merk', $row['kode_merk'] ?? null),
                    'aset_produsen_id' => $this->resolveId(AsetProdusen::class, 'kode_produsen', $row['kode_produsen'] ?? null),
                    'aset_aspak_alat_id' => $this->resolveAspakId($row['kode_aspak'] ?? null),
                    'kelas_aset' => $row['kelas_aset'] ?? null,
                    'tahun_produksi' => filled($row['tahun_produksi'] ?? '') ? (int) $row['tahun_produksi'] : null,
                ], fn ($v) => $v !== null),
            );
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importAspak(array $rows): int
    {
        $n = 0;

        // Pass 1: upsert without parent FK so children can reference parents later.
        foreach ($rows as $row) {
            AsetAspakAlat::query()->updateOrCreate(
                ['id_alat_aspak' => $row['id_alat_aspak']],
                array_filter([
                    'nama_alat' => $row['nama_alat'],
                    'kode' => $row['kode'] ?? null,
                    'alat_path' => $row['alat_path'] ?? null,
                    'sinonim' => $row['sinonim'] ?? null,
                    'wajib_kalibrasi' => ($row['wajib_kalibrasi'] ?? '') === '1',
                    'durasi_kalibrasi_hari' => filled($row['durasi_kalibrasi_hari'] ?? '')
                        ? (int) $row['durasi_kalibrasi_hari']
                        : 700,
                ], fn ($v) => $v !== null),
            );
            $n++;
        }

        // Pass 2: resolve parent_id from parent_id_alat_aspak (ASPAK external id).
        foreach ($rows as $row) {
            $parentKey = $row['parent_id_alat_aspak'] ?? $row['parent_id'] ?? null;
            if (! filled($parentKey)) {
                continue;
            }

            $parentId = AsetAspakAlat::query()->where('id_alat_aspak', $parentKey)->value('id');
            if ($parentId === null) {
                continue;
            }

            AsetAspakAlat::query()
                ->where('id_alat_aspak', $row['id_alat_aspak'])
                ->update(['parent_id' => $parentId]);
        }

        return $n;
    }

    /**
     * CSV kolom (kompatibel dump m_non_alkes):
     * id_alat,alat_name,alat_code,parent_id,alat_ket,level,sinonim,kode,deleted,alat_path
     *
     * @param  array<int, array<string, string>>  $rows
     */
    private function importNonAlkes(array $rows): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $idAlat = $row['id_alat'] ?? '';
            if ($idAlat === '') {
                continue;
            }

            $kode = $row['kode'] ?? $row['alat_code'] ?? null;
            $deleted = in_array($row['deleted'] ?? '0', ['1', 'true', 'yes'], true);

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

    /**
     * @param  class-string  $model
     */
    private function resolveId(string $model, string $kodeColumn, ?string $kode): ?int
    {
        if (! filled($kode)) {
            return null;
        }

        return $model::query()->where($kodeColumn, $kode)->value('id');
    }

    private function resolveAspakId(?string $kode): ?int
    {
        if (! filled($kode)) {
            return null;
        }

        return AsetAspakAlat::query()
            ->where('kode', $kode)
            ->orWhere('id_alat_aspak', $kode)
            ->value('id');
    }
}
