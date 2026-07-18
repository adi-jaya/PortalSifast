<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetDokumen;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

function buatAsetDenganBarang(string $kode = 'INV-DOC-2026-0001'): Aset
{
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'DOC', 'nama_ruang' => 'Ruang Dokumen']);
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'BDOC1',
        'nama_barang' => 'Mini Komputer',
    ]);

    return Aset::query()->create([
        'kode_aset' => $kode,
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'DOC',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_ketersediaan' => 'tersedia',
    ]);
}

it('uploads dokumen to unit scope', function () {
    $user = User::factory()->create();
    $aset = buatAsetDenganBarang();

    actingAs($user)
        ->post("/aset/{$aset->kode_aset}/dokumen", [
            'file' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
            'tipe' => 'kontrak',
            'lingkup' => 'unit',
            'judul' => 'Kontrak pengadaan',
        ])
        ->assertRedirect();

    $dokumen = AsetDokumen::query()->first();
    expect($dokumen)->not->toBeNull()
        ->and($dokumen->lingkup)->toBe('unit')
        ->and($dokumen->aset_id)->toBe($aset->id)
        ->and($dokumen->aset_barang_id)->toBeNull()
        ->and($dokumen->tipe)->toBe('kontrak')
        ->and($dokumen->judul)->toBe('Kontrak pengadaan');

    Storage::disk('public')->assertExists($dokumen->path);
});

it('uploads dokumen to barang scope and shows on sibling units', function () {
    $user = User::factory()->create();
    $asetA = buatAsetDenganBarang('INV-DOC-2026-0001');
    $asetB = Aset::query()->create([
        'kode_aset' => 'INV-DOC-2026-0002',
        'aset_barang_id' => $asetA->aset_barang_id,
        'aset_ruang_id' => $asetA->aset_ruang_id,
        'kode_ruang_registrasi' => 'DOC',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_ketersediaan' => 'tersedia',
    ]);

    actingAs($user)
        ->post("/aset/{$asetA->kode_aset}/dokumen", [
            'file' => UploadedFile::fake()->create('manual.pdf', 80, 'application/pdf'),
            'tipe' => 'manual',
            'lingkup' => 'barang',
        ])
        ->assertRedirect();

    $dokumen = AsetDokumen::query()->first();
    expect($dokumen->lingkup)->toBe('barang')
        ->and($dokumen->aset_id)->toBeNull()
        ->and($dokumen->aset_barang_id)->toBe($asetA->aset_barang_id);

    actingAs($user)
        ->get("/aset/{$asetB->kode_aset}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('aset/show')
            ->has('dokumen', 1)
            ->where('dokumen.0.lingkup', 'barang')
            ->where('dokumen.0.tipe', 'manual'));
});

it('rejects barang lingkup when aset has no barang', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'X', 'nama_ruang' => 'X']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-DOC-2026-0099',
        'aset_barang_id' => null,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'X',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->post("/aset/{$aset->kode_aset}/dokumen", [
            'file' => UploadedFile::fake()->create('manual.pdf', 40, 'application/pdf'),
            'tipe' => 'manual',
            'lingkup' => 'barang',
        ])
        ->assertSessionHasErrors('lingkup');
});

it('downloads and deletes dokumen', function () {
    $user = User::factory()->create();
    $aset = buatAsetDenganBarang();

    actingAs($user)
        ->post("/aset/{$aset->kode_aset}/dokumen", [
            'file' => UploadedFile::fake()->create('ba.pdf', 50, 'application/pdf'),
            'tipe' => 'ba_penerimaan',
            'lingkup' => 'unit',
        ])
        ->assertRedirect();

    $dokumen = AsetDokumen::query()->first();

    actingAs($user)
        ->get("/aset/{$aset->kode_aset}/dokumen/{$dokumen->id}/unduh")
        ->assertSuccessful();

    actingAs($user)
        ->delete("/aset/{$aset->kode_aset}/dokumen/{$dokumen->id}")
        ->assertRedirect();

    expect(AsetDokumen::query()->count())->toBe(0);
});
