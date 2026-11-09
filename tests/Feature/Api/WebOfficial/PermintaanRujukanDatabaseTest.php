<?php

namespace Tests\Feature\Api\WebOfficial;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermintaanRujukanDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection('dbsimrs')->getDriverName() === 'sqlite') {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_11_04_000001_create_permintaan_rujukan_faskes_table.php',
                '--database' => 'dbsimrs',
            ]);
        }
    }

    public function test_permintaan_rujukan_faskes_table_exists(): void
    {
        $this->assertTrue(
            Schema::connection('dbsimrs')->hasTable('permintaan_rujukan_faskes'),
            'Tabel permintaan_rujukan_faskes harus ada di koneksi dbsimrs'
        );
        $this->assertTrue(
            Schema::connection('dbsimrs')->hasColumns('permintaan_rujukan_faskes', [
                'no_rujukan',
                'tanggal_rujukan',
                'jam_rujukan',
                'nama_fktp',
                'nama_perujuk',
                'no_telp_perujuk',
                'nama_pasien',
                'no_ktp',
                'jk',
                'tgl_lahir',
                'umur',
                'no_telp_pasien',
                'diagnosa',
                'keluhan_utama',
                'tanda_vital_kesadaran',
                'tanda_vital_td',
                'tanda_vital_nadi',
                'tanda_vital_rr',
                'tanda_vital_suhu',
                'tanda_vital_spo2',
                'tindakan_diberikan',
                'transportasi',
                'kebutuhan_khusus',
                'estimasi_tiba',
                'status',
                'catatan_igd',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            ]),
            'Semua kolom permintaan_rujukan_faskes harus lengkap sesuai spesifikasi'
        );
    }

    public function test_permintaan_rujukan_faskes_indexes_exist(): void
    {
        if (DB::connection('dbsimrs')->getDriverName() === 'sqlite') {
            $indices = collect(DB::connection('dbsimrs')->select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = 'permintaan_rujukan_faskes'"))
                ->pluck('name')
                ->all();

            $this->assertContains('idx_tgl_status', $indices);
            $this->assertContains('idx_no_ktp', $indices);
            $this->assertContains('idx_nama_pasien', $indices);

            return;
        }

        $indices = collect(DB::connection('dbsimrs')->select('SHOW INDEX FROM permintaan_rujukan_faskes'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        $this->assertContains('PRIMARY', $indices);
        $this->assertContains('idx_tgl_status', $indices);
        $this->assertContains('idx_no_ktp', $indices);
        $this->assertContains('idx_nama_pasien', $indices);
    }
}
