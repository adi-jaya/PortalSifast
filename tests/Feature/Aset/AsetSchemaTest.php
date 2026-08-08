<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetCounterNomor;
use App\Models\AsetRuang;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create local aset schema records', function () {
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD01',
        'nama_ruang' => 'IGD',
    ]);

    $barang = AsetBarang::query()->create([
        'kode_barang' => 'BRG001',
        'nama_barang' => 'Monitor',
        'kelas_aset' => 'medis',
        'wajib_kalibrasi' => true,
        'umur_ekonomis_bulan' => 60,
    ]);

    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IGD01-2026-0001',
        'no_simrs' => 'I000000001',
        'aset_barang_id' => $barang->id,
        'kode_ruang_registrasi' => $ruang->kode_ruang,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'draf',
    ]);

    AsetCounterNomor::query()->create([
        'awalan' => 'INV',
        'kode_ruang' => 'IGD01',
        'tahun' => 2026,
        'terakhir' => 1,
    ]);

    expect($aset->fresh()->barang?->nama_barang)->toBe('Monitor')
        ->and($aset->ruang?->kode_ruang)->toBe('IGD01')
        ->and($barang->wajib_kalibrasi)->toBeTrue();
});
