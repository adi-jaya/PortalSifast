<?php

namespace App\Support;

use App\Models\EmployeeSalary;

/**
 * Struktur slip gaji — selaraskan dengan resources/js/pages/payroll/payroll-slip-structure.ts
 */
final class PayrollSlipStructure
{
    /**
     * @var list<array{number: string, title: string, lines: list<array{key: string, label: string, dynamic_label_key?: string}>}>
     */
    public const SECTIONS = [
        [
            'number' => '1',
            'title' => 'Gaji Pokok',
            'lines' => [
                ['key' => 'gaji_pokok', 'label' => 'Gaji Pokok'],
            ],
        ],
        [
            'number' => '2',
            'title' => 'Tunjangan',
            'lines' => [
                ['key' => 'keluarga', 'label' => 'Keluarga'],
                ['key' => 'tunj_masa_kerja', 'label' => 'Masa Kerja'],
                ['key' => 'tunj_kehadiran', 'label' => 'Kehadiran'],
                ['key' => 'tunj_makan', 'label' => 'Makan'],
                ['key' => 'fungsional', 'label' => 'Fungsional'],
                ['key' => 'struktural', 'label' => 'Struktural'],
                ['key' => 'operasional', 'label' => 'Operasional'],
                ['key' => 'tunj_bpjs_tk', 'label' => 'BPJS Ketenagakerjaan'],
                ['key' => 'bpjs_kes', 'label' => 'BPJS Kesehatan'],
                ['key' => 'transport_spj', 'label' => 'Transport/SPJ'],
                ['key' => 'jm_dokter', 'label' => 'Jasa Medis Dokter'],
                ['key' => 'lain_lain', 'label' => 'Lain-lain'],
            ],
        ],
        [
            'number' => '3',
            'title' => 'Lain-Lain',
            'lines' => [
                ['key' => 'lembur', 'label' => 'Lembur'],
                ['key' => 'on_call', 'label' => 'On Call / Asisten'],
                ['key' => 'jkn', 'label' => 'Remunerasi JKN', 'dynamic_label_key' => 'jkn_label'],
                ['key' => 'umum', 'label' => 'Remunerasi Umum', 'dynamic_label_key' => 'umum_label'],
                ['key' => 'jkn_susulan', 'label' => 'Remunerasi JKN Susulan'],
                ['key' => 'jkn_susulan_l', 'label' => 'Remunerasi JKN Susulan'],
            ],
        ],
        [
            'number' => '4',
            'title' => 'Potongan-Potongan',
            'lines' => [
                ['key' => 'zakat', 'label' => 'Zakat'],
                ['key' => 'pajak', 'label' => 'Pajak'],
                ['key' => 'pot_bpjs_tk', 'label' => 'BPJS Ketenagakerjaan'],
                ['key' => 'bpjs_kes_k', 'label' => 'BPJS Kesehatan'],
                ['key' => 'jht_i', 'label' => 'Jaminan Hari Tua'],
                ['key' => 'jp_i', 'label' => 'Jaminan Pensiun'],
                ['key' => 'bpjs_kes_i', 'label' => 'BPJS Kesehatan'],
                ['key' => 'bpjs_kes_tidak_ditanggung', 'label' => 'BPJS Kesehatan tdk di tgg'],
                ['key' => 'matan', 'label' => 'Matan'],
                ['key' => 'lazismu', 'label' => 'Lazismu'],
                ['key' => 'obat2an', 'label' => 'Obat/Jasmed/Tindakan'],
                ['key' => 'hutang_bpjs', 'label' => 'Hutang BPJS'],
                ['key' => 'hutang_seragam', 'label' => 'Hutang Seragam'],
                ['key' => 'ikkm', 'label' => 'IKKM'],
                ['key' => 'keterlambatan', 'label' => 'Keterlambatan'],
                ['key' => 'ijin', 'label' => 'Ijin'],
                ['key' => 'lain_pot', 'label' => 'Lain-lain'],
            ],
        ],
    ];

    /**
     * @return list<string>
     */
    public static function tunjanganKeys(): array
    {
        return array_column(self::SECTIONS[1]['lines'], 'key');
    }

    /**
     * @return list<string>
     */
    public static function lainLainKeys(): array
    {
        return array_column(self::SECTIONS[2]['lines'], 'key');
    }

    /**
     * @return list<string>
     */
    public static function potonganKeys(): array
    {
        return array_column(self::SECTIONS[3]['lines'], 'key');
    }

