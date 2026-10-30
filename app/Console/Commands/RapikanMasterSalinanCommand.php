<?php

namespace App\Console\Commands;

use App\Services\Inventaris\RapikanMasterSalinan;
use Illuminate\Console\Command;

class RapikanMasterSalinanCommand extends Command
{
    protected $signature = 'aset:rapikan-master-salinan {--apply : Terapkan perubahan (tanpa opsi ini hanya pratinjau)}';

    protected $description = 'Pulihkan tautan katalog master salinan dan gabungkan master barang yang spesifikasinya identik';

    public function handle(RapikanMasterSalinan $rapikan): int
    {
        $terapkan = (bool) $this->option('apply');
        $laporan = $rapikan->jalankan($terapkan);

        if ($laporan['keluarga'] === []) {
            $this->info('Tidak ada master salinan yang perlu dirapikan.');

            return self::SUCCESS;
        }

        $this->table(
            ['Kode dasar', 'Nama barang', 'Tautan dipulihkan', 'Master digabung', 'Unit dipindah'],
            array_map(fn (array $baris) => [
                $baris['kode_dasar'],
                $baris['nama_barang'],
                $baris['tautan_dipulihkan'],
                $baris['master_digabung'],
                $baris['unit_dipindah'],
            ], $laporan['keluarga']),
        );

        $ringkasan = "Tautan katalog dipulihkan: {$laporan['tautan_dipulihkan']}, master digabung: {$laporan['master_digabung']}, unit dipindah: {$laporan['unit_dipindah']}.";

        if (! $terapkan) {
            $this->warn('PRATINJAU - belum ada data yang diubah. '.$ringkasan);
            $this->line('Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->info('Selesai. '.$ringkasan);

        return self::SUCCESS;
    }
}
