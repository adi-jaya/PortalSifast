<?php

use App\Models\AsetAspakAlat;
use App\Models\AsetNonAlkes;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function masterCsvFile(array $headers, array $rows): UploadedFile
{
    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, $headers);
    foreach ($rows as $row) {
        fputcsv($fh, $row);
    }
    rewind($fh);
    $content = stream_get_contents($fh);
    fclose($fh);

    return UploadedFile::fake()->createWithContent('master.csv', $content);
}

it('shows master ruang page', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);

    actingAs($user)
        ->get(route('aset.master.ruang.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('aset/master-ruang/index')
            ->has('items.data', 1));
});

it('imports ruang via ui csv upsert', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD Lama']);

    $file = masterCsvFile(
        ['kode_ruang', 'nama_ruang'],
        [
            ['IGD01', 'IGD Baru'],
            ['POL01', 'Poli'],
        ],
    );

    actingAs($user)
        ->post(route('aset.master.csv.import', ['tipe' => 'ruang']), ['file' => $file])
        ->assertRedirect(route('aset.master.ruang.index'))
        ->assertSessionHas('success');

    expect(AsetRuang::query()->count())->toBe(2)
        ->and(AsetRuang::query()->where('kode_ruang', 'IGD01')->value('nama_ruang'))->toBe('IGD Baru')
        ->and(AsetRuang::query()->where('kode_ruang', 'POL01')->exists())->toBeTrue();
});

it('exports ruang csv with kode_ruang header', function () {
    $user = User::factory()->create();
    AsetRuang::query()->create(['kode_ruang' => 'IGD01', 'nama_ruang' => 'IGD']);

    $response = actingAs($user)->get(route('aset.master.csv.export', ['tipe' => 'ruang']));
    $response->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain('kode_ruang')
        ->and($csv)->toContain('IGD01')
        ->and($csv)->toContain('IGD');
});

it('imports aspak via ui and keeps artisan path working', function () {
    $user = User::factory()->create();
    $file = masterCsvFile(
        ['id_alat_aspak', 'nama_alat', 'kode', 'alat_code', 'parent_id_alat_aspak', 'wajib_kalibrasi'],
        [
            ['9', 'Parent Aspak', 'P1', 'P1', '', '0'],
            ['91', 'Leaf Aspak', 'L1', 'L1', '9', '1'],
        ],
    );

    actingAs($user)
        ->post(route('aset.master.csv.import', ['tipe' => 'aspak']), ['file' => $file])
        ->assertRedirect(route('aset.master.aspak.index'));

    $leaf = AsetAspakAlat::query()->where('id_alat_aspak', '91')->first();
    $parent = AsetAspakAlat::query()->where('id_alat_aspak', '9')->first();

    expect($leaf)->not->toBeNull()
        ->and($parent)->not->toBeNull()
        ->and($leaf->parent_id)->toBe($parent->id)
        ->and($leaf->isLeaf())->toBeTrue();
});

it('imports non_alkes via ui csv', function () {
    $user = User::factory()->create();
    $file = masterCsvFile(
        ['id_alat', 'alat_name', 'alat_code', 'parent_id', 'level', 'kode', 'deleted'],
        [
            ['200', 'Folder', '10', '0', '1', '10', '0'],
            ['201', 'Laptop', '10.02.002', '200', '2', '10.02.002', '0'],
        ],
    );

    actingAs($user)
        ->post(route('aset.master.csv.import', ['tipe' => 'non_alkes']), ['file' => $file])
        ->assertRedirect(route('aset.master.non-alkes.index'));

    $leaf = AsetNonAlkes::query()->where('id_alat', '201')->first();
    expect($leaf)->not->toBeNull()
        ->and($leaf->kode)->toBe('10.02.002')
        ->and($leaf->isLeaf())->toBeTrue();
});

it('downloads aspak template', function () {
    $user = User::factory()->create();
    $response = actingAs($user)->get(route('aset.master.csv.template', ['tipe' => 'aspak']));
    $response->assertOk();
    expect($response->streamedContent())->toContain('id_alat_aspak');
});
