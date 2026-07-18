<?php

use App\Models\Inventaris;
use App\Models\InventarisRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventarisStatusDatabaseAvailable(): bool
{
    try {
        Inventaris::query()->limit(1)->get();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

function inventarisStatusWriteAllowed(): bool
{
    try {
        $sample = Inventaris::query()->first();
        if (! $sample) {
            return false;
        }

        $original = $sample->status_barang;
        $probe = $original === 'Ada' ? 'Rusak' : 'Ada';

        DB::connection('dbsimrs')
            ->table('inventaris')
            ->where('no_inventaris', $sample->no_inventaris)
            ->update(['status_barang' => $probe]);

        DB::connection('dbsimrs')
            ->table('inventaris')
            ->where('no_inventaris', $sample->no_inventaris)
            ->update(['status_barang' => $original]);

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    if (! inventarisStatusDatabaseAvailable()) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('includes stats and open_tickets on inventaris index', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/inventaris')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris/index')
            ->has('stats.total')
            ->has('stats.by_status')
            ->has('inventaris.data.0', fn (Assert $item) => $item
                ->has('open_tickets')
                ->etc()
            )
        );
});

it('requires authentication for inventaris audit', function () {
    $ruang = InventarisRuang::query()->first();

    if (! $ruang) {
        $this->markTestSkipped('No inventaris_ruang sample data available.');
    }

    $this->get(route('inventaris.audit', ['id_ruang' => $ruang->id_ruang]))
        ->assertRedirect(route('login'));
});

it('can open inventaris audit for a room', function () {
    $user = User::factory()->create();
    $ruang = InventarisRuang::query()->first();

    if (! $ruang) {
        $this->markTestSkipped('No inventaris_ruang sample data available.');
    }

    actingAs($user)
        ->get(route('inventaris.audit', ['id_ruang' => $ruang->id_ruang]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris/audit')
            ->where('ruang.id_ruang', $ruang->id_ruang)
            ->has('items')
            ->has('statusOptions')
            ->has('allRuang')
        );
});

it('rejects inventaris audit without id_ruang', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('inventaris.audit'))
        ->assertUnprocessable();
});

it('blocks inventaris status update route after read-only cutover', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris sample data available.');
    }

    actingAs($user)
        ->patch('/inventaris/'.$sample->no_inventaris.'/status', [
            'status_barang' => 'Rusak',
        ])
        ->assertNotFound();
});

it('no longer exposes inventaris status named route', function () {
    expect(fn () => route('inventaris.status', 'X'))->toThrow(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

it('passes status options on inventaris show', function () {
    $user = User::factory()->create();
    $sample = Inventaris::query()->first();

    if (! $sample) {
        $this->markTestSkipped('No inventaris sample data available.');
    }

    actingAs($user)
        ->get(route('inventaris.show', $sample->no_inventaris))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventaris/show')
            ->has('statusOptions')
        );
});
