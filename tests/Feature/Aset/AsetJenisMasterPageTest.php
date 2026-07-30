<?php

use App\Models\AsetJenis;
use App\Models\AsetMerk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renders master jenis page', function () {
    $user = User::factory()->create();
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'LEN',
        'nama_merk' => 'Lenovo',
    ]);
    AsetJenis::query()->create([
        'kode_jenis' => 'IDEA',
        'nama_jenis' => 'Ideapad',
        'aset_merk_id' => $merk->id,
    ]);
    AsetJenis::query()->create([
        'kode_jenis' => 'GEN',
        'nama_jenis' => 'General',
    ]);

    actingAs($user)
        ->get('/aset/master/jenis')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-jenis/index')
            ->has('items.data', 2)
            ->has('merkOptions', 1));
});

it('can assign merk on jenis from master page', function () {
    $user = User::factory()->create();
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'HP',
        'nama_merk' => 'HP',
    ]);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'ELITE',
        'nama_jenis' => 'EliteBook',
    ]);

    actingAs($user)
        ->patch("/aset/master/jenis/{$jenis->id}/merk", [
            'aset_merk_id' => $merk->id,
        ])
        ->assertRedirect();

    expect($jenis->fresh()->aset_merk_id)->toBe($merk->id);
});

it('can clear merk on jenis from master page', function () {
    $user = User::factory()->create();
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'DELL',
        'nama_merk' => 'Dell',
    ]);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'LAT',
        'nama_jenis' => 'Latitude',
        'aset_merk_id' => $merk->id,
    ]);

    actingAs($user)
        ->patch("/aset/master/jenis/{$jenis->id}/merk", [
            'aset_merk_id' => null,
        ])
        ->assertRedirect();

    expect($jenis->fresh()->aset_merk_id)->toBeNull();
});

it('filters unassigned jenis on master page', function () {
    $user = User::factory()->create();
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'ASUS',
        'nama_merk' => 'Asus',
    ]);
    AsetJenis::query()->create([
        'kode_jenis' => 'VIVO',
        'nama_jenis' => 'VivoBook',
        'aset_merk_id' => $merk->id,
    ]);
    AsetJenis::query()->create([
        'kode_jenis' => 'UNK',
        'nama_jenis' => 'Unknown',
    ]);

    actingAs($user)
        ->get('/aset/master/jenis?only_unassigned=1')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.nama_jenis', 'Unknown'));
});

it('imports jenis with kode_merk from csv', function () {
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'LEN2',
        'nama_merk' => 'Lenovo',
    ]);

    $csv = tempnam(sys_get_temp_dir(), 'jenis');
    file_put_contents($csv, "kode_jenis,nama_jenis,kode_merk\nTHINK,Thinkpad,LEN2\n");

    $this->artisan('aset:import-master', ['file' => $csv, '--tipe' => 'jenis'])
        ->assertSuccessful();

    $jenis = AsetJenis::query()->where('kode_jenis', 'THINK')->first();
    expect($jenis)->not->toBeNull()
        ->and($jenis->nama_jenis)->toBe('Thinkpad')
        ->and($jenis->aset_merk_id)->toBe($merk->id);

    unlink($csv);
});
