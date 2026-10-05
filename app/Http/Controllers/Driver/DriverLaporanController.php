<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverLaporanAggregator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverLaporanController extends Controller
{
    public function __invoke(Request $request, DriverLaporanAggregator $aggregator): Response
    {
        abort_unless(
            $request->user()?->canCoordinateChecklistKendaraan() || $request->user()?->canManageDriverMaster(),
            403,
        );

        $mode = $request->query('mode', 'harian');
        $tanggal = $request->query('tanggal', now()->toDateString());

        if ($mode === 'mingguan') {
            $mulai = Carbon::parse($request->query('mulai', now()->startOfWeek()->toDateString()))->startOfDay();
            $selesai = Carbon::parse($request->query('selesai', now()->endOfWeek()->toDateString()))->startOfDay();
            $data = $aggregator->mingguan($mulai, $selesai);
        } else {
            $data = $aggregator->harian(Carbon::parse($tanggal));
            $mode = 'harian';
        }

        return Inertia::render('driver/laporan', [
            'mode' => $mode,
            'filters' => [
                'tanggal' => $tanggal,
                'mulai' => $request->query('mulai', now()->startOfWeek()->toDateString()),
                'selesai' => $request->query('selesai', now()->endOfWeek()->toDateString()),
            ],
            'data' => $data,
        ]);
    }
}
