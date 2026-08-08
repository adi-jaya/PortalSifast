<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates single aset with merk jenis and serial', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);
    $merk = AsetMerk::query()->create(['kode_merk' => 'DELL', 'nama_merk' => 'Dell']);
    $jenis = AsetJenis::query()->create(['kode_jenis' => 'LAP', 'nama_jenis' => 'Laptop']);

    actingAs($user)
        ->post('/aset', [
            'nama_barang' => 'Latitude 5440',
            'aset_ruang_id' => $ruang->id,
            'aset_merk_id' => $merk->id,
            'aset_jenis_id' => $jenis->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
            'no_seri_list' => ['SN-001'],
            'harga' => 12000000,
            'status_fungsi' => 'berfungsi',
            'tingkat_kerusakan' => 'baik',
            'kelas_aset' => 'non_medis',
        ])
        ->assertRedirect();

    $aset = Aset::query()->first();
    expect($aset)->not->toBeNull()
        ->and($aset->no_seri)->toBe('SN-001')
        ->and($aset->status_fungsi)->toBe('berfungsi')
        ->and($aset->barang?->merk?->nama_merk)->toBe('Dell')
        ->and($aset->barang?->jenis?->nama_jenis)->toBe('Laptop')
        ->and($aset->barang?->jumlah)->toBe(1);
});

it('creates batch of 3 units with partial serial', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);

    actingAs($user)
        ->post('/aset', [
            'nama_barang' => 'ThinkPad E14',
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 3,
            'no_seri_list' => ['SN-A', 'SN-B', ''],
            'harga' => 10000000,
            'kelas_aset' => 'non_medis',
        ])
        ->assertRedirect();

    expect(Aset::query()->count())->toBe(3)
        ->and(AsetBarang::query()->count())->toBe(1)
        ->and(AsetBarang::query()->first()?->jumlah)->toBe(3)
        ->and(Aset::query()->whereNotNull('no_seri')->count())->toBe(2)
        ->and(Aset::query()->pluck('kode_aset')->sort()->values()->all())
        ->toBe(['INV-IT01-2026-0001', 'INV-IT01-2026-0002', 'INV-IT01-2026-0003']);
});

it('creates batch with different ruang and serial per unit', function () {
    $user = User::factory()->create();
    $igd = AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    $poli = AsetRuang::query()->create(['kode_ruang' => 'POL01', 'nama_ruang' => 'Poli']);
    $merk = AsetMerk::query()->create(['kode_merk' => 'LEN', 'nama_merk' => 'Lenovo']);
    $jenis = AsetJenis::query()->create(['kode_jenis' => 'ID3', 'nama_jenis' => 'Ideapad 3']);

    actingAs($user)
        ->post('/aset', [
            'nama_barang' => 'Lenovo Ideapad 3',
            'aset_merk_id' => $merk->id,
            'aset_jenis_id' => $jenis->id,
            'aset_ruang_id_list' => [$igd->id, $poli->id],
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 2,
            'no_seri_list' => ['SN-IGD-01', 'SN-POL-01'],
            'harga' => 6000000,
            'kelas_aset' => 'non_medis',
            'asal_barang' => 'Beli',
        ])
        ->assertRedirect();

    $asets = Aset::query()->with('barang')->orderBy('id')->get();

    expect($asets)->toHaveCount(2)
        ->and(AsetBarang::query()->count())->toBe(1)
        ->and($asets[0]->aset_ruang_id)->toBe($igd->id)
        ->and($asets[0]->no_seri)->toBe('SN-IGD-01')
        ->and($asets[0]->kode_aset)->toBe('INV-IGD01-2026-0001')
        ->and($asets[1]->aset_ruang_id)->toBe($poli->id)
        ->and($asets[1]->no_seri)->toBe('SN-POL-01')
        ->and($asets[1]->kode_aset)->toBe('INV-POL01-2026-0001')
        ->and($asets[0]->aset_barang_id)->toBe($asets[1]->aset_barang_id)
        ->and($asets[0]->barang?->nama_barang)->toBe('Lenovo Ideapad 3');
});

it('rejects create when ruang list is shorter than jumlah unit', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);

    actingAs($user)
        ->from('/aset/create')
        ->post('/aset', [
            'nama_barang' => 'Monitor',
            'aset_ruang_id_list' => [$ruang->id],
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 2,
            'kelas_aset' => 'non_medis',
        ])
        ->assertRedirect('/aset/create')
        ->assertSessionHasErrors('aset_ruang_id_list');
});

it('rejects duplicate serial across asets', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'L1', 'nama_barang' => 'Laptop']);

    Aset::query()->create([
        'kode_aset' => 'INV-IT01-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT01',
        'tahun_registrasi' => 2026,
        'no_seri' => 'DUP-1',
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->post('/aset', [
            'aset_barang_id' => $barang->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
            'no_seri_list' => ['DUP-1'],
        ])
        ->assertSessionHasErrors('no_seri_list');
});

