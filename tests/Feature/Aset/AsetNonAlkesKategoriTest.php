<?php

use App\Models\AsetBarang;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('resolves kategori from ancestor when leaf has none', function () {
    $kategori = AsetKategori::query()->create([
        'kode_kategori' => 'EL',
        'nama_kategori' => 'Elektronik',
    ]);
    $root = AsetNonAlkes::query()->create([
        'id_alat' => '500001',
        'nama_alat' => 'KOMPUTER UNIT',
        'kode' => '10',
        'level' => 1,
        'aset_kategori_id' => $kategori->id,
        'deleted' => false,
    ]);
    $folder = AsetNonAlkes::query()->create([
        'id_alat' => '500002',
        'nama_alat' => 'PERSONAL KOMPUTER',
        'kode' => '10.01',
        'parent_id' => $root->id,
        'level' => 2,
        'deleted' => false,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '500003',
        'nama_alat' => 'Lap Top',
        'kode' => '10.01.001',
        'parent_id' => $folder->id,
        'level' => 3,
        'deleted' => false,
    ]);

    expect($leaf->resolvedKategoriId())->toBe($kategori->id)
        ->and($leaf->resolvedKategoriNama())->toBe('Elektronik');
});

it('auto-fills kategori when creating aset from non-alkes leaf', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT', 'nama_ruang' => 'IT']);
    $kategori = AsetKategori::query()->create([
        'kode_kategori' => 'EL2',
        'nama_kategori' => 'Elektronik',
    ]);
    $root = AsetNonAlkes::query()->create([
        'id_alat' => '510001',
        'nama_alat' => 'KOMPUTER',
        'kode' => '11',
        'level' => 1,
        'aset_kategori_id' => $kategori->id,
        'deleted' => false,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '510002',
        'nama_alat' => 'Notebook',
        'kode' => '11.01.001',
        'parent_id' => $root->id,
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'non_medis',
            'aset_non_alkes_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();

    $barang = AsetBarang::query()->where('aset_non_alkes_id', $leaf->id)->first();
    expect($barang)->not->toBeNull()
        ->and($barang->aset_kategori_id)->toBe($kategori->id);
});

it('search non-alkes returns resolved kategori id', function () {
    $user = User::factory()->create();
    $kategori = AsetKategori::query()->create([
        'kode_kategori' => 'FR',
        'nama_kategori' => 'Furniture',
    ]);
    $root = AsetNonAlkes::query()->create([
        'id_alat' => '520001',
        'nama_alat' => 'MEBEL',
        'kode' => '20',
        'level' => 1,
        'aset_kategori_id' => $kategori->id,
        'deleted' => false,
    ]);
    AsetNonAlkes::query()->create([
        'id_alat' => '520002',
        'nama_alat' => 'Meja Kerja',
        'kode' => '20.01.001',
        'parent_id' => $root->id,
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->getJson('/aset/master/non-alkes/search?q=Meja')
        ->assertSuccessful()
        ->assertJsonPath('0.aset_kategori_id', $kategori->id);
});

it('can assign kategori on folder from master page', function () {
    $user = User::factory()->create();
    $kategori = AsetKategori::query()->create([
        'kode_kategori' => 'EL3',
        'nama_kategori' => 'Elektronik',
    ]);
    $root = AsetNonAlkes::query()->create([
        'id_alat' => '530001',
        'nama_alat' => 'ALAT BANTU',
        'kode' => '1',
        'level' => 1,
        'deleted' => false,
    ]);

    actingAs($user)
        ->patch("/aset/master/non-alkes/{$root->id}/kategori", [
            'aset_kategori_id' => $kategori->id,
        ])
        ->assertRedirect();

    expect($root->fresh()->aset_kategori_id)->toBe($kategori->id);
});
