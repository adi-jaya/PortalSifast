<?php

use App\Models\Aset;
use App\Models\AsetAspakAlat;
use App\Models\AsetBarang;
use App\Models\AsetJenis;
use App\Models\AsetMerk;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('imports aspak from aspak_new_alat compatible csv headers', function () {
    $path = storage_path('app/test-import-aspak-new.csv');
    file_put_contents(
        $path,
        "id_alat,alat_name,alat_code,parent_id,alat_ket,alat_path,sinonim,kode,filter,wajibkalibrasi,durasi,code_aspak\n".
        "1549,Obstetric & Gynecological Devices,21101,0,,, ,21101,0,0,700,21101\n".
        "4083,Laparoscopic insufflator,21101018,1549,,, ,21101018,0,1,365,21101018\n"
    );

    Artisan::call('aset:import-master', ['file' => $path, '--tipe' => 'aspak']);

    $parent = AsetAspakAlat::query()->where('id_alat_aspak', '1549')->first();
    $leaf = AsetAspakAlat::query()->where('id_alat_aspak', '4083')->first();

    expect($parent)->not->toBeNull()
        ->and($parent->parent_id)->toBeNull()
        ->and($leaf)->not->toBeNull()
        ->and($leaf->parent_id)->toBe($parent->id)
        ->and($leaf->wajib_kalibrasi)->toBeTrue()
        ->and($leaf->durasi_kalibrasi_hari)->toBe(365)
        ->and($leaf->isLeaf())->toBeTrue()
        ->and($parent->isLeaf())->toBeFalse();

    @unlink($path);
});

it('creates medis aset from aspak leaf with merk and jenis', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'USG01',
        'nama_ruang' => 'USG',
    ]);
    $merk = AsetMerk::query()->create([
        'kode_merk' => 'GE',
        'nama_merk' => 'GE Healthcare',
    ]);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'VOL',
        'nama_jenis' => 'Voluson',
        'aset_merk_id' => $merk->id,
    ]);
    $leaf = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '5001',
        'nama_alat' => 'Ultrasound system',
        'kode' => '31101001',
        'wajib_kalibrasi' => true,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'aset_merk_id' => $merk->id,
            'aset_jenis_id' => $jenis->id,
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();

    $barang = AsetBarang::query()->where('aset_aspak_alat_id', $leaf->id)->first();
    expect($barang)->not->toBeNull()
        ->and($barang->aset_merk_id)->toBe($merk->id)
        ->and($barang->aset_jenis_id)->toBe($jenis->id);
});

it('rejects medis create when jenis does not match merk', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'RAD01',
        'nama_ruang' => 'Radiologi',
    ]);
    $ge = AsetMerk::query()->create(['kode_merk' => 'GE2', 'nama_merk' => 'GE']);
    $philips = AsetMerk::query()->create(['kode_merk' => 'PHI', 'nama_merk' => 'Philips']);
    $jenis = AsetJenis::query()->create([
        'kode_jenis' => 'CT1',
        'nama_jenis' => 'Brilliance CT',
        'aset_merk_id' => $philips->id,
    ]);
    $leaf = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '5002',
        'nama_alat' => 'CT Scanner',
        'kode' => '31102001',
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'aset_merk_id' => $ge->id,
            'aset_jenis_id' => $jenis->id,
            'jumlah_unit' => 1,
        ])
        ->assertSessionHasErrors('aset_jenis_id');
});

it('creates medis aset from aspak leaf and reuses barang', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'OK01',
        'nama_ruang' => 'OK',
    ]);
    $parent = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '1549',
        'nama_alat' => 'Obstetric Devices',
        'kode' => '21101',
    ]);
    $leaf = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '4083',
        'nama_alat' => 'Laparoscopic insufflator',
        'kode' => '21101018',
        'parent_id' => $parent->id,
        'wajib_kalibrasi' => true,
        'durasi_kalibrasi_hari' => 365,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();

    $barang = AsetBarang::query()->where('aset_aspak_alat_id', $leaf->id)->first();
    expect($barang)->not->toBeNull()
        ->and($barang->kelas_aset)->toBe('medis')
        ->and($barang->wajib_kalibrasi)->toBeTrue()
        ->and($barang->nama_barang)->toBe('Laparoscopic insufflator');

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $leaf->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
            'jumlah_unit' => 1,
        ])
        ->assertRedirect();

    expect(AsetBarang::query()->where('aset_aspak_alat_id', $leaf->id)->count())->toBe(1)
        ->and(Aset::query()->count())->toBe(2);
});

it('rejects medis create without aspak leaf', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD',
        'nama_ruang' => 'IGD',
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'nama_barang' => 'Infus Pump Manual',
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
        ])
        ->assertSessionHasErrors('aset_aspak_alat_id');
});

it('rejects aspak folder parent selection', function () {
    $user = User::factory()->create();
    $ruang = AsetRuang::query()->create([
        'kode_ruang' => 'IGD',
        'nama_ruang' => 'IGD',
    ]);
    $parent = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '100',
        'nama_alat' => 'Folder',
        'kode' => '10',
    ]);
    AsetAspakAlat::query()->create([
        'id_alat_aspak' => '110',
        'nama_alat' => 'Leaf',
        'kode' => '10.01',
        'parent_id' => $parent->id,
    ]);

    actingAs($user)
        ->post('/aset', [
            'kelas_aset' => 'medis',
            'aset_aspak_alat_id' => $parent->id,
            'aset_ruang_id' => $ruang->id,
            'tahun_registrasi' => 2026,
        ])
        ->assertSessionHasErrors('aset_aspak_alat_id');
});

it('searches aspak leaves only', function () {
    $user = User::factory()->create();
    $parent = AsetAspakAlat::query()->create([
        'id_alat_aspak' => '200',
        'nama_alat' => 'Folder Keratoscope',
        'kode' => '01',
    ]);
    AsetAspakAlat::query()->create([
        'id_alat_aspak' => '4071',
        'nama_alat' => 'Keratoscope',
        'kode' => '01201020',
        'parent_id' => $parent->id,
    ]);

    actingAs($user)
        ->getJson('/aset/master/aspak/search?q=Keratoscope')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonPath('0.nama_alat', 'Keratoscope')
        ->assertJsonMissing(['nama_alat' => 'Folder Keratoscope']);
});
