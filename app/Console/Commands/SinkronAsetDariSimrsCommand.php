<?php

namespace App\Console\Commands;

use App\Services\Inventaris\SinkronAsetDariSimrs;
use Illuminate\Console\Command;

class SinkronAsetDariSimrsCommand extends Command
{
    protected $signature = 'aset:sinkron-dari-simrs {--dry-run : Hanya preview tanpa menulis} {--apply : Terapkan sinkron ke database utama}';

    protected $description = 'Sinkron inventaris SIMRS (read-only) ke tabel aset portal';

    public function handle(SinkronAsetDariSimrs $sinkron): int
    {
        if ($this->option('apply')) {
            $result = $sinkron->apply();
            $this->info('Sinkron selesai.');
        } else {
            $result = $sinkron->preview();
            $this->info('Preview sinkron (dry-run).');
        }

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Baru', $result['jumlah_baru']],
                ['Berubah', $result['jumlah_berubah']],
                ['Sama', $result['jumlah_sama']],
                ['Hilang', $result['jumlah_hilang']],
                ['Gagal', $result['jumlah_gagal']],
            ]
        );

        return self::SUCCESS;
    }
}
