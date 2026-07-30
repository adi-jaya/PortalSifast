<?php

use App\Models\Aset;
use App\Models\AsetAspakAlat;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can create portal-only aset with aspak leaf', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD01',
        'nama_ruang' => 'IGD',
    ]);
    $leaf = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '9001',
        'nama_alat' => 'Infus Pump',
        'kode' => 'INFUS01',
        'wajib_kalibrasi' => true,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2019,
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
        ->and($aset->barang?->wajib_kalibrasi)->toBeTrue()
        ->and($aset->barang?->aset_aspak_alat_id)->toBe($leaf->id);
});
