<?php

use App\Models\AsetJenis;
use App\Models\AsetMerk;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates jenis bound to selected merk', function () {
    $user = User::factory()->create();
    $merk = AsetMerk::query()->create(['kode_merk' => 'LEN', 'nama_merk' => 'Lenovo']);

    actingAs($user)
        ->postJson('/aset/master/jenis', [
            'nama' => 'ThinkPad T14',
            'aset_merk_id' => $merk->id,
        ])
        ->assertCreated()
        ->assertJsonPath('item.nama', 'ThinkPad T14')
        ->assertJsonPath('item.aset_merk_id', $merk->id);

    expect(AsetJenis::query()->where('nama_jenis', 'ThinkPad T14')->value('aset_merk_id'))->toBe($merk->id);
});

it('rejects jenis that belongs to another merk', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT', 'nama_ruang' => 'IT']);
    $lenovo = AsetMerk::query()->create(['kode_merk' => 'LEN2', 'nama_merk' => 'Lenovo']);
    $asus = AsetMerk::query()->create(['kode_merk' => 'ASU', 'nama_merk' => 'Asus']);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'TP14',
        'nama_jenis' => 'ThinkPad T14',
        'aset_merk_id' => $lenovo->id,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '700001',
        'nama_alat' => 'Notebook',
        'kode' => '70.01.001',
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'non_medis',
            'aset_non_alkes_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'aset_merk_id' => $asus->id,
            'aset_jenis_id' => $jenis->id,
            'jumlah_unit' => 1,
        ])
        ->assertSessionHasErrors('aset_jenis_id');
});

it('allows shared jenis with null merk for any merk', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT2', 'nama_ruang' => 'IT']);
    $merk = AsetMerk::query()->create(['kode_merk' => 'DEL', 'nama_merk' => 'Dell']);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'LAP',
        'nama_jenis' => 'Laptop Umum',
        'aset_merk_id' => null,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '700002',
        'nama_alat' => 'Lap Top',
        'kode' => '70.02.001',
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'non_medis',
            'aset_non_alkes_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'aset_merk_id' => $merk->id,
            'aset_jenis_id' => $jenis->id,
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();
});
