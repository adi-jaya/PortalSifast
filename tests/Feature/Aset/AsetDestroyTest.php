<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('requires authentication to delete aset', function () {
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'B1', 'nama_barang' => 'Laptop']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IT01-2026-0101',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT01',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    $this->delete(route('aset.destroy', $aset))
        ->assertRedirect(route('login'));

    expect(Aset::query()->whereKey($aset->id)->exists())->toBeTrue();
});

it('soft deletes aset from portal and redirects to index', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT02', 'nama_ruang' => 'Server']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'B2', 'nama_barang' => 'Switch']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IT02-2026-0202',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT02',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->from(route('aset.show', $aset))
        ->delete(route('aset.destroy', $aset))
        ->assertRedirect(route('aset.index'))
        ->assertSessionHas('success');

    expect(Aset::query()->whereKey($aset->id)->exists())->toBeFalse()
        ->and(Aset::withTrashed()->whereKey($aset->id)->exists())->toBeTrue();
});

it('aset show page exposes hapus aset action', function () {
    $show = file_get_contents(resource_path('js/pages/aset/show.tsx'));

    expect($show)
        ->toContain('handleDeleteAset')
        ->toContain('Hapus aset')
        ->toContain('router.delete(`/aset/${aset.kode_aset}`');
});
