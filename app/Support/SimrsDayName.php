<?php

namespace App\Support;

use Carbon\CarbonInterface;
use InvalidArgumentException;

final class SimrsDayName
{
    /**
     * @var array<int, string>
     */
    private const ISO_DAY_TO_SIMRS = [
        1 => 'SENIN',
        2 => 'SELASA',
        3 => 'RABU',
        4 => 'KAMIS',
        5 => 'JUMAT',
        6 => 'SABTU',
        7 => 'MINGGU',
    ];

    /**
     * @var array<string, string>
     */
    private const ALIAS_TO_SIMRS = [
        'SENIN' => 'SENIN',
        'SELASA' => 'SELASA',
        'RABU' => 'RABU',
        'KAMIS' => 'KAMIS',
        'JUMAT' => 'JUMAT',
        'SABTU' => 'SABTU',
        'MINGGU' => 'MINGGU',
        'SENIN-' => 'SENIN',
        'MON' => 'SENIN',
        'MONDAY' => 'SENIN',
        'TUE' => 'SELASA',
        'TUESDAY' => 'SELASA',
        'WED' => 'RABU',
        'WEDNESDAY' => 'RABU',
        'THU' => 'KAMIS',
        'THURSDAY' => 'KAMIS',
        'FRI' => 'JUMAT',
        'FRIDAY' => 'JUMAT',
        'SAT' => 'SABTU',
        'SATURDAY' => 'SABTU',
        'SUN' => 'MINGGU',
        'SUNDAY' => 'MINGGU',
    ];

    public static function fromDate(CarbonInterface $date): string
    {
        return self::ISO_DAY_TO_SIMRS[$date->dayOfWeekIso]
            ?? throw new InvalidArgumentException('Tidak dapat menentukan hari dari tanggal.');
    }

    public static function normalize(string $hari): string
    {
        $key = strtoupper(trim($hari));

        if (isset(self::ALIAS_TO_SIMRS[$key])) {
            return self::ALIAS_TO_SIMRS[$key];
        }

        throw new InvalidArgumentException("Hari tidak valid: {$hari}");
    }

    public static function label(string $simrsDay): string
    {
        return ucfirst(strtolower($simrsDay));
    }
}