it('imports master barang from csv', function () {
    $kat = AsetKategori::query()->create(['kode_kategori' => 'K1', 'nama_kategori' => 'Elektronik']);
    $jenis = AsetJenis::query()->create(['kode_jenis' => 'J1', 'nama_jenis' => 'Laptop']);
    $merk = AsetMerk::query()->create(['kode_merk' => 'M1', 'nama_merk' => 'Lenovo']);

    $path = storage_path('app/test-import-barang.csv');
    file_put_contents($path, "kode_barang,nama_barang,kode_kategori,kode_jenis,kode_merk,kelas_aset\nBRG01,Laptop X,K1,J1,M1,non_medis\n");

    Artisan::call('aset:import-master', ['file' => $path, '--tipe' => 'barang']);

    $barang = AsetBarang::query()->where('kode_barang', 'BRG01')->first();
    expect($barang)->not->toBeNull()
        ->and($barang->aset_kategori_id)->toBe($kat->id)
        ->and($barang->aset_jenis_id)->toBe($jenis->id)
        ->and($barang->aset_merk_id)->toBe($merk->id);

    @unlink($path);
});

it('soft deletes aset without removing row permanently', function () {
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IT01', 'nama_ruang' => 'IT']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'B1', 'nama_barang' => 'X']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IT01-2026-0099',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IT01',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    $aset->delete();

    expect(Aset::query()->count())->toBe(0)
        ->and(Aset::withTrashed()->count())->toBe(1);
});

it('user can quick create merk master inline', function () {
    $user = \App\Models\User::factory()->create();

    actingAs($user)
        ->postJson('/aset/master/merk', ['nama' => 'Philips Medical'])
        ->assertCreated()
        ->assertJsonPath('item.nama', 'Philips Medical')
        ->assertJsonPath('created', true);

    expect(AsetMerk::query()->where('nama_merk', 'Philips Medical')->exists())->toBeTrue();
});

it('quick create merk returns existing when name already exists', function () {
    $user = \App\Models\User::factory()->create();
    $merk = AsetMerk::query()->create(['kode_merk' => 'DELL', 'nama_merk' => 'Dell']);

    actingAs($user)
        ->postJson('/aset/master/merk', ['nama' => 'dell'])
        ->assertOk()
        ->assertJsonPath('item.id', $merk->id)
        ->assertJsonPath('created', false);
});

it('user can quick create jenis master inline', function () {
    $user = \App\Models\User::factory()->create();

    actingAs($user)
        ->postJson('/aset/master/jenis', ['nama' => 'Monitor ICU'])
        ->assertCreated()
        ->assertJsonPath('item.nama', 'Monitor ICU');

    expect(AsetJenis::query()->where('nama_jenis', 'Monitor ICU')->exists())->toBeTrue();
});

it('user can quick create distributor master inline', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/aset/master/distributor', ['nama' => 'PT Medika Sejahtera'])
        ->assertCreated()
        ->assertJsonPath('item.nama', 'PT Medika Sejahtera');

    expect(\App\Models\AsetDistributor::query()->where('nama_distributor', 'PT Medika Sejahtera')->exists())->toBeTrue();
});

it('imports aspak with parent hierarchy', function () {
    $path = storage_path('app/test-import-aspak.csv');
    file_put_contents(
        $path,
        "id_alat_aspak,nama_alat,kode,parent_id_alat_aspak,alat_path,sinonim,wajib_kalibrasi,durasi_kalibrasi_hari\n".
        "100,Kelompok Rehabilitasi,14,,,Rehab,0,700\n".
        "110,Elektrode cable,14.01.001,100,/0/100/110/,Electrode,0,700\n"
    );

    Artisan::call('aset:import-master', ['file' => $path, '--tipe' => 'aspak']);

    $parent = \App\Models\AsetAspakAlat::query()->where('id_alat_aspak', '100')->first();
    $child = \App\Models\AsetAspakAlat::query()->where('id_alat_aspak', '110')->first();

    expect($parent)->not->toBeNull()
        ->and($child)->not->toBeNull()
        ->and($child->parent_id)->toBe($parent->id)
        ->and($child->alat_path)->toBe('/0/100/110/');

    @unlink($path);
});

it('updates aset serial and status fungsi from edit', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'IGD', 'nama_ruang' => 'IGD']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'BEDIT', 'nama_barang' => 'Monitor']);
    $aset = Aset::query()->create([
        'kode_aset' => 'INV-IGD-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'IGD',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
        'status_fungsi' => 'berfungsi',
        'tingkat_kerusakan' => 'baik',
    ]);

    actingAs($user)
        ->put("/aset/{$aset->kode_aset}", [
            'aset_barang_id' => $barang->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'no_seri' => 'SN-EDIT-1',
            'status_fungsi' => 'tidak_berfungsi',
            'tingkat_kerusakan' => 'rusak_ringan',
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();

    $aset->refresh();
    expect($aset->no_seri)->toBe('SN-EDIT-1')
        ->and($aset->status_fungsi)->toBe('tidak_berfungsi')
        ->and($aset->tingkat_kerusakan)->toBe('rusak_ringan');
});

it('renders aset index with compact list props', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create(['kode_ruang' => 'LAB', 'nama_ruang' => 'Lab']);
    $barang = AsetBarang::query()->create(['kode_barang' => 'BIDX', 'nama_barang' => 'Infus Pump']);
    Aset::query()->create([
        'kode_aset' => 'INV-LAB-2026-0001',
        'aset_barang_id' => $barang->id,
        'aset_ruang_id' => $ruang->id,
        'kode_ruang_registrasi' => 'LAB',
        'tahun_registrasi' => 2026,
        'siklus_hidup' => 'aktif',
    ]);

    actingAs($user)
        ->get('/aset')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('aset/index')
            ->has('asets.data', 1)
            ->has('stats.total')
            ->where('asets.data.0.kode_aset', 'INV-LAB-2026-0001')
            ->where('asets.data.0.nama_barang', 'Infus Pump'));
});
