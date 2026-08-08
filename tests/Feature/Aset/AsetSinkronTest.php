<?php

use App\Models\Aset;
use App\Models\User;
use App\Services\Inventaris\SinkronAsetDariSimrs;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function simrsAsetReadable(): bool
{
    try {
        \App\Models\Inventaris::query()->limit(1)->get();

        return true;
    } catch (\Throwable) {
        return false;
    }
}

beforeEach(function () {
    if (! simrsAsetReadable()) {
        $this->markTestSkipped('Database SIMRS (dbsimrs) not available.');
    }
});

it('previews and applies simrs sync into local aset tables', function () {
    $service = app(SinkronAsetDariSimrs::class);

    $preview = $service->preview();
    expect($preview['jumlah_baru'])->toBeGreaterThan(0);

    $first = $service->apply();
    expect($first['jumlah_baru'])->toBeGreaterThan(0)
        ->and(Aset::query()->whereNotNull('no_simrs')->count())->toBeGreaterThan(0);

    $sample = Aset::query()->whereNotNull('no_simrs')->first();
    expect($sample?->kode_aset)->toStartWith('INV-')
        ->and($sample?->siklus_hidup)->toBe('draf');

    $second = $service->apply();
    expect($second['jumlah_baru'])->toBe(0);
});

it('can open sinkron page when authenticated', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('aset.sinkron.index'))
        ->assertOk();
});
