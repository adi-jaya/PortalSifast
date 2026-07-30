<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetMerk;
use App\Models\AsetRuang;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('renders aset show with trilux-style detail props', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'MATA', 'nama_ruang' => 'Poli Spesialis Mata']);
    $merk = AsetMerk::query()->create(['kode_merk' => 'LEN', 'nama_merk' => 'Lenovo']);
    $barang = AsetBarang::query()->create([
        'kode_barang' => '10.01.002',
        'nama_barang' => 'Mini Komputer',
        'aset_merk_id' => $merk->id,
        'kelas_aset' => 'non_medis',
        'umur_ekonomis_bulan' => 60,
        'tahun_produksi' => 2026,
        'level_teknologi' => 'medium',
        'nilai_residu' => 60000,
    ]);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-MATA-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'MATA',
        'tahun_registrasi' => 2026,
        'no_seri' => '14451541',
        'harga' => 6000000,
        'asal_barang' => 'APBD',
        'tanggal_pengadaan' => '2026-01-15',
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
        'status_ketersediaan' => 'tersedia',
    ]);

    actingAs($user)
        ->get("/aset/{$aset->kode_aset}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/show')
            ->where('aset.kode_aset', 'INV-MATA-2026-0001')
            ->where('aset.no_seri', '14451541')
            ->where('aset.harga', '6000000.00')
            ->where('aset.barang.nama_barang', 'Mini Komputer')
            ->where('aset.barang.nama_merk', 'Lenovo')
            ->where('aset.barang.level_teknologi', 'medium')
            ->where('aset.barang.umur_ekonomis_bulan', 60)
            ->where('aset.ruang.nama_ruang', 'Poli Spesialis Mata')
            ->where('aset.status_fungsi', 'berfungsi')
            ->where('penyusutan.dapat_dihitung', true)
            ->where('penyusutan.metode', 'garis_lurus')
            ->where('penyusutan.penyusutan_per_bulan', 99000)
            ->has('tickets')
            ->has('peminjamanRiwayat')
            ->has('mutasiRiwayat')
            ->where('monitoring', null)
            ->has('canLinkMonitoring')
            ->has('linkableDevices'));
});
