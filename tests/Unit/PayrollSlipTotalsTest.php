<?php

use App\Models\EmployeeSalary;
use App\Support\PayrollSlipStructure;
use Tests\TestCase;

uses(TestCase::class);

test('resolve jumlah totals when csv columns use swapped rs export labels', function () {
    $salary = EmployeeSalary::make([
        'gaji_pokok' => '1475300',
        'keluarga' => '221295',
        'tunj_masa_kerja' => '885914',
        'tunj_kehadiran' => '570000',
        'tunj_makan' => '380000',
        'fungsional' => '1500000',
        'jumlah' => '6256817',
        'jumlah_tunjangan' => '7732117',
    ]);

    $totals = PayrollSlipStructure::computeTotals($salary);

    expect($totals['jumlah_tunjangan'])->toBe(6_256_817.0)
        ->and($totals['jumlah_gaji'])->toBe(7_732_117.0);
});

test('resolve jumlah totals when csv columns use standard labels', function () {
    $salary = EmployeeSalary::make([
        'gaji_pokok' => '1759050',
        'keluarga' => '263858',
        'tunj_masa_kerja' => '1197489',
        'tunj_kehadiran' => '660000',
        'tunj_makan' => '440000',
        'fungsional' => '2400000',
        'struktural' => '2400000',
        'operasional' => '2000000',
        'tunj_bpjs_tk' => '336942',
        'bpjs_kes' => '257574',
        'transport_spj' => '230000',
        'jm_dokter' => '2108399',
        'jkn' => '853882',
        'umum' => '332075',
        'jkn_susulan' => '14765',
        'jkn_susulan_l' => '39297',
        'jumlah' => '15293330',
        'jumlah_tunjangan' => '13534281',
    ]);

    $totals = PayrollSlipStructure::computeTotals($salary);

    expect($totals['jumlah_tunjangan'])->toBe(13_534_281.0)
        ->and($totals['jumlah_gaji'])->toBe(15_293_330.0);
});

test('resolve jumlah totals falls back to computed values when only jumlah csv exists', function () {
    $salary = EmployeeSalary::make([
        'gaji_pokok' => '1759050',
        'keluarga' => '263858',
        'fungsional' => '2400000',
        'jumlah' => '15293330',
    ]);

    $totals = PayrollSlipStructure::computeTotals($salary);

    expect($totals['jumlah_gaji'])->toBe(15_293_330.0)
        ->and($totals['from_csv']['jumlah_gaji'])->toBeTrue()
        ->and($totals['from_csv']['jumlah_tunjangan'])->toBeFalse();
});
