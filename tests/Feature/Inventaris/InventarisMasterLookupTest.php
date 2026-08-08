<?php

use App\Models\InventarisJenis;
use App\Models\InventarisKategori;
use App\Models\InventarisMerk;
use App\Models\InventarisProdusen;
use App\Models\InventarisRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventarisMasterWriteAllowed(): bool
{
    try {
        $id = 'ZW'.substr(uniqid(), -3);
        DB::connection('dbsimrs')->table('inventaris_ruang')->insert([
            'id_ruang' => $id,
            'nama_ruang' => 'Probe',
        ]);
        DB::connection('dbsimrs')->table('inventaris_ruang')->where('id_ruang', $id)->delete();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    try {
        InventarisRuang::query()->limit(1)->get();
    } catch (\Throwable) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('can open inventaris master index pages', function () {
    $user = User::factory()->create();

    actingAs($user)->get('/inventaris-ruang')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inventaris-ruang/index'));
    actingAs($user)->get('/inventaris-kategori')->assertOk();
    actingAs($user)->get('/inventaris-jenis')->assertOk();
    actingAs($user)->get('/inventaris-merk')->assertOk();
    actingAs($user)->get('/inventaris-produsen')->assertOk();
});

it('can manage inventaris ruang master', function () {
    if (! inventarisMasterWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write inventaris master tables.');
    }

    $user = User::factory()->create();
    $id = 'T'.substr(uniqid(), -4);

    actingAs($user)
        ->post('/inventaris-ruang', [
            'id_ruang' => $id,
            'nama_ruang' => 'Ruang Test',
        ])
        ->assertRedirect(route('inventaris-ruang.index'));

    expect(InventarisRuang::query()->where('id_ruang', $id)->exists())->toBeTrue();

    actingAs($user)
        ->patch('/inventaris-ruang/'.$id, ['nama_ruang' => 'Ruang Updated'])
        ->assertRedirect(route('inventaris-ruang.index'));

    actingAs($user)
        ->delete('/inventaris-ruang/'.$id)
        ->assertRedirect(route('inventaris-ruang.index'));

    expect(InventarisRuang::query()->where('id_ruang', $id)->exists())->toBeFalse();
});

it('can manage inventaris kategori jenis merk and produsen', function () {
    if (! inventarisMasterWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write inventaris master tables.');
    }

    $user = User::factory()->create();
    $suffix = substr(str_replace('.', '', uniqid('', true)), -6);

    actingAs($user)->post('/inventaris-kategori', [
        'id_kategori' => 'K'.$suffix,
        'nama_kategori' => 'Kat Test',
    ])->assertRedirect();

    actingAs($user)->post('/inventaris-jenis', [
        'id_jenis' => 'J'.$suffix,
        'nama_jenis' => 'Jenis Test',
    ])->assertRedirect();

    actingAs($user)->post('/inventaris-merk', [
        'id_merk' => 'M'.$suffix,
        'nama_merk' => 'Merk Test',
    ])->assertRedirect();

    actingAs($user)->post('/inventaris-produsen', [
        'kode_produsen' => 'P'.$suffix,
        'nama_produsen' => 'Prod Test',
        'email' => 'a@b.co',
        'no_telp' => '081234',
    ])->assertRedirect();

    expect(InventarisKategori::query()->where('id_kategori', 'K'.$suffix)->exists())->toBeTrue();
    expect(InventarisJenis::query()->where('id_jenis', 'J'.$suffix)->exists())->toBeTrue();
    expect(InventarisMerk::query()->where('id_merk', 'M'.$suffix)->exists())->toBeTrue();
    expect(InventarisProdusen::query()->where('kode_produsen', 'P'.$suffix)->exists())->toBeTrue();

    actingAs($user)->delete('/inventaris-kategori/K'.$suffix)->assertRedirect();
    actingAs($user)->delete('/inventaris-jenis/J'.$suffix)->assertRedirect();
    actingAs($user)->delete('/inventaris-merk/M'.$suffix)->assertRedirect();
    actingAs($user)->delete('/inventaris-produsen/P'.$suffix)->assertRedirect();
});
