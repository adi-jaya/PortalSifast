<?php

namespace App\Console\Commands;

use App\Models\AsetAspakAlat;
use App\Models\AsetBarang;
use App\Models\AsetDistributor;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetProdusen;
use App\Services\Inventaris\AsetMasterCsv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAsetMasterCommand extends Command
{
    protected $signature = 'aset:import-master {file : Path CSV} {--tipe= : kategori|jenis|merk|produsen|distributor|barang|aspak|non_alkes|ruang}';

    protected $description = 'Import master aset portal dari CSV';

    public function handle(AsetMasterCsv $masterCsv): int
    {
        $tipe = (string) $this->option('tipe');
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        if (in_array($tipe, AsetMasterCsv::TIPES, true)) {
            try {
                $count = $masterCsv->import($tipe, $path);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->error(collect($e->errors())->flatten()->first() ?? 'Import gagal.');

                return self::FAILURE;
            }

            $this->info("Import {$tipe} selesai: {$count} baris.");

            return self::SUCCESS;
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
                default => throw new \InvalidArgumentException('Tipe tidak dikenal. Gunakan: kategori|jenis|merk|produsen|distributor|barang|aspak|non_alkes|ruang'),
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
            $payload = ['nama_jenis' => $row['nama_jenis']];

            if (array_key_exists('kode_merk', $row) && filled($row['kode_merk'])) {
                $payload['aset_merk_id'] = AsetMerk::query()
                    ->where('kode_merk', $row['kode_merk'])
                    ->value('id');
            }

            AsetJenis::query()->updateOrCreate(
                ['kode_jenis' => $row['kode_jenis']],
                $payload,
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
