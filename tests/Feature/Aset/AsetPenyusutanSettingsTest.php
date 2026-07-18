<?php

use App\Models\AsetPengaturan;
use App\Models\User;
use App\Services\Inventaris\PengaturanPenyusutanAset;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

it('renders and updates penyusutan settings', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/aset/pengaturan-penyusutan')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/pengaturan-penyusutan')
            ->where('pengaturan.metode', 'garis_lurus')
            ->where('pengaturan.residu_persen_default', 1)
            ->where('pengaturan.umur_bulan_medis', 60));

    actingAs($user)
        ->put('/aset/pengaturan-penyusutan', [
            'residu_persen_default' => 2.5,
            'umur_bulan_medis' => 72,
            'umur_bulan_non_medis' => 36,
            'umur_bulan_default' => 48,
        ])
        ->assertRedirect(route('aset.pengaturan-penyusutan.edit'));

    expect(AsetPengaturan::query()->where('kunci', 'residu_persen_default')->value('nilai'))->toBe('2.5')
        ->and(AsetPengaturan::query()->where('kunci', 'umur_bulan_medis')->value('nilai'))->toBe('72');

    Cache::forget('aset.pengaturan.penyusutan');
    $all = app(PengaturanPenyusutanAset::class)->all();
    expect($all['residu_persen_default'])->toBe(2.5)
        ->and($all['umur_bulan_medis'])->toBe(72)
        ->and($all['umur_bulan_non_medis'])->toBe(36);
});

it('rejects invalid residu percent', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->put('/aset/pengaturan-penyusutan', [
            'residu_persen_default' => 99,
            'umur_bulan_medis' => 60,
            'umur_bulan_non_medis' => 48,
            'umur_bulan_default' => 60,
        ])
        ->assertSessionHasErrors('residu_persen_default');
});

it('resolves umur and residu defaults for asset class', function () {
    Cache::forget('aset.pengaturan.penyusutan');
    $svc = app(PengaturanPenyusutanAset::class);

    expect($svc->umurDefaultUntuk('medis'))->toBe(60)
        ->and($svc->umurDefaultUntuk('non_medis'))->toBe(48)
        ->and($svc->residuDefaultDariHarga(6_000_000))->toBe(60_000.0);

    $resolved = $svc->resolveUntukAset(6_000_000, null, null, 'non_medis');
    expect($resolved['umur_bulan'])->toBe(48)
        ->and($resolved['nilai_residu'])->toBe(60_000.0)
        ->and($resolved['memakai_default_umur'])->toBeTrue()
        ->and($resolved['memakai_default_residu'])->toBeTrue();

    $explicit = $svc->resolveUntukAset(6_000_000, 36, 100_000, 'medis');
    expect($explicit['umur_bulan'])->toBe(36)
        ->and($explicit['nilai_residu'])->toBe(100_000.0)
        ->and($explicit['memakai_default_umur'])->toBeFalse();
});
