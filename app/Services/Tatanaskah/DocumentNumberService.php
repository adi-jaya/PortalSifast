<?php

namespace App\Services\Tatanaskah;

use App\Models\CounterNomorDokumen;
use App\Models\KodeSifatNaskah;
use App\Models\KodeUnitKlasifikasi;
use App\Models\KonfigurasiJenisDokumen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DocumentNumberService
{
    private const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public function generate(
        KonfigurasiJenisDokumen $jenis,
        KodeUnitKlasifikasi $unit,
        KodeSifatNaskah $sifat,
        ?Carbon $tanggal = null,
    ): string {
        $tanggal ??= now();
        $tahun = (int) $tanggal->format('Y');
        $bulanRomawi = self::ROMAN_MONTHS[(int) $tanggal->format('n')] ?? 'I';

        $sequence = DB::transaction(function () use ($unit, $tahun): int {
            $row = CounterNomorDokumen::query()
                ->where('kode_unit_klasifikasi_id', $unit->id)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $row = CounterNomorDokumen::query()->create([
                    'kode_unit_klasifikasi_id' => $unit->id,
                    'tahun' => $tahun,
                    'counter' => 0,
                ]);

                $row = CounterNomorDokumen::query()
                    ->whereKey($row->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $row->increment('counter');

            return (int) $row->fresh()->counter;
        });

        $format = $jenis->format_nomor ?? "RS'ASF/[NNN]/[UNIT_KLASIFIKASI]/[SIFAT]/[BR]/[YYYY]";

        return str_replace(
            ['[NNN]', '[UNIT_KLASIFIKASI]', '[SIFAT]', '[BR]', '[YYYY]', '[PREFIX]'],
            [
                str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                $unit->kode,
                $sifat->kode,
                $bulanRomawi,
                (string) $tahun,
                $jenis->prefix_kode_rs ?? "RS'ASF",
            ],
            $format,
        );
    }

    public static function romanMonth(int $month): string
    {
        return self::ROMAN_MONTHS[$month] ?? 'I';
    }
}
