<?php

use App\Models\Inventaris;
use App\Models\InventarisRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    try {
        Inventaris::query()->limit(1)->get();
    } catch (\Throwable) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('renders inventaris qr label print page for 24mm tape', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->with(['barang', 'ruang'])->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris sample data available.');
    }

    actingAs($user)
        ->get(route('inventaris.label-print', $sample->no_inventaris))
        ->assertOk()
        ->assertSee($sample->no_inventaris, false)
        ->assertSee('Epson LW-600P', false)
        ->assertSee('24mm', false)
        ->assertSee('<svg', false);
});

it('renders batch label print for one room', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->whereNotNull('id_ruang')->where('id_ruang', '!=', '')->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris with room available.');
    }

    $ruang = InventarisRuang::query()->find($sample->id_ruang);
    $count = Inventaris::query()->where('id_ruang', $sample->id_ruang)->count();

    actingAs($user)
        ->get(route('inventaris.label-print-batch', ['id_ruang' => $sample->id_ruang]))
        ->assertOk()
        ->assertSee('Cetak massal ruang', false)
        ->assertSee((string) $count.' label', false)
        ->assertSee($ruang?->nama_ruang ?? '', false)
        ->assertSee('<svg', false);
});

it('requires id_ruang for batch label print', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('inventaris.label-print-batch'))
        ->assertStatus(422);
});
