<?php

namespace App\Services\Tatanaskah;

use Illuminate\Support\Carbon;

/**
 * Konversi tanggal Masehi ke format Hijriyah untuk dokumen RS'ASF.
 * Algoritma Umm al-Qura approximation — cukup untuk fase 1.
 */
final class HijriConverterService
{
    /** @var list<string> */
    private const HIJRI_MONTHS = [
        'Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir',
        'Jumadil Awal', 'Jumadil Akhir', 'Rajab', 'Sya\'ban',
        'Ramadhan', 'Syawal', 'Dzulqa\'dah', 'Dzulhijah',
    ];

    public function formatFromGregorian(Carbon $date): string
    {
        [$day, $month, $year] = $this->gregorianToHijri(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
        );

        $monthName = self::HIJRI_MONTHS[$month - 1] ?? self::HIJRI_MONTHS[0];

        return sprintf('%d %s %d H', $day, $monthName, $year);
    }

    /**
     * @return array{0: int, 1: int, 2: int} day, month, year
     */
    private function gregorianToHijri(int $year, int $month, int $day): array
    {
        $jd = $this->gregorianToJulian($year, $month, $day);

        return $this->julianToHijri($jd);
    }

    private function gregorianToJulian(int $year, int $month, int $day): float
    {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }

        $a = (int) floor($year / 100);
        $b = 2 - $a + (int) floor($a / 4);

        return (int) floor(365.25 * ($year + 4716))
            + (int) floor(30.6001 * ($month + 1))
            + $day + $b - 1524.5;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function julianToHijri(float $jd): array
    {
        $jd = (int) floor($jd) + 0.5;
        $l = $jd - 1948440 + 10632;
        $n = (int) floor(($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = (int) floor((10985 - $l) / 5316) * (int) floor((50 * $l) / 17719)
            + (int) floor($l / 5670) * (int) floor((43 * $l) / 15238);
        $l = $l - (int) floor((30 - $j) / 15) * (int) floor((17719 * $j) / 50)
            - (int) floor($j / 16) * (int) floor((15238 * $j) / 43) + 29;
        $month = (int) floor((24 * $l) / 709);
        $day = (int) ($l - (int) floor((709 * $month) / 24));
        $year = 30 * $n + $j - 30;

        return [$day, $month, $year];
    }
}
