<?php

namespace Tests\Unit;

use App\Support\SimrsDayName;
use Carbon\Carbon;
use InvalidArgumentException;

test('fromDate returns correct simrs day names', function (string $date, string $expected): void {
    expect(SimrsDayName::fromDate(Carbon::parse($date)))->toBe($expected);
})->with([
    'monday' => ['2026-07-06', 'SENIN'],
    'tuesday' => ['2026-07-07', 'SELASA'],
    'wednesday' => ['2026-07-08', 'RABU'],
    'thursday' => ['2026-07-09', 'KAMIS'],
    'friday' => ['2026-07-10', 'JUMAT'],
    'saturday' => ['2026-07-11', 'SABTU'],
    'sunday' => ['2026-07-12', 'MINGGU'],
]);

test('normalize rejects unknown day', function (): void {
    SimrsDayName::normalize('notaday');
})->throws(InvalidArgumentException::class);
