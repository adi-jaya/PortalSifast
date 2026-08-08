<?php

namespace Database\Seeders;

use App\Models\KodeSifatNaskah;
use App\Models\KodeUnitKlasifikasi;
use App\Models\KonfigurasiJenisDokumen;
use Illuminate\Database\Seeder;

class TatanaskahSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['kode' => 'A', 'nama' => 'Biasa'],
            ['kode' => 'I', 'nama' => 'Internal'],
            ['kode' => 'S', 'nama' => 'Segera'],
            ['kode' => 'R', 'nama' => 'Rahasia'],
        ] as $sifat) {
            KodeSifatNaskah::query()->updateOrCreate(
                ['kode' => $sifat['kode']],
                ['nama' => $sifat['nama'], 'is_aktif' => true],
            );
        }

        foreach ([
            ['kode' => 'III.6.AU', 'nama' => 'Administrasi Umum', 'dep_id' => 'ADM'],
            ['kode' => 'III.1.DIR', 'nama' => 'Direktur', 'dep_id' => 'DIR'],
            ['kode' => 'III.2.MUT', 'nama' => 'Mutu & Kesekretariatan', 'dep_id' => 'MUT'],
        ] as $unit) {
            KodeUnitKlasifikasi::query()->updateOrCreate(
                ['kode' => $unit['kode']],
                ['nama' => $unit['nama'], 'dep_id' => $unit['dep_id'], 'is_aktif' => true],
            );
        }

        $jenisList = [
            ['kode' => 'SPO', 'nama' => 'Standar Prosedur Operasional', 'varian_kop' => 'tabel_spo'],
            ['kode' => 'PER', 'nama' => 'Peraturan Direktur', 'varian_kop' => 'standar'],
            ['kode' => 'SK', 'nama' => 'Keputusan Direktur', 'varian_kop' => 'standar'],
            ['kode' => 'INS', 'nama' => 'Instruksi Direktur', 'varian_kop' => 'standar'],
            ['kode' => 'SE', 'nama' => 'Surat Edaran Direktur', 'varian_kop' => 'standar'],
            ['kode' => 'PDM', 'nama' => 'Pedoman', 'varian_kop' => 'standar'],
            ['kode' => 'PAN', 'nama' => 'Panduan', 'varian_kop' => 'standar'],
            ['kode' => 'CP', 'nama' => 'Clinical Pathway', 'varian_kop' => 'standar'],
            ['kode' => 'PRK', 'nama' => 'Program Kerja', 'varian_kop' => 'standar'],
        ];

        foreach ($jenisList as $jenis) {
            KonfigurasiJenisDokumen::query()->updateOrCreate(
                ['kode' => $jenis['kode']],
                [
                    'nama' => $jenis['nama'],
                    'kategori' => 'regulasi',
                    'format_nomor' => "RS'ASF/[NNN]/[UNIT_KLASIFIKASI]/[SIFAT]/[BR]/[YYYY]",
                    'prefix_kode_rs' => "RS'ASF",
                    'varian_kop' => $jenis['varian_kop'],
                    'tipe_workflow' => 'regulasi',
                    'butuh_tte_direktur' => true,
                    'pakai_salam_islami' => true,
                    'is_aktif' => true,
                ],
            );
        }
    }
}
