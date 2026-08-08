<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lists local aset and filters by kelas', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $barangMedis = AsetBarang::query()->create([
        'kode_barang' => 'M1',
        'nama_barang' => 'Monitor',
        'kelas_aset' => 'medis',
        'wajib_kalibrasi' => true,
    ]);
    $barangNon = AsetBarang::query()->create([
        'kode_barang' => 'N1',
        'nama_barang' => 'Meja',
        'kelas_aset' => 'non_medis',
        'wajib_kalibrasi' => false,
    ]);

    Aset::query()->create([
        'kode_aset' => 'INV-IGD01-2026-0001',
        'aset_barang_id' => $barangMedis->id,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);
    Aset::query()->create([
        'kode_aset' => 'INV-IGD01-2026-0002',
        'aset_barang_id' => $barangNon->id,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'draf',
    ]);

    actingAs($user)
        ->get('/aset?kelas_aset=medis')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('aset/index')
            ->has('asets.data', 1)
            ->where('asets.data.0.kelas_aset', 'medis')
            ->has('stats')
            ->has('stats.dimonitor')
        );
});

it('filters aset by monitoring link', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'PC1',
        'nama_barang' => 'PC',
        'kelas_aset' => 'non_medis',
        'id_kategori' => 'KI005',
    ]);
    $linked = Aset::query()->create([
        'kode_aset' => 'INV-IT01-2026-0100',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);
    Aset::query()->create([
        'kode_aset' => 'INV-IT01-2026-0101',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    \App\Models\MonitoredDevice::factory()->online()->create([
        'hostname' => 'pc-linked',
        'aset_id' => $linked->id,
    ]);

    actingAs($user)
        ->get('/aset?monitoring=dimonitor')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('aset/index')
            ->has('asets.data', 1)
            ->where('asets.data.0.kode_aset', 'INV-IT01-2026-0100')
            ->where('asets.data.0.monitoring.status', 'online')
            ->where('filters.monitoring', 'dimonitor')
        );
});
