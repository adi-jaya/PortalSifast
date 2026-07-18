<?php

use App\Models\AsetNonAlkes;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('renders katalog non-alkes master page', function () {
    $user = User::factory()->create();

    $root = AsetNonAlkes::query()->create([
        'id_alat' => '900001',
        'nama_alat' => 'ALAT BANTU',
        'kode' => '1',
        'level' => 1,
        'deleted' => false,
    ]);
    AsetNonAlkes::query()->create([
        'id_alat' => '900002',
        'nama_alat' => 'Elevator /Lift',
        'kode' => '1.01.001',
        'parent_id' => $root->id,
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->get('/aset/master/non-alkes')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-non-alkes/index')
            ->has('items.data', 2)
            ->where('stats.total', 2)
            ->where('stats.leaf', 1));
});

it('filters only leaf on master page', function () {
    $user = User::factory()->create();

    $root = AsetNonAlkes::query()->create([
        'id_alat' => '910001',
        'nama_alat' => 'KOMPUTER',
        'kode' => '10',
        'level' => 1,
        'deleted' => false,
    ]);
    AsetNonAlkes::query()->create([
        'id_alat' => '910002',
        'nama_alat' => 'Lap Top',
        'kode' => '10.02.002',
        'parent_id' => $root->id,
        'level' => 3,
        'deleted' => false,
    ]);

    actingAs($user)
        ->get('/aset/master/non-alkes?only_leaf=1')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-non-alkes/index')
            ->has('items.data', 1)
            ->where('items.data.0.nama_alat', 'Lap Top')
            ->where('items.data.0.is_leaf', true));
});
