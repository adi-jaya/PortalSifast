<?php

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeUnitImportCsv(array $rows): UploadedFile
{
    $headers = [
        'kelas_aset',
        'kode_non_alkes',
        'kode_aspak',
        'kode_ruang',
        'tahun_registrasi',
        'no_seri',
        'nama_barang',
        'nama_merk',
        'nama_tipe',
        'harga',
        'asal_barang',
        'tanggal_pengadaan',
        'status_fungsi',
        'tingkat_kerusakan',
    ];

    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, $headers);
    foreach ($rows as $row) {
        fputcsv($fh, $row);
    }
    rewind($fh);
    $content = stream_get_contents($fh);
    fclose($fh);

    return UploadedFile::fake()->createWithContent('import-aset.csv', $content);
}

it('downloads unit import csv template', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->get(route('aset.import.template'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('template-import-aset-unit.csv');

    $csv = $response->streamedContent();

    expect($csv)->toContain('kelas_aset')
        ->and($csv)->toContain('kode_ruang')
        ->and($csv)->toContain('10.02.002');
});

it('previews errors when kode_non_alkes missing for non_medis', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);

    $file = makeUnitImportCsv([
        ['non_medis', '', '', 'IGD01', '2026', 'SN1', 'Laptop', '', '', '', '', '', '', ''],
    ]);

    actingAs($user)
        ->post(route('aset.import.preview'), ['file' => $file])
        ->assertRedirect(route('aset.import'));

    $preview = session(App\Services\Inventaris\ImportAsetUnitCsv::SESSION_KEY);
    expect($preview)->not->toBeNull()
        ->and($preview['error_count'])->toBe(1)
        ->and($preview['rows'][0]['ok'])->toBeFalse();
});

it('previews error when kode_ruang unknown', function () {
    $user = User::factory()->create();
    AsetNonAlkes::query()->create([
        'id_alat' => '1002002',
        'nama_alat' => 'Laptop',
        'kode' => '10.02.002',
        'alat_code' => '10.02.002',
        'deleted' => false,
    ]);

    $file = makeUnitImportCsv([
        ['non_medis', '10.02.002', '', 'ZZZ99', '2026', 'SN1', 'Laptop', '', '', '', '', '', '', ''],
    ]);

    actingAs($user)
        ->post(route('aset.import.preview'), ['file' => $file])
        ->assertRedirect(route('aset.import'));

    $preview = session(App\Services\Inventaris\ImportAsetUnitCsv::SESSION_KEY);
    expect($preview['error_count'])->toBe(1)
        ->and(collect($preview['rows'][0]['errors'])->implode(' '))->toContain('kode_ruang');
});

it('rejects confirm while preview still has errors', function () {
    $user = User::factory()->create();
    session([
        App\Services\Inventaris\ImportAsetUnitCsv::SESSION_KEY => [
            'token' => 'tok-1',
            'error_count' => 1,
            'ok_count' => 0,
            'rows' => [],
            'payloads' => [],
        ],
    ]);

    actingAs($user)
        ->from(route('aset.import'))
        ->post(route('aset.import.store'), ['token' => 'tok-1'])
        ->assertRedirect(route('aset.import'))
        ->assertSessionHasErrors('file');
});

it('imports two units different ruang same catalog after clean preview', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    AsetRuang::query()->create(['kode_ruang' => 'POL01', 'nama_ruang' => 'Poli']);
    AsetNonAlkes::query()->create([
        'id_alat' => '1002002',
        'nama_alat' => 'Laptop',
        'kode' => '10.02.002',
        'alat_code' => '10.02.002',
        'deleted' => false,
    ]);

    $file = makeUnitImportCsv([
        ['non_medis', '10.02.002', '', 'IGD01', '2026', 'SN-IGD-01', 'Lenovo Ideapad 3', 'Lenovo', 'Ideapad 3', '6000000', 'Beli', '2026-01-15', 'berfungsi', 'baik'],
        ['non_medis', '10.02.002', '', 'POL01', '2026', 'SN-POL-01', 'Lenovo Ideapad 3', 'Lenovo', 'Ideapad 3', '6000000', 'Beli', '2026-01-15', 'berfungsi', 'baik'],
    ]);

    actingAs($user)
        ->post(route('aset.import.preview'), ['file' => $file])
        ->assertRedirect(route('aset.import'));

    $preview = session(App\Services\Inventaris\ImportAsetUnitCsv::SESSION_KEY);
    expect($preview['error_count'])->toBe(0)->and($preview['ok_count'])->toBe(2);

    actingAs($user)
        ->post(route('aset.import.store'), ['token' => $preview['token']])
        ->assertRedirect();

    $asets = Aset::query()->orderBy('id')->get();
    expect($asets)->toHaveCount(2)
        ->and(AsetBarang::query()->count())->toBe(1)
        ->and($asets[0]->no_seri)->toBe('SN-IGD-01')
        ->and($asets[1]->no_seri)->toBe('SN-POL-01')
        ->and($asets[0]->aset_barang_id)->toBe($asets[1]->aset_barang_id)
        ->and($asets[0]->kode_aset)->toBe('INV-IGD01-2026-0001')
        ->and($asets[1]->kode_aset)->toBe('INV-POL01-2026-0001');
});

it('flags duplicate serial within the csv file', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);
    AsetNonAlkes::query()->create([
        'id_alat' => '1002002',
        'nama_alat' => 'Laptop',
        'kode' => '10.02.002',
        'alat_code' => '10.02.002',
        'deleted' => false,
    ]);

    $file = makeUnitImportCsv([
        ['non_medis', '10.02.002', '', 'IGD01', '2026', 'SN-DUP', 'A', '', '', '', '', '', '', ''],
        ['non_medis', '10.02.002', '', 'IGD01', '2026', 'SN-DUP', 'B', '', '', '', '', '', '', ''],
    ]);

    actingAs($user)
        ->post(route('aset.import.preview'), ['file' => $file])
        ->assertRedirect(route('aset.import'));

    $preview = session(App\Services\Inventaris\ImportAsetUnitCsv::SESSION_KEY);
    expect($preview['error_count'])->toBe(1)
        ->and($preview['rows'][1]['ok'])->toBeFalse();
});
