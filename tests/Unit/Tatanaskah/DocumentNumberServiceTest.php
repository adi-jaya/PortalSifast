<?php

use App\Models\KodeSifatNaskah;
use App\Models\KodeUnitKlasifikasi;
use App\Models\KonfigurasiJenisDokumen;
use App\Services\Tatanaskah\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\TatanaskahSeeder::class);
});

it('generates formatted document number with incrementing counter per unit and year', function () {
    $service = app(DocumentNumberService::class);
    $jenis = KonfigurasiJenisDokumen::query()->where('kode', 'SPO')->firstOrFail();
    $unit = KodeUnitKlasifikasi::query()->where('kode', 'III.6.AU')->firstOrFail();
    $sifat = KodeSifatNaskah::query()->where('kode', 'I')->firstOrFail();
    $tanggal = Carbon::create(2026, 6, 19);

    $first = $service->generate($jenis, $unit, $sifat, $tanggal);
    $second = $service->generate($jenis, $unit, $sifat, $tanggal);

    expect($first)->toBe("RS'ASF/001/III.6.AU/I/VI/2026")
        ->and($second)->toBe("RS'ASF/002/III.6.AU/I/VI/2026");
});

it('returns roman month helper', function () {
    expect(DocumentNumberService::romanMonth(6))->toBe('VI')
        ->and(DocumentNumberService::romanMonth(12))->toBe('XII');
});
