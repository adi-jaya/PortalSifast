<?php

use App\Models\Aset;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can upload and delete aset foto on portal disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD01',
        'nama_ruang' => 'IGD',
    ]);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IGD01-2026-0001',
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD01',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->post(route('aset.foto.store', $aset), [
            'foto' => UploadedFile::fake()->image('alat.jpg', 200, 200),
        ])
        ->assertRedirect();

    $aset->refresh();
    expect($aset->foto()->count())->toBe(1);
    Storage::disk('public')->assertExists($aset->foto()->first()->path);

    $foto = $aset->foto()->first();

    actingAs($user)
        ->delete(route('aset.foto.destroy', [$aset, $foto]))
        ->assertRedirect();

    expect($aset->foto()->count())->toBe(0);
});