    /**
     * @return array{
     *     kehadiran: float,
     *     subtotal_tunjangan: float,
     *     subtotal_lain_lain: float,
     *     jumlah_tunjangan: float,
     *     jumlah_gaji: float,
     *     jumlah_potongan: float,
     *     gaji_bersih: float,
     *     from_csv: array{jumlah_tunjangan: bool, jumlah_gaji: bool, jumlah_potongan: bool, gaji_bersih: bool}
     * }
     */
    public static function computeTotals(EmployeeSalary $salary): array
    {
        $kehadiran = self::moneyValue($salary->gaji_pokok);
        $subtotalTunjangan = self::sumKeys($salary, self::tunjanganKeys());
        $subtotalLainLain = self::sumKeys($salary, self::lainLainKeys());
        $computedTunjangan = $subtotalTunjangan + $subtotalLainLain;

        $fromCsvJumlahPot = self::parseMoney($salary->jumlah_pot) !== null;
        $fromCsvGajiBersih = self::parseMoney($salary->pembulatan ?? $salary->penerimaan) !== null;

        $resolved = self::resolveJumlahTotals(
            $salary,
            $kehadiran,
            $computedTunjangan,
        );

        $jumlahTunjangan = $resolved['jumlah_tunjangan'];
        $jumlahGaji = $resolved['jumlah_gaji'];

        $jumlahPotongan = self::parseMoney($salary->jumlah_pot)
            ?? self::sumKeys($salary, self::potonganKeys());

        $gajiBersih = self::parseMoney($salary->pembulatan ?? $salary->penerimaan)
            ?? ($jumlahGaji - $jumlahPotongan);

        return [
            'kehadiran' => $kehadiran,
            'subtotal_tunjangan' => $subtotalTunjangan,
            'subtotal_lain_lain' => $subtotalLainLain,
            'jumlah_tunjangan' => $jumlahTunjangan,
            'jumlah_gaji' => $jumlahGaji,
            'jumlah_potongan' => $jumlahPotongan,
            'gaji_bersih' => $gajiBersih,
            'from_csv' => [
                'jumlah_tunjangan' => $resolved['from_csv']['jumlah_tunjangan'],
                'jumlah_gaji' => $resolved['from_csv']['jumlah_gaji'],
                'jumlah_potongan' => $fromCsvJumlahPot,
                'gaji_bersih' => $fromCsvGajiBersih,
            ],
        ];
    }

    /**
     * Beberapa export CSV RS menukar makna kolom: `jumlah` = total tunjangan, `jumlah_tunjangan` = jumlah gaji.
     *
     * @return array{
     *     jumlah_tunjangan: float,
     *     jumlah_gaji: float,
     *     from_csv: array{jumlah_tunjangan: bool, jumlah_gaji: bool}
     * }
     */
    public static function resolveJumlahTotals(
        EmployeeSalary $salary,
        float $kehadiran,
        float $computedTunjangan,
    ): array {
        $csvJumlah = self::parseMoney($salary->jumlah);
        $csvJumlahTunjangan = self::parseMoney($salary->jumlah_tunjangan);

        if ($csvJumlah !== null && $csvJumlahTunjangan !== null) {
            $swappedMatch = abs($csvJumlah - $computedTunjangan) < 1
                && abs($csvJumlahTunjangan - ($kehadiran + $csvJumlah)) < 1;

            $standardMatch = abs($csvJumlahTunjangan - $computedTunjangan) < 1
                && abs($csvJumlah - ($kehadiran + $csvJumlahTunjangan)) < 1;

            if ($swappedMatch && ! $standardMatch) {
                return [
                    'jumlah_tunjangan' => $csvJumlah,
                    'jumlah_gaji' => $csvJumlahTunjangan,
                    'from_csv' => ['jumlah_tunjangan' => true, 'jumlah_gaji' => true],
                ];
            }

            if ($standardMatch) {
                return [
                    'jumlah_tunjangan' => $csvJumlahTunjangan,
                    'jumlah_gaji' => $csvJumlah,
                    'from_csv' => ['jumlah_tunjangan' => true, 'jumlah_gaji' => true],
                ];
            }

            if (abs($csvJumlahTunjangan - ($kehadiran + $csvJumlah)) < 1) {
                return [
                    'jumlah_tunjangan' => $csvJumlah,
                    'jumlah_gaji' => $csvJumlahTunjangan,
                    'from_csv' => ['jumlah_tunjangan' => true, 'jumlah_gaji' => true],
                ];
            }

            if (abs($csvJumlah - ($kehadiran + $csvJumlahTunjangan)) < 1) {
                return [
                    'jumlah_tunjangan' => $csvJumlahTunjangan,
                    'jumlah_gaji' => $csvJumlah,
                    'from_csv' => ['jumlah_tunjangan' => true, 'jumlah_gaji' => true],
                ];
            }
        }

        if ($csvJumlahTunjangan !== null) {
            $jumlahTunjangan = $csvJumlahTunjangan;

            return [
                'jumlah_tunjangan' => $jumlahTunjangan,
                'jumlah_gaji' => $csvJumlah ?? ($kehadiran + $jumlahTunjangan),
                'from_csv' => [
                    'jumlah_tunjangan' => true,
                    'jumlah_gaji' => $csvJumlah !== null,
                ],
            ];
        }

        if ($csvJumlah !== null) {
            return [
                'jumlah_tunjangan' => $computedTunjangan,
                'jumlah_gaji' => $csvJumlah,
                'from_csv' => [
                    'jumlah_tunjangan' => false,
                    'jumlah_gaji' => true,
                ],
            ];
        }

        return [
            'jumlah_tunjangan' => $computedTunjangan,
            'jumlah_gaji' => $kehadiran + $computedTunjangan,
            'from_csv' => [
                'jumlah_tunjangan' => false,
                'jumlah_gaji' => false,
            ],
        ];
    }

