<?php

use App\Enums\DokumenStatus;
use App\Models\Dokumen;
use App\Models\KodeUnitKlasifikasi;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    Storage::fake('local');
    $this->seed(\Database\Seeders\TatanaskahSeeder::class);

    $this->unit = KodeUnitKlasifikasi::query()->where('kode', 'III.6.AU')->firstOrFail();
});

function tatanaskahMinimalPdf(): UploadedFile
{
    $content = '%PDF-1.4 minimal test';

    return UploadedFile::fake()->createWithContent('draft.pdf', $content);
}

function tatanaskahPenandatanganPayload(): array
{
    return [
        'penandatangan_nik' => '99.test.direktur',
        'penandatangan_nama' => 'Direktur RS Test',
        'penandatangan_jabatan' => 'Direktur',
    ];
}

it('allows staff to access tatanaskah index', function () {
    $staff = User::factory()->staff('ADM')->create(['email_verified_at' => now()]);

    $this->actingAs($staff)
        ->get('/tatanaskah/dokumen')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('tatanaskah/dokumen/index'));
});

it('creates draft dokumen with pdf upload', function () {
    $staff = User::factory()->staff('ADM')->create(['email_verified_at' => now()]);

    $response = $this->actingAs($staff)->post('/tatanaskah/dokumen', array_merge([
        'judul' => 'SPO Pelayanan Rawat Jalan',
        'kode_jenis' => 'SPO',
        'kode_unit_klasifikasi_id' => $this->unit->id,
        'kode_sifat' => 'I',
        'nomor_revisi' => '00',
        'file' => tatanaskahMinimalPdf(),
    ], tatanaskahPenandatanganPayload()));

    $dokumen = Dokumen::query()->first();
    expect($dokumen)->not->toBeNull()
        ->and($dokumen->judul)->toBe('SPO Pelayanan Rawat Jalan')
        ->and($dokumen->status)->toBe(DokumenStatus::Draft)
        ->and($dokumen->nomor_dokumen)->toBeNull();

    $response->assertRedirect(route('tatanaskah.dokumen.show', $dokumen));
});

it('assigns nomor when draft is submitted directly to direktur', function () {
    $staff = User::factory()->staff('ADM')->create(['email_verified_at' => now()]);

    $this->actingAs($staff)->post('/tatanaskah/dokumen', array_merge([
        'judul' => 'SPO Test Nomor',
        'kode_jenis' => 'SPO',
        'kode_unit_klasifikasi_id' => $this->unit->id,
        'kode_sifat' => 'I',
        'file' => tatanaskahMinimalPdf(),
    ], tatanaskahPenandatanganPayload()));

    $dokumen = Dokumen::query()->firstOrFail();

    $this->actingAs($staff)->post(route('tatanaskah.dokumen.transition', $dokumen), [
        'status' => DokumenStatus::MenungguTte->value,
    ])->assertRedirect();

    $dokumen->refresh();

    expect($dokumen->status)->toBe(DokumenStatus::MenungguTte)
        ->and($dokumen->nomor_dokumen)->toMatch("/^RS'ASF\/\d{3}\/III\.6\.AU\/I\/[IVX]+\/2026$/")
        ->and($dokumen->tanggal_hijriyah)->not->toBeNull();
});

it('allows legacy review_unit dokumen to skip to menunggu persetujuan direktur', function () {
    $staff = User::factory()->staff('ADM')->create(['email_verified_at' => now()]);

    $this->actingAs($staff)->post('/tatanaskah/dokumen', array_merge([
        'judul' => 'Legacy Review Unit',
        'kode_jenis' => 'SPO',
        'kode_unit_klasifikasi_id' => $this->unit->id,
        'kode_sifat' => 'I',
        'file' => tatanaskahMinimalPdf(),
    ], tatanaskahPenandatanganPayload()));

    $dokumen = Dokumen::query()->firstOrFail();
    $dokumen->update(['status' => DokumenStatus::ReviewUnit]);

    $this->actingAs($staff)->post(route('tatanaskah.dokumen.transition', $dokumen), [
        'status' => DokumenStatus::MenungguTte->value,
    ])->assertRedirect();

    expect($dokumen->fresh()->status)->toBe(DokumenStatus::MenungguTte);
});

it('denies pemohon without tatanaskah flags', function () {
    $pemohon = User::factory()->pemohon()->create(['email_verified_at' => now()]);

    $this->actingAs($pemohon)
        ->get('/tatanaskah/dokumen')
        ->assertForbidden();
});
