<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRuang;
use App\Models\User;
use App\Services\Inventaris\BuatMutasiLokasiAset;
use App\Services\Inventaris\BuatPeminjamanAset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function seedAsetAktifUntukMutasi(array $overrides = []): Aset
{
    $ruang = AsetRuang::query()->firstOrCreate(
        ['kode_ruang' => 'IGD'],
        ['nama_ruang' => 'IGD']
    );
    $barang = AsetBarang::query()->firstOrCreate(
        ['kode_barang' => 'BRG-MUT'],
        ['nama_barang' => 'Alat Mutasi Test', 'kelas_aset' => 'medis']
    );

    return Aset::query()->create(array_merge([
        'kode_aset' => 'INV-IGD-2026-9100',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
    ], $overrides));
}

it('mutates ruang and stores history', function () {
    $user = User::factory()->create();
    $asal = AsetRuang::query()->firstOrCreate(['kode_ruang' => 'IGD'], ['nama_ruang' => 'IGD']);
    $tujuan = AsetRuang::query()->firstOrCreate(['kode_ruang' => 'OK1'], ['nama_ruang' => 'OK']);
    $aset = seedAsetAktifUntukMutasi(['aset_ruang_id' => $asal->id, 'kode_aset' => 'INV-IGD-2026-9101']);

    $mutasi = app(BuatMutasiLokasiAset::class)->handle(
        aset: $aset,
        actor: $user,
        ruangTujuanId: $tujuan->id,
        penerimaUserId: $user->id,
        penerimaNik: null,
        tanggalMutasi: now(),
        catatan: 'Pindah OK',
    );

    expect($mutasi->aset_ruang_asal_id)->toBe($asal->id)
        ->and($mutasi->aset_ruang_tujuan_id)->toBe($tujuan->id)
        ->and($aset->fresh()->aset_ruang_id)->toBe($tujuan->id)
        ->and($aset->fresh()->status_ketersediaan)->toBe('tersedia');
});

it('rejects mutasi when aset dipinjam', function () {
    $user = User::factory()->create();
    $tujuan = AsetRuang::query()->firstOrCreate(['kode_ruang' => 'OK2'], ['nama_ruang' => 'OK2']);
    $aset = seedAsetAktifUntukMutasi(['kode_aset' => 'INV-IGD-2026-9102']);
    app(BuatPeminjamanAset::class)->handle($aset, $user, $user->id, null, now(), null, null);

    expect(fn () => app(BuatMutasiLokasiAset::class)->handle(
        $aset->fresh(),
        $user,
        $tujuan->id,
        $user->id,
        null,
        now(),
        null
    ))->toThrow(ValidationException::class);
});

it('can store mutasi via http', function () {
    $user = User::factory()->create();
    $asal = AsetRuang::query()->firstOrCreate(['kode_ruang' => 'IGD'], ['nama_ruang' => 'IGD']);
    $tujuan = AsetRuang::query()->firstOrCreate(['kode_ruang' => 'OK3'], ['nama_ruang' => 'OK3']);
    $aset = seedAsetAktifUntukMutasi(['aset_ruang_id' => $asal->id, 'kode_aset' => 'INV-IGD-2026-9103']);

    actingAs($user)
        ->post(route('aset-mutasi-lokasi.store'), [
            'aset_id' => $aset->id,
            'aset_ruang_tujuan_id' => $tujuan->id,
            'penerima_user_id' => $user->id,
            'tanggal_mutasi' => now()->toDateTimeString(),
            'catatan' => 'HTTP mutasi',
        ])
        ->assertRedirect();

    expect($aset->fresh()->aset_ruang_id)->toBe($tujuan->id);
});
