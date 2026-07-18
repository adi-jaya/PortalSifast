<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetPeminjaman;
use App\Models\AsetRuang;
use App\Models\User;
use App\Services\Inventaris\BuatPeminjamanAset;
use App\Services\Inventaris\KembalikanPeminjamanAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function seedAsetAktifUntukPeminjaman(array $overrides = []): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'IGD'],
        ['nama_ruang' => 'IGD']
    );
    $barang = AsetBarang::query()->firstOrCreate(
        ['kode_barang' => 'BRG-PINJ'],
        ['nama_barang' => 'Alat Pinjam Test', 'kelas_aset' => 'medis']
    );

    return Aset::query()->create(array_merge([
        'kode_aset' => 'INV-IGD-2026-9001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
    ], $overrides));
}

it('defaults status_ketersediaan to tersedia', function () {
    $aset = seedAsetAktifUntukPeminjaman();
    expect($aset->fresh()->status_ketersediaan)->toBe('tersedia');
});

it('creates peminjaman and marks aset dipinjam', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman();

    $peminjaman = app(BuatPeminjamanAset::class)->handle(
        aset: $aset,
        actor: $user,
        peminjamUserId: $user->id,
        peminjamNik: null,
        tanggalPinjam: now(),
        tanggalKembaliRencana: now()->addDays(3)->toDateString(),
        catatan: 'Uji pinjam',
    );

    expect($peminjaman->status)->toBe('dipinjam')
        ->and($peminjaman->diserahkan_oleh_user_id)->toBe($user->id)
        ->and($peminjaman->nomor)->not->toBeEmpty()
        ->and($aset->fresh()->status_ketersediaan)->toBe('dipinjam');
});

it('rejects second active peminjaman on same aset', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9003']);
    $svc = app(BuatPeminjamanAset::class);
    $svc->handle($aset, $user, $user->id, null, now(), null, null);

    expect(fn () => $svc->handle($aset->fresh(), $user, $user->id, null, now(), null, null))
        ->toThrow(ValidationException::class);
});

it('returns peminjaman and restores tersedia', function () {
    $user = User::factory()->create();
    $penerima = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9004']);
    $pinjam = app(BuatPeminjamanAset::class)->handle($aset, $user, null, '198001012000011001', now(), null, null);

    $kembali = app(KembalikanPeminjamanAset::class)->handle($pinjam, $penerima, 'Baik');

    expect($kembali->status)->toBe('dikembalikan')
        ->and($kembali->diterima_kembali_oleh_user_id)->toBe($penerima->id)
        ->and($aset->fresh()->status_ketersediaan)->toBe('tersedia');
});

it('scopes terlambat when rencana date passed', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9002']);
    $pinjam = app(BuatPeminjamanAset::class)->handle(
        $aset,
        $user,
        $user->id,
        null,
        now()->subDays(5),
        now()->subDay()->toDateString(),
        null
    );

    expect($pinjam->isTerlambat())->toBeTrue()
        ->and(AsetPeminjaman::query()->terlambat()->pluck('id')->all())->toContain($pinjam->id);
});

it('can store peminjaman via http', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9010']);

    actingAs($user)
        ->post(route('aset-peminjaman.store'), [
            'aset_id' => $aset->id,
            'peminjam_user_id' => $user->id,
            'tanggal_pinjam' => now()->toDateTimeString(),
            'tanggal_kembali_rencana' => now()->addWeek()->toDateString(),
            'catatan' => 'HTTP pinjam',
        ])
        ->assertRedirect();

    expect($aset->fresh()->status_ketersediaan)->toBe('dipinjam');
});

it('can kembalikan via http', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9011']);
    $pinjam = app(BuatPeminjamanAset::class)->handle($aset, $user, $user->id, null, now(), null, null);

    actingAs($user)
        ->post(route('aset-peminjaman.kembalikan', $pinjam), [
            'kondisi_kembali' => 'OK',
        ])
        ->assertRedirect();

    expect($aset->fresh()->status_ketersediaan)->toBe('tersedia');
});

it('shows peminjaman detail with bound model id', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9012']);
    $pinjam = app(BuatPeminjamanAset::class)->handle($aset, $user, $user->id, null, now(), null, 'Catatan show');

    actingAs($user)
        ->get(route('aset-peminjaman.show', $pinjam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('aset-peminjaman/show')
            ->where('peminjaman.id', $pinjam->id)
            ->where('peminjaman.nomor', $pinjam->nomor)
            ->where('peminjaman.aset.kode_aset', 'INV-IGD-2026-9012')
            ->where('peminjaman.peminjam_label', $user->name)
            ->where('peminjaman.catatan', 'Catatan show'));
});

it('prints peminjaman bukti with real id', function () {
    $user = User::factory()->create();
    $aset = seedAsetAktifUntukPeminjaman(['kode_aset' => 'INV-IGD-2026-9013']);
    $pinjam = app(BuatPeminjamanAset::class)->handle($aset, $user, $user->id, null, now(), null, null);

    actingAs($user)
        ->get(route('aset-peminjaman.print', $pinjam))
        ->assertOk()
        ->assertSee($pinjam->nomor, false)
        ->assertSee('INV-IGD-2026-9013', false);
});
