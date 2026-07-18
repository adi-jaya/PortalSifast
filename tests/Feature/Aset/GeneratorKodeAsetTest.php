<?php

use App\Services\Inventaris\GeneratorKodeAset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates inv kode with room and year', function () {
    $generator = app(GeneratorKodeAset::class);

    $first = $generator->generate('IGD01', 2019);
    $second = $generator->generate('IGD01', 2019);
    $otherYear = $generator->generate('IGD01', 2026);

    expect($first)->toBe('INV-IGD01-2019-0001')
        ->and($second)->toBe('INV-IGD01-2019-0002')
        ->and($otherYear)->toBe('INV-IGD01-2026-0001');
});

it('uses fallback room code when empty', function () {
    config(['aset.kode_ruang_fallback' => 'TANPA']);

    $kode = app(GeneratorKodeAset::class)->generate('', 2026);

    expect($kode)->toBe('INV-TANPA-2026-0001');
});
