<?php

use App\Models\AsetAspakAlat;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('renders katalog aspak master page', function () {
    $user = User::factory()->create();

    $root = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '1549',
        'nama_alat' => 'Obstetric Devices',
        'kode' => '21101',
    ]);
    AsetAspakAlat::query()->create([
        'id_alat_aspak' => '4083',
        'nama_alat' => 'Laparoscopic insufflator',
        'kode' => '21101018',
        'parent_id' => $root->id,
        'wajib_kalibrasi' => true,
    ]);

    actingAs($user)
        ->get('/aset/master/aspak')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-aspak/index')
            ->has('items.data', 2)
            ->where('stats.total', 2)
            ->where('stats.leaf', 1));
});

it('filters only leaf on aspak master page', function () {
    $user = User::factory()->create();

    $root = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '300',
        'nama_alat' => 'Parent',
        'kode' => '30',
    ]);
    AsetAspakAlat::query()->create([
        'id_alat_aspak' => '301',
        'nama_alat' => 'Leaf Tool',
        'kode' => '30.01',
        'parent_id' => $root->id,
    ]);

    actingAs($user)
        ->get('/aset/master/aspak?only_leaf=1')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-aspak/index')
            ->has('items.data', 1)
            ->where('items.data.0.nama_alat', 'Leaf Tool')
            ->where('items.data.0.is_leaf', true));
});
