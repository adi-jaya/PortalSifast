<?php

use App\Models\EmployeeSalary;
use App\Models\User;
use App\Support\PayrollSlipStructure;

use function Pest\Laravel\actingAs;

test('payroll detail api returns slip sections and computed totals', function () {
    $user = User::factory()->create(['simrs_nik' => '99.01.08.2012']);

    $salary = EmployeeSalary::query()->create([
        'period_start' => '2026-04-01',
        'simrs_nik' => '99.01.08.2012',
        'employee_name' => 'Test Pegawai',
        'unit' => 'IT',
        'status' => 'published',
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
        'jkn_label' => 'Remunerasi JKN Maret 2026',
        'umum' => '332075',
        'umum_label' => 'Remunerasi Umum April 2026',
        'jkn_susulan' => '14765',
        'jkn_susulan_l' => '39297',
        'jumlah' => '15293330',
        'penerimaan' => '14000000',
        'pajak' => '100000',
        'zakat' => '0',
        'raw_row' => ['nik' => '99.01.08.2012'],
    ]);

    $expected = PayrollSlipStructure::computeTotals($salary);

    actingAs($user, 'sanctum')
        ->getJson("/api/sifast/payroll/{$salary->id}")
        ->assertOk()
        ->assertJsonPath('data.totals.jumlah_tunjangan', (int) $expected['jumlah_tunjangan'])
        ->assertJsonPath('data.totals.jumlah_gaji', (int) $expected['jumlah_gaji'])
        ->assertJsonPath('data.components.tunj_masa_kerja', '1197489.00')
        ->assertJsonPath('data.slip_sections.0.number', '1')
        ->assertJsonPath('data.slip_sections.0.title', 'Gaji Pokok')
        ->assertJsonPath('data.slip_sections.0.lines.0.key', 'gaji_pokok')
        ->assertJsonPath('data.slip_sections.0.lines.0.label', 'Gaji Pokok')
        ->assertJsonPath('data.slip_sections.1.number', '2')
        ->assertJsonPath('data.slip_sections.1.title', 'Tunjangan')
        ->assertJsonStructure([
            'data' => [
                'totals' => [
                    'kehadiran',
                    'subtotal_tunjangan',
                    'subtotal_lain_lain',
                    'jumlah_tunjangan',
                    'jumlah_gaji',
                    'jumlah_potongan',
                    'gaji_bersih',
                    'from_csv',
                ],
                'slip_sections' => [
                    '*' => [
                        'number',
                        'title',
                        'lines' => [
                            '*' => ['key', 'label', 'amount'],
                        ],
                    ],
                ],
                'components',
            ],
        ]);

    expect($expected['jumlah_tunjangan'])->toBe(13_534_281.0)
        ->and($expected['jumlah_gaji'])->toBe(15_293_330.0);
});

test('payroll detail api uses combined tunjangan label for july csv format', function () {
    $user = User::factory()->create(['simrs_nik' => '13.20.04.2007']);

    $salary = EmployeeSalary::query()->create([
        'period_start' => '2026-07-01',
        'simrs_nik' => '13.20.04.2007',
        'employee_name' => 'Dokter Gabungan',
        'unit' => 'OBG',
        'status' => 'published',
        'gaji_pokok' => '2097300',
        'keluarga' => '1048650',
        'tunj_kehadiran' => '2768436',
        'fungsional' => '3000000',
        'penerimaan' => '15275385',
        'pajak' => '0',
        'zakat' => '0',
        'raw_row' => [
            'nik' => '13.20.04.2007',
            'tunjangan_kehadiran,_makan_&_masa_kerja' => '2.768.436',
        ],
    ]);

    $response = actingAs($user, 'sanctum')
        ->getJson("/api/sifast/payroll/{$salary->id}")
        ->assertOk();

    $tunjanganLines = collect($response->json('data.slip_sections.1.lines'));

    expect($tunjanganLines->firstWhere('key', 'tunj_kehadiran')['label'])
        ->toBe('Kehadiran, Makan & Masa Kerja')
        ->and($tunjanganLines->firstWhere('key', 'tunj_masa_kerja'))->toBeNull()
        ->and($tunjanganLines->firstWhere('key', 'tunj_makan'))->toBeNull();
});

test('payroll list api includes summary totals', function () {
    $user = User::factory()->create(['simrs_nik' => '01.02.03.2000']);

    EmployeeSalary::query()->create([
        'period_start' => '2026-02-01',
        'simrs_nik' => $user->simrs_nik,
        'employee_name' => 'Saya',
        'unit' => 'IT',
        'status' => 'published',
        'gaji_pokok' => '1000000',
        'keluarga' => '100000',
        'penerimaan' => '1100000',
        'pajak' => '0',
        'zakat' => '0',
        'raw_row' => ['nik' => $user->simrs_nik],
    ]);

    actingAs($user, 'sanctum')
        ->getJson('/api/sifast/payroll')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'jumlah_gaji',
                    'jumlah_tunjangan',
                    'jumlah_potongan',
                    'gaji_bersih',
                ],
            ],
        ]);
});
