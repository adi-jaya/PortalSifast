<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetKategori;
use App\Models\AsetPengaturan;
use App\Models\AsetRuang;
use App\Models\User;
use App\Services\Agent\PengaturanMonitorableKategori;

it('shows monitoring kategori settings page', function () {
    $user = User::factory()->create();
    AsetKategori::query()->create([
        'kode_kategori' => 'KI005',
        'nama_kategori' => 'Komputer',
    ]);
    AsetKategori::query()->create([
        'kode_kategori' => 'KI014',
        'nama_kategori' => 'Display',
    ]);

    $this->actingAs($user)
        ->get(route('monitoring.pengaturan-kategori.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('monitoring/pengaturan-kategori')
            ->has('kategori')
            ->has('selectedCodes')
            ->has('defaults'));
});

it('saves monitorable kategori codes and unlocks matching aset', function () {
    $user = User::factory()->create();
    $display = AsetKategori::query()->create([
        'kode_kategori' => 'KI014',
        'nama_kategori' => 'Display',
    ]);
    AsetKategori::query()->create([
        'kode_kategori' => 'KI005',
        'nama_kategori' => 'Komputer',
    ]);

    $ruang = AsetRuang::query()->create(['kode_ruang' => 'ITSET', 'nama_ruang' => 'IT']);
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'DELL-T30',
        'nama_barang' => 'dell t30',
        'aset_kategori_id' => $display->id,
        'id_kategori' => null,
        'kelas_aset' => 'non_medis',
    ]);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-ITSET-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_ketersediaan' => 'tersedia',
    ]);

    expect($aset->isMonitorableForAgent())->toBeFalse();

    $this->actingAs($user)
        ->put(route('monitoring.pengaturan-kategori.update'), [
            'kategori_codes' => ['KI014', 'KI005'],
        ])
        ->assertRedirect(route('monitoring.pengaturan-kategori.edit'));

    $stored = AsetPengaturan::query()
        ->where('kunci', PengaturanMonitorableKategori::SETTING_KEY)
        ->value('nilai');

    expect(json_decode((string) $stored, true))->toContain('KI014')
        ->and($aset->fresh()->isMonitorableForAgent())->toBeTrue();

    $this->actingAs($user)
        ->get(route('aset.show', $aset))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('canLinkMonitoring', true));
});

it('rejects unknown kategori codes', function () {
    $user = User::factory()->create();
    AsetKategori::query()->create([
        'kode_kategori' => 'KI005',
        'nama_kategori' => 'Komputer',
    ]);

    $this->actingAs($user)
        ->put(route('monitoring.pengaturan-kategori.update'), [
            'kategori_codes' => ['NOPE'],
        ])
        ->assertSessionHasErrors('kategori_codes.0');
});
