<?php

use App\Models\Inventaris;
use App\Models\InventarisBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventarisDatabaseAvailable(): bool
{
    try {
        Inventaris::query()->limit(1)->get();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

function inventarisWriteAllowed(): bool
{
    try {
        $probe = 'ZW'.substr(uniqid(), -6);
        DB::connection('dbsimrs')->table('inventaris')->insert([
            'no_inventaris' => $probe,
            'kode_barang' => InventarisBarang::query()->value('kode_barang') ?? 'X',
            'asal_barang' => 'Beli',
            'status_barang' => 'Ada',
        ]);
        DB::connection('dbsimrs')->table('inventaris')->where('no_inventaris', $probe)->delete();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    if (! inventarisDatabaseAvailable()) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('requires authentication for inventaris index', function () {
    $this->get('/inventaris')->assertRedirect(route('login'));
});

it('can list inventaris with filters', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/inventaris?status=Ada')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris/index')
            ->has('inventaris.data')
            ->where('filters.status', 'Ada')
            ->has('statusOptions')
            ->has('ruang')
            ->has('stats')
            ->has('inventaris.data.0', fn (Assert $item) => $item
                ->has('has_photo')
                ->has('photo_src')
                ->has('harga')
                ->has('asal_barang')
                ->has('open_tickets')
                ->etc()
            )
        );
});

it('can stream inventaris photo without exposing simrs host', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->has('gambar')->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris with photo available.');
    }

    actingAs($user)
        ->get(route('inventaris.photo', $sample->no_inventaris))
        ->assertOk()
        ->assertHeader('content-type');
});

it('can show inventaris with tickets payload', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->with('barang')->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris sample data available.');
    }

    actingAs($user)
        ->get('/inventaris/'.$sample->no_inventaris)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris/show')
            ->where('inventaris.no_inventaris', $sample->no_inventaris)
            ->has('tickets')
            ->has('inventaris.photo_url')
        );
});

it('can create update and delete inventaris', function () {
    $this->markTestSkipped('Penulisan inventaris ke SIMRS dinonaktifkan; gunakan modul Aset portal.');
});

it('rejects inventaris store because simrs writes are disabled', function () {
    $user = User::factory()->create();
    $barang = InventarisBarang::query()->first();

    if (! $barang) {
        $this->markTestSkipped('No inventaris_barang sample data available.');
    }

    actingAs($user)
        ->post('/inventaris', [
            'no_inventaris' => 'T'.substr(uniqid(), -8).'BAD',
            'kode_barang' => $barang->kode_barang,
            'status_barang' => 'TidakValid',
        ])
        ->assertMethodNotAllowed();
});