    /**
     * @return list<array{number: string, title: string, lines: list<array{key: string, label: string, amount: float}>}>
     */
    public static function buildSections(EmployeeSalary $salary): array
    {
        $raw = is_array($salary->raw_row) ? $salary->raw_row : [];
        $usesCombinedTunjangan = PayrollCsvMapper::usesCombinedTunjangan($raw);
        $sections = [];

        foreach (self::SECTIONS as $section) {
            $lines = [];

            foreach ($section['lines'] as $line) {
                if (
                    $usesCombinedTunjangan
                    && in_array($line['key'], ['tunj_masa_kerja', 'tunj_makan'], true)
                ) {
                    continue;
                }

                $label = $line['label'];
                if (isset($line['dynamic_label_key'])) {
                    $dynamic = $salary->{$line['dynamic_label_key']} ?? null;
                    if (is_string($dynamic) && $dynamic !== '') {
                        $label = $dynamic;
                    }
                }

                if ($usesCombinedTunjangan && $line['key'] === 'tunj_kehadiran') {
                    $label = 'Kehadiran, Makan & Masa Kerja';
                }

                $lines[] = [
                    'key' => $line['key'],
                    'label' => $label,
                    'amount' => self::moneyValue($salary->{$line['key']} ?? null),
                ];
            }

            $sections[] = [
                'number' => $section['number'],
                'title' => $section['title'],
                'lines' => $lines,
            ];
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    public static function componentsPayload(EmployeeSalary $salary): array
    {
        return [
            'gaji_pokok' => $salary->gaji_pokok,
            'keluarga' => $salary->keluarga,
            'tunj_masa_kerja' => $salary->tunj_masa_kerja,
            'tunj_kehadiran' => $salary->tunj_kehadiran,
            'tunj_makan' => $salary->tunj_makan,
            'fungsional' => $salary->fungsional,
            'struktural' => $salary->struktural,
            'operasional' => $salary->operasional,
            'tunj_bpjs_tk' => $salary->tunj_bpjs_tk,
            'bpjs_kes' => $salary->bpjs_kes,
            'transport_spj' => $salary->transport_spj,
            'jm_dokter' => $salary->jm_dokter,
            'lain_lain' => $salary->lain_lain,
            'lembur' => $salary->lembur,
            'on_call' => $salary->on_call,
            'jkn' => $salary->jkn,
            'jkn_label' => $salary->jkn_label,
            'umum' => $salary->umum,
            'umum_label' => $salary->umum_label,
            'jkn_susulan' => $salary->jkn_susulan,
            'jkn_susulan_l' => $salary->jkn_susulan_l,
            'pot_bpjs_tk' => $salary->pot_bpjs_tk,
            'bpjs_kes_k' => $salary->bpjs_kes_k,
            'jht_i' => $salary->jht_i,
            'jp_i' => $salary->jp_i,
            'bpjs_kes_i' => $salary->bpjs_kes_i,
            'bpjs_kes_tidak_ditanggung' => $salary->bpjs_kes_tidak_ditanggung,
            'matan' => $salary->matan,
            'lazismu' => $salary->lazismu,
            'obat2an' => $salary->obat2an,
            'hutang_bpjs' => $salary->hutang_bpjs,
            'hutang_seragam' => $salary->hutang_seragam,
            'ikkm' => $salary->ikkm,
            'keterlambatan' => $salary->keterlambatan,
            'ijin' => $salary->ijin,
            'lain_pot' => $salary->lain_pot,
            'jumlah_tunjangan' => $salary->jumlah_tunjangan,
            'jumlah' => $salary->jumlah,
            'jumlah_pot' => $salary->jumlah_pot,
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private static function sumKeys(EmployeeSalary $salary, array $keys): float
    {
        $sum = 0.0;
        foreach ($keys as $key) {
            $sum += self::moneyValue($salary->{$key} ?? null);
        }

        return $sum;
    }

    private static function moneyValue(mixed $value): float
    {
        return self::parseMoney($value) ?? 0.0;
    }

    private static function parseMoney(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || $trimmed === '-' || $trimmed === '–') {
            return null;
        }

        $noRp = str_ireplace('rp', '', $trimmed);
        $noRp = preg_replace('/\s+/', '', $noRp) ?? $trimmed;

        if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $noRp) === 1) {
            $digits = str_replace('.', '', $noRp);
            $n = (float) $digits;

            return is_finite($n) ? $n : null;
        }

        $cleaned = preg_replace('/[^0-9,.\-]/', '', $noRp) ?? '';
        $normalized = str_replace(',', '.', $cleaned);
        $n = (float) $normalized;

        return is_finite($n) ? $n : null;
    }
}
