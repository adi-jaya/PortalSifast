<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetRiwayat;
use App\Models\AsetRuang;
use App\Models\AuditAset;
use App\Models\AuditAsetItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function buatAsetDiRuang(AsetRuang $ruang, string $kode, array $overrides = []): Aset
{
    $barang = AsetBarang::query()->create([
        'kode_barang' => 'BRG'.fake()->unique()->numerify('####'),
        'nama_barang' => 'Barang Uji',
        'kelas_aset' => 'non_medis',
        'wajib_kalibrasi' => false,
    ]);

    return Aset::query()->create(array_merge([
        'kode_aset' => $kode,
        'aset_barang_id' => $barang->id,
        'kode_ruang_registrasi' => $ruang->kode_ruang,
        'aset_ruang_id' => $ruang->id,
        'tahun_registrasi' => 2024,
        'kondisi' => 'Ada',
        'siklus_hidup' => 'aktif',
    ], $overrides));
}

it('membuat sesi audit dan mengisi checklist dari aset ruang', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    buatAsetDiRuang($ruang, 'INV-IGD01-2024-0001');
    buatAsetDiRuang($ruang, 'INV-IGD01-2024-0002');

    actingAs($user)
        ->post('/aset/audit', ['aset_ruang_id' => $ruang->id])
        ->assertRedirect();

    $audit = AuditAset::query()->first();
    expect($audit)->not->toBeNull()
        ->and($audit->status)->toBe('berjalan')
        ->and($audit->items()->count())->toBe(2);
});

it('menandai item via scan kode aset', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $aset = buatAsetDiRuang($ruang, 'INV-IGD01-2024-0001');

    $audit = AuditAset::query()->create([
        'aset_ruang_id' => $ruang->id,
        'judul' => 'Audit test',
        'status' => 'berjalan',
        'dimulai_oleh' => $user->id,
        'dimulai_pada' => now(),
    ]);
    AuditAsetItem::query()->create([
        'audit_aset_id' => $audit->id,
        'aset_id' => $aset->id,
        'kode_aset' => $aset->kode_aset,
    ]);

    actingAs($user)
        ->post("/aset/audit/{$audit->id}/scan", [
            'kode_aset' => 'INV-IGD01-2024-0001',
            'hasil' => 'ditemukan',
            'kondisi_aktual' => 'Ada',
        ])
        ->assertRedirect();

    expect($audit->items()->first()->hasil)->toBe('ditemukan')
        ->and($audit->items()->first()->dicek_oleh)->toBe($user->id);
});

it('menolak menyelesaikan audit jika masih ada item belum dicek tanpa paksa', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $aset = buatAsetDiRuang($ruang, 'INV-IGD01-2024-0001');
    $audit = AuditAset::query()->create([
        'aset_ruang_id' => $ruang->id,
        'status' => 'berjalan',
        'dimulai_oleh' => $user->id,
        'dimulai_pada' => now(),
    ]);
    AuditAsetItem::query()->create([
        'audit_aset_id' => $audit->id,
        'aset_id' => $aset->id,
        'kode_aset' => $aset->kode_aset,
    ]);

    actingAs($user)
        ->post("/aset/audit/{$audit->id}/selesai")
        ->assertSessionHasErrors('status');

    expect($audit->fresh()->status)->toBe('berjalan');
});

it('menyetujui audit dan menerapkan koreksi hilang serta salah ruang tanpa tulis SIMRS', function () {
    $user = User::factory()->create();
    $ruangA = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $ruangB = AsetRuang::query()->create(['kode_ruang' => 'OK01', 'nama_ruang' => 'OK']);

    $hilang = buatAsetDiRuang($ruangA, 'INV-IGD01-2024-0001');
    $salah = buatAsetDiRuang($ruangA, 'INV-IGD01-2024-0002');
    $ada = buatAsetDiRuang($ruangA, 'INV-IGD01-2024-0003', ['kondisi' => 'Rusak']);

    $audit = AuditAset::query()->create([
        'aset_ruang_id' => $ruangA->id,
        'status' => 'selesai',
        'dimulai_oleh' => $user->id,
        'dimulai_pada' => now(),
        'selesai_pada' => now(),
    ]);

    AuditAsetItem::query()->create([
        'audit_aset_id' => $audit->id,
        'aset_id' => $hilang->id,
        'kode_aset' => $hilang->kode_aset,
        'hasil' => 'tidak_ditemukan',
        'dicek_oleh' => $user->id,
        'dicek_pada' => now(),
    ]);
    AuditAsetItem::query()->create([
        'audit_aset_id' => $audit->id,
        'aset_id' => $salah->id,
        'kode_aset' => $salah->kode_aset,
        'hasil' => 'salah_ruang',
        'aset_ruang_ditemukan_id' => $ruangB->id,
        'dicek_oleh' => $user->id,
        'dicek_pada' => now(),
    ]);
    AuditAsetItem::query()->create([
        'audit_aset_id' => $audit->id,
        'aset_id' => $ada->id,
        'kode_aset' => $ada->kode_aset,
        'hasil' => 'ditemukan',
        'kondisi_aktual' => 'Ada',
        'dicek_oleh' => $user->id,
        'dicek_pada' => now(),
    ]);

    actingAs($user)
        ->post("/aset/audit/{$audit->id}/setujui")
        ->assertRedirect();

    expect($audit->fresh()->status)->toBe('disetujui')
        ->and($hilang->fresh()->siklus_hidup)->toBe('hilang')
        ->and($hilang->fresh()->kondisi)->toBe('Hilang')
        ->and($salah->fresh()->aset_ruang_id)->toBe($ruangB->id)
        ->and($salah->fresh()->kode_aset)->toBe('INV-IGD01-2024-0002')
        ->and($ada->fresh()->kondisi)->toBe('Ada');

    expect(AsetRiwayat::query()->where('jenis_peristiwa', 'audit_disetujui')->count())->toBe(3);
});

it('menampilkan daftar dan detail audit', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $audit = AuditAset::query()->create([
        'aset_ruang_id' => $ruang->id,
        'judul' => 'Audit IGD',
        'status' => 'berjalan',
        'dimulai_oleh' => $user->id,
        'dimulai_pada' => now(),
    ]);

    actingAs($user)
        ->get('/aset/audit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('aset/audit/index'));

    actingAs($user)
        ->get("/aset/audit/{$audit->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('aset/audit/show')
            ->where('audit.id', $audit->id));
});
