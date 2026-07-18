<?php

use App\Models\Aset;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can create portal-only aset with custom kode', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD01',
        'nama_ruang' => 'IGD',
    ]);

    actingAs($user)
        ->post('/aset', [
            'nama_barang' => 'Infus Pump',
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2019,
            'kelas_aset' => 'medis',
            'wajib_kalibrasi' => true,
            'umur_ekonomis_bulan' => 60,
            'kondisi' => 'Ada',
        ])
        ->assertRedirect();

    $aset = Aset::query()->first();
    expect($aset)->not->toBeNull()
        ->and($aset->kode_aset)->toBe('INV-IGD01-2019-0001')
        ->and($aset->no_simrs)->toBeNull()
        ->and($aset->siklus_hidup)->toBe('aktif')
        ->and($aset->barang?->kelas_aset)->toBe('medis')
        ->and($aset->barang?->wajib_kalibrasi)->toBeTrue();
});
