<?php

use App\Support\PayrollCsvMapper;

test('maps payroll csv column aliases to database fields', function () {
    $raw = [
        'gaji_pokok' => '1,475,300',
        'tunjangan_keluarga' => '221,295',
        'tunjangan_masa_kerja' => '885,914',
        'tunjangan_kehadiran' => '570,000',
        'tunjangan_makan_minum' => '380,000',
        'fungsional_profesi' => '1,500,000',
        'jkn_maret_2026' => '520,992',
        'umumapril_2026' => '190,213',
        'jkk' => '14,384',
        'jkm' => '17,980',
        'jht' => '221,758',
        'jp' => '119,869',
        'lain_-_lain' => '10,000',
    ];

    $mapped = PayrollCsvMapper::mapRawRow($raw);

    expect($mapped['keluarga'])->toBe('221295')
        ->and($mapped['tunj_masa_kerja'])->toBe('885914')
        ->and($mapped['tunj_kehadiran'])->toBe('570000')
        ->and($mapped['tunj_makan'])->toBe('380000')
        ->and($mapped['fungsional'])->toBe('1500000')
        ->and($mapped['jkn'])->toBe('520992')
        ->and($mapped['jkn_label'])->toBe('Remunerasi JKN Maret 2026')
        ->and($mapped['umum'])->toBe('190213')
        ->and($mapped['umum_label'])->toBe('Remunerasi Umum April 2026')
        ->and($mapped['tunj_bpjs_tk'])->toBe('373991')
        ->and($mapped['lain_pot'])->toBe('10000');
});

test('maps july 2026 payroll csv format with combined tunjangan column', function () {
    $raw = [
        'gaji_pokok' => '2.097.300',
        'tunjangan_keluarga' => '1.048.650',
        'tunjangan_kehadiran,_makan_&_masa_kerja' => '2.768.436',
        'fungsional_profesi' => '3.000.000',
        'jkn_mei_2026' => '1.103.169',
        'umum_juni_2026' => '528.309',
        'lain2_jaga_pabrik' => '150.000',
        'keterlambatan' => '120.000',
        'ijin' => '50.000',
        'lain_-_lain' => '25.000',
        'jumlah' => '18.346.211',
        'jumlah_pot' => '1.912.751',
        'penerimaan' => '15.275.385',
    ];

    $mapped = PayrollCsvMapper::mapRawRow($raw);

    expect($mapped['tunj_kehadiran'])->toBe('2768436')
        ->and($mapped['tunj_masa_kerja'])->toBeNull()
        ->and($mapped['tunj_makan'])->toBeNull()
        ->and(PayrollCsvMapper::usesCombinedTunjangan($raw))->toBeTrue()
        ->and($mapped['lain_lain'])->toBe('150000')
        ->and($mapped['lain_pot'])->toBe('195000')
        ->and($mapped['jkn'])->toBe('1103169')
        ->and($mapped['umum'])->toBe('528309');
});

test('build verification rows detect csv db mismatch', function () {
    $raw = [
        'tunjangan_keluarga' => '221,295',
        'fungsional_profesi' => '1,500,000',
    ];

    $rows = PayrollCsvMapper::buildVerificationRows($raw, [
        'keluarga' => '221295.00',
        'fungsional' => null,
    ]);

    $keluargaRow = collect($rows)->firstWhere('db_key', 'keluarga');
    $fungsionalRow = collect($rows)->firstWhere('db_key', 'fungsional');

    expect($keluargaRow['match'])->toBeTrue()
        ->and($fungsionalRow['match'])->toBeFalse();
});

test('july 2026 verification matches after combined tunjangan mapping', function () {
    $raw = [
        'gaji_pokok' => '2.097.300',
        'tunjangan_keluarga' => '1.048.650',
        'tunjangan_kehadiran,_makan_&_masa_kerja' => '2.768.436',
        'fungsional_profesi' => '3.000.000',
        'jkk' => '14.384',
        'jkm' => '17.980',
        'jht' => '221.758',
        'jp' => '119.869',
        'lain2_jaga_pabrik' => '150.000',
        'keterlambatan' => '120.000',
        'ijin' => '50.000',
        'lain_-_lain' => '25.000',
        'jkn_mei_2026' => '1.103.169',
        'umum_juni_2026' => '528.309',
        'jumlah' => '18.346.211',
        'jumlah_pot' => '1.912.751',
        'penerimaan' => '15.275.385',
    ];

    $mapped = PayrollCsvMapper::mapRawRow($raw);
    $rows = PayrollCsvMapper::buildVerificationRows($raw, $mapped);

    expect(collect($rows)->every(fn ($row) => $row['match']))->toBeTrue()
        ->and(collect($rows)->firstWhere('csv_key', 'tunjangan_kmm')['match'])->toBeTrue()
        ->and(collect($rows)->firstWhere('csv_key', 'jkk+jkm+jht+jp')['match'])->toBeTrue();
});
