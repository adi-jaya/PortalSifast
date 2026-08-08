<?php

use App\Models\AsetBarang;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use App\Models\User;
use App\Services\Inventaris\MergeAsetKategori;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('merges kategori source into target and deletes source', function () {
    $target = AsetKategori::query()->create([
        'kode_kategori' => 'KI005',
        'nama_kategori' => 'Komputer',
    ]);
    $source = AsetKategori::query()->create([
        'kode_kategori' => 'KI016',
        'nama_kategori' => 'Laptop / Notebook',
    ]);
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'BRG-LAP',
        'nama_barang' => 'Unit lama',
        'aset_kategori_id' => $source->id,
    ]);
    $folder = AsetNonAlkes::query()->create([
        'id_alat' => '900001',
        'nama_alat' => 'PERSONAL KOMPUTER',
        'kode' => '10.02',
        'level' => 2,
        'aset_kategori_id' => $source->id,
        'deleted' => false,
    ]);

    $stats = app(MergeAsetKategori::class)->handle($target, $source);

    expect($stats['barang'])->toBe(1)
        ->and($stats['non_alkes'])->toBe(1)
        ->and($barang->fresh()->aset_kategori_id)->toBe($target->id)
        ->and($folder->fresh()->aset_kategori_id)->toBe($target->id)
        ->and(AsetKategori::query()->find($source->id))->toBeNull();
});

it('merges kategori via master page', function () {
    $user = User::factory()->create();
    $target = AsetKategori::query()->create([
        'kode_kategori' => 'KOMP',
        'nama_kategori' => 'Komputer',
    ]);
    $source = AsetKategori::query()->create([
        'kode_kategori' => 'LAP',
        'nama_kategori' => 'Laptop / Notebook',
    ]);

    actingAs($user)
        ->post("/aset/master/kategori/{$target->id}/merge", [
            'source_id' => $source->id,
        ])
        ->assertRedirect();

    expect(AsetKategori::query()->where('nama_kategori', 'Laptop / Notebook')->exists())->toBeFalse()
        ->and(AsetKategori::query()->whereKey($target->id)->exists())->toBeTrue();
});

it('updates non-alkes nama from master page', function () {
    $user = User::factory()->create();
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '900002',
        'nama_alat' => 'Lap Top',
        'kode' => '10.02.002',
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->patch("/aset/master/non-alkes/{$leaf->id}/nama", [
            'nama_alat' => 'Laptop',
        ])
        ->assertRedirect();

    expect($leaf->fresh()->nama_alat)->toBe('Laptop');
});

it('rejects blank non-alkes nama', function () {
    $user = User::factory()->create();
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '900003',
        'nama_alat' => 'Note Book',
        'kode' => '10.02.003',
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->patch("/aset/master/non-alkes/{$leaf->id}/nama", [
            'nama_alat' => 'A',
        ])
        ->assertSessionHasErrors('nama_alat');
});
