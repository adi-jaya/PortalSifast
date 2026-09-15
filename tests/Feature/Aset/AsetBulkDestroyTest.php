<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\User;

use function Pest\Laravel\actingAs;

function makeBulkDestroyAset(string $kode, string $ruangKode, string $barangKode): Aset
{
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => $ruangKode,
        'nama_ruang' => "Ruang {$ruangKode}",
    ]);
    $barang = AsetBarang::query()->create([
        'kode_barang' => $barangKode,
        'nama_barang' => "Barang {$barangKode}",
    ]);

    return Aset::query()->create([
        'kode_aset' => $kode,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => $ruangKode,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);
}

it('requires authentication to bulk delete aset', function () {
    $aset = makeBulkDestroyAset('INV-BD-2026-0001', 'BD01', 'BB1');

    $this->post(route('aset.bulk-destroy'), ['ids' => [$aset->id]])
        ->assertRedirect(route('login'));

    expect(Aset::query()->whereKey($aset->id)->exists())->toBeTrue();
});

it('soft deletes selected aset from index bulk action', function () {
    $user = User::factory()->create();
    $a = makeBulkDestroyAset('INV-BD-2026-0002', 'BD02', 'BB2');
    $b = makeBulkDestroyAset('INV-BD-2026-0003', 'BD03', 'BB3');
    $keep = makeBulkDestroyAset('INV-BD-2026-0004', 'BD04', 'BB4');

    actingAs($user)
        ->from(route('aset.index'))
        ->post(route('aset.bulk-destroy'), ['ids' => [$a->id, $b->id]])
        ->assertRedirect(route('aset.index'))
        ->assertSessionHas('success');

    expect(Aset::query()->whereKey($a->id)->exists())->toBeFalse()
        ->and(Aset::query()->whereKey($b->id)->exists())->toBeFalse()
        ->and(Aset::query()->whereKey($keep->id)->exists())->toBeTrue()
        ->and(Aset::withTrashed()->whereKey([$a->id, $b->id])->count())->toBe(2);
});

it('validates bulk delete ids', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->from(route('aset.index'))
        ->post(route('aset.bulk-destroy'), ['ids' => []])
        ->assertRedirect(route('aset.index'))
        ->assertSessionHasErrors('ids');
});

it('aset index page supports select and bulk delete', function () {
    $index = file_get_contents(resource_path('js/pages/aset/index.tsx'));

    expect($index)
        ->toContain('selectedIds')
        ->toContain('handleBulkDelete')
        ->toContain('/aset/bulk-delete')
        ->toContain('Hapus');
});
