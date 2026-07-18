<?php

use App\Models\InventarisBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventarisBarangDatabaseAvailable(): bool
{
    try {
        InventarisBarang::query()->limit(1)->get();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

function inventarisBarangWriteAllowed(): bool
{
    try {
        $probe = 'ZW'.substr(uniqid(), -8);
        DB::connection('dbsimrs')->table('inventaris_barang')->insert([
            'kode_barang' => $probe,
            'nama_barang' => 'Probe',
        ]);
        DB::connection('dbsimrs')->table('inventaris_barang')->where('kode_barang', $probe)->delete();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    if (! inventarisBarangDatabaseAvailable()) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('can list inventaris barang with kategori filter payload', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/inventaris-barang')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris-barang/index')
            ->has('barang.data')
            ->has('kategori')
            ->has('jenis')
            ->has('filters')
        );
});

it('can show inventaris barang with units list', function () {
    $user = User::factory()->create();
    $barang = InventarisBarang::query()
        ->where('kode_barang', 'not like', '%/%')
        ->first()
        ?? InventarisBarang::query()->first();

    if (! $barang) {
        $this->markTestSkipped('No inventaris_barang sample data available.');
    }

    actingAs($user)
        ->get('/inventaris-barang/'.$barang->kode_barang)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris-barang/show')
            ->where('barang.kode_barang', $barang->kode_barang)
            ->has('units')
        );
});

it('validates inventaris barang using dbsimrs rules', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/inventaris-barang', [
            'kode_barang' => '',
            'nama_barang' => '',
        ])
        ->assertSessionHasErrors(['kode_barang', 'nama_barang']);
});

it('can create and delete inventaris barang', function () {
    if (! inventarisBarangWriteAllowed()) {
        $this->markTestSkipped('dbsimrs user cannot write inventaris_barang.');
    }

    $user = User::factory()->create();
    $kode = 'T'.substr(str_replace('.', '', uniqid('', true)), -9);

    actingAs($user)
        ->post('/inventaris-barang', [
            'kode_barang' => $kode,
            'nama_barang' => 'Barang Test Portal',
            'jml_barang' => 1,
        ])
        ->assertRedirect(route('inventaris-barang.index'));

    expect(InventarisBarang::query()->where('kode_barang', $kode)->exists())->toBeTrue();

    actingAs($user)
        ->delete('/inventaris-barang/'.$kode)
        ->assertRedirect(route('inventaris-barang.index'));

    expect(InventarisBarang::query()->where('kode_barang', $kode)->exists())->toBeFalse();
});
