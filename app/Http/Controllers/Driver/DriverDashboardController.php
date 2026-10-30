<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverKendaraan;
use App\Models\DriverPemeriksaan;
use App\Models\DriverPemeriksaanDetail;
use App\Services\Driver\DriverLaporanAggregator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverDashboardController extends Controller
{
    public function __invoke(Request $request, DriverLaporanAggregator $aggregator): Response
    {
        $harian = $aggregator->harian(now());

        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);
        $canCreate = $request->user()?->canCreateDriverPemeriksaan() ?? false;

        $cards = collect($harian['rows'])->map(function (array $row) use ($maxPerDay) {
            $count = ($row['check_1'] ? 1 : 0) + ($row['check_2'] ? 1 : 0);

            return [
                ...$row,
                'jumlah_pemeriksaan' => $count,
                'bisa_buat_baru' => $count < $maxPerDay,
            ];
        })->values()->all();

        return Inertia::render('driver/dashboard', [
            'tanggal' => $harian['tanggal'],
            'summary' => $harian['summary'],
            'kendaraan' => $cards,
            'canCreate' => $canCreate,
            'maxPerDay' => $maxPerDay,
            'openTemuanHariIni' => DriverPemeriksaanDetail::query()
                ->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK)
                ->whereHas('pemeriksaan', function ($q) {
                    $q->whereDate('tanggal', now()->toDateString())
                        ->where('status', DriverPemeriksaan::STATUS_SELESAI);
                })
                ->count(),
            'totalAktif' => DriverKendaraan::query()->where('status', DriverKendaraan::STATUS_AKTIF)->count(),
        ]);
    }
}
