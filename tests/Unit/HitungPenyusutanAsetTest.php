<?php

use App\Services\Inventaris\HitungPenyusutanAset;
use Illuminate\Support\Carbon;

it('computes straight-line depreciation after some months', function () {
    $hasil = (new HitungPenyusutanAset)->hitung(
        hargaPerolehan: 6_000_000,
        tanggalMulai: Carbon::parse('2026-01-01'),
        umurManfaatBulan: 60,
        nilaiResidu: 0,
        sampai: Carbon::parse('2027-01-01'),
    );

    expect($hasil['dapat_dihitung'])->toBeTrue()
        ->and($hasil['penyusutan_per_bulan'])->toBe(100_000.0)
        ->and($hasil['bulan_berjalan'])->toBe(12)
        ->and($hasil['akumulasi_penyusutan'])->toBe(1_200_000.0)
        ->and($hasil['nilai_buku'])->toBe(4_800_000.0)
        ->and($hasil['sisa_umur_bulan'])->toBe(48)
        ->and($hasil['persen_sisa'])->toBe(80.0);
});

it('respects residual value so book value never drops below it', function () {
    $hasil = (new HitungPenyusutanAset)->hitung(
        hargaPerolehan: 6_000_000,
        tanggalMulai: Carbon::parse('2020-01-01'),
        umurManfaatBulan: 60,
        nilaiResidu: 500_000,
        sampai: Carbon::parse('2030-01-01'),
    );

    expect($hasil['bulan_berjalan'])->toBe(120)
        ->and($hasil['akumulasi_penyusutan'])->toBe(5_500_000.0)
        ->and($hasil['nilai_buku'])->toBe(500_000.0)
        ->and($hasil['sisa_umur_bulan'])->toBe(0);
});

it('cannot compute without required inputs', function () {
    $hasil = (new HitungPenyusutanAset)->hitung(
        hargaPerolehan: null,
        tanggalMulai: null,
        umurManfaatBulan: null,
    );

    expect($hasil['dapat_dihitung'])->toBeFalse()
        ->and($hasil['nilai_buku'])->toBeNull()
        ->and($hasil['akumulasi_penyusutan'])->toBeNull();
});
