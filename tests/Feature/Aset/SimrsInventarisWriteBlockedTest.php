<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('blocks inventaris store route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/inventaris', [
            'no_inventaris' => 'XTEST001',
            'kode_barang' => 'X',
            'status_barang' => 'Ada',
        ])
        ->assertMethodNotAllowed();
});

it('blocks inventaris status patch route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch('/inventaris/I000000001/status', [
            'status_barang' => 'Rusak',
        ])
        ->assertNotFound();
});

it('blocks inventaris gambar upload route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/inventaris/I000000001/gambar')
        ->assertNotFound();
});

it('blocks inventaris ruang store route', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/inventaris-ruang', [
            'id_ruang' => 'XX',
            'nama_ruang' => 'Test',
        ])
        ->assertMethodNotAllowed();
});
