<?php

namespace Database\Seeders;

use App\Models\DriverChecklistItem;
use App\Models\DriverKendaraan;
use App\Models\DriverKendaraanItem;
use Illuminate\Database\Seeder;

class DriverChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $kendaraanSeed = [
            ['nama' => 'AMB APV', 'no_polisi' => null, 'merk' => 'Toyota', 'model' => 'APV', 'tahun' => null],
            ['nama' => 'AMB HIACE', 'no_polisi' => null, 'merk' => 'Toyota', 'model' => 'Hiace', 'tahun' => null],
            ['nama' => 'ERTIGA', 'no_polisi' => null, 'merk' => 'Suzuki', 'model' => 'Ertiga', 'tahun' => null],
            ['nama' => 'INNOVA', 'no_polisi' => null, 'merk' => 'Toyota', 'model' => 'Innova', 'tahun' => null],
        ];

        $itemsSeed = [
            ['nama' => 'Tekanan Angin', 'kategori' => 'umum', 'urutan' => 1],
            ['nama' => 'Rem', 'kategori' => 'umum', 'urutan' => 2],
            ['nama' => 'Lampu', 'kategori' => 'umum', 'urutan' => 3],
            ['nama' => 'Klakson & Setir', 'kategori' => 'umum', 'urutan' => 4],
            ['nama' => 'Sirine & Rotator', 'kategori' => 'ambulance', 'urutan' => 5],
            ['nama' => 'Oli', 'kategori' => 'umum', 'urutan' => 6],
            ['nama' => 'Air Radiator', 'kategori' => 'umum', 'urutan' => 7],
            ['nama' => 'Accu', 'kategori' => 'umum', 'urutan' => 8],
            ['nama' => 'Wiper', 'kategori' => 'umum', 'urutan' => 9],
            ['nama' => 'Oksigen', 'kategori' => 'ambulance', 'urutan' => 10],
            ['nama' => 'BBM', 'kategori' => 'umum', 'urutan' => 11],
            ['nama' => 'Ban Cadangan', 'kategori' => 'umum', 'urutan' => 12],
            ['nama' => 'Kelengkapan STNK', 'kategori' => 'dokumen', 'urutan' => 13],
            ['nama' => 'Pajak Kendaraan', 'kategori' => 'dokumen', 'urutan' => 14],
            ['nama' => 'Pencucian Kendaraan', 'kategori' => 'umum', 'urutan' => 15],
            ['nama' => 'Saldo E-Tol', 'kategori' => 'umum', 'urutan' => 16],
        ];

        $tidakBerlakuUntuk = [
            'ERTIGA' => ['Sirine & Rotator', 'Oksigen'],
            'INNOVA' => ['Sirine & Rotator', 'Oksigen'],
        ];

        $kendaraanModels = collect($kendaraanSeed)->map(function (array $data) {
            return DriverKendaraan::query()->firstOrCreate(
                ['nama' => $data['nama']],
                [
                    ...$data,
                    'status' => DriverKendaraan::STATUS_AKTIF,
                ],
            );
        });

        $itemModels = collect($itemsSeed)->map(function (array $data) {
            return DriverChecklistItem::query()->updateOrCreate(
                ['nama' => $data['nama']],
                [
                    'kategori' => $data['kategori'],
                    'urutan' => $data['urutan'],
                    'aktif' => true,
                ],
            );
        });

        foreach ($kendaraanModels as $kendaraan) {
            foreach ($itemModels as $item) {
                $naList = $tidakBerlakuUntuk[$kendaraan->nama] ?? [];
                $berlaku = ! in_array($item->nama, $naList, true);

                DriverKendaraanItem::query()->updateOrCreate(
                    [
                        'driver_kendaraan_id' => $kendaraan->id,
                        'driver_checklist_item_id' => $item->id,
                    ],
                    ['berlaku' => $berlaku],
                );
            }
        }
    }
}
