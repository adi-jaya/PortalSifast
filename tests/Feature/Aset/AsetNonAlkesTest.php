<?php

use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

it('imports non_alkes hierarchy from m_non_alkes-compatible csv', function () {
    $path = storage_path('app/test-import-non-alkes.csv');
    file_put_contents(
        $path,
        "id_alat,alat_name,alat_code,parent_id,alat_ket,level,sinonim,kode,deleted,alat_path\n".
        "100003,ALAT BANTU,1,0,,1,,1,0,\n".
        "100004,FEEDER,1.01,100003,,2,,1.01,0,\n".
        "100005,Elevator /Lift,1.01.001,100004,,3,,1.01.001,0,\n".
        "100013,COMPRESSOR,1.02,100003,,2,,1.02,0,\n".
        "100014,Transportable Compressor,1.02.001,100013,,3,,1.02.001,0,\n"
    );

    Artisan::call('aset:import-master', ['file' => $path, '--tipe' => 'non_alkes']);

    $root = AsetNonAlkes::query()->where('id_alat', '100003')->first();
    $folder = AsetNonAlkes::query()->where('id_alat', '100004')->first();
    $leaf = AsetNonAlkes::query()->where('id_alat', '100005')->first();

    expect($root)->not->toBeNull()
        ->and($root->parent_id)->toBeNull()
        ->and($folder->parent_id)->toBe($root->id)
        ->and($leaf->parent_id)->toBe($folder->id)
        ->and($leaf->isLeaf())->toBeTrue()
        ->and($folder->isLeaf())->toBeFalse();

    @unlink($path);
});

it('search non_alkes returns only leaf nodes', function () {
    $user = User::factory()->create();
    $root = AsetNonAlkes::query()->create([
        'id_alat' => '200001',
        'nama_alat' => 'KOMPUTER',
        'kode' => '10',
        'alat_code' => '10',
        'level' => 1,
        'deleted' => false,
    ]);
    $folder = AsetNonAlkes::query()->create([
        'id_alat' => '200002',
        'nama_alat' => 'PERSONAL KOMPUTER',
        'kode' => '10.02',
        'alat_code' => '10.02',
        'parent_id' => $root->id,
        'level' => 2,
        'deleted' => false,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '200003',
        'nama_alat' => 'Lap Top',
        'kode' => '10.02.002',
        'alat_code' => '10.02.002',
        'parent_id' => $folder->id,
        'level' => 3,
        'sinonim' => 'Laptop',
        'deleted' => false,
    ]);

    actingAs($user)
        ->getJson('/aset/master/non-alkes/search?q=Lap')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $leaf->id, 'nama_alat' => 'Lap Top'])
        ->assertJsonMissing(['id' => $folder->id])
        ->assertJsonMissing(['id' => $root->id]);
});

it('creates aset from non_alkes leaf and rejects parent folder', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT', 'nama_ruang' => 'IT']);

    $root = AsetNonAlkes::query()->create([
        'id_alat' => '300001',
        'nama_alat' => 'ALAT BANTU',
        'kode' => '1',
        'level' => 1,
        'deleted' => false,
    ]);
    $folder = AsetNonAlkes::query()->create([
        'id_alat' => '300002',
        'nama_alat' => 'FEEDER',
        'kode' => '1.01',
        'parent_id' => $root->id,
        'level' => 2,
        'deleted' => false,
    ]);
    $leaf = AsetNonAlkes::query()->create([
        'id_alat' => '300003',
        'nama_alat' => 'Elevator /Lift',
        'kode' => '1.01.001',
        'parent_id' => $folder->id,
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->post('/aset', [
            'aset_non_alkes_id' => $folder->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
            'status_fungsi' => 'berfungsi',
            'tingkat_kerusakan' => 'baik',
        ])
        ->assertSessionHasErrors('aset_non_alkes_id');

    actingAs($user)
        ->post('/aset', [
            'aset_non_alkes_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
            'status_fungsi' => 'berfungsi',
            'tingkat_kerusakan' => 'baik',
        ])
        ->assertRedirect();

    $barang = \App\Models\AsetBarang::query()->where('aset_non_alkes_id', $leaf->id)->first();
    expect($barang)->not->toBeNull()
        ->and($barang->nama_barang)->toBe('Elevator /Lift')
        ->and($barang->kode_barang)->toBe('1.01.001')
        ->and($barang->kelas_aset)->toBe('non_medis');

    expect(\App\Models\Aset::query()->where('aset_barang_id', $barang->id)->exists())->toBeTrue();
});
