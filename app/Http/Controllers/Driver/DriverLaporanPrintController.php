<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverLaporanAggregator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverLaporanPrintController extends Controller
{
    public function __invoke(Request $request, DriverLaporanAggregator $aggregator): View
    {
        abort_unless(
            $request->user()?->canCoordinateChecklistKendaraan() || $request->user()?->canManageDriverMaster(),
            403,
        );

        $mode = $request->query('mode', 'harian');

        if ($mode === 'mingguan') {
            $mulai = Carbon::parse($request->query('mulai', now()->startOfWeek()->toDateString()))->startOfDay();
            $selesai = Carbon::parse($request->query('selesai', now()->endOfWeek()->toDateString()))->startOfDay();
            $data = $aggregator->mingguan($mulai, $selesai);
        } else {
            $mode = 'harian';
            $data = $aggregator->harian(Carbon::parse($request->query('tanggal', now()->toDateString())));
        }

        return view('driver.laporan-print', [
            'mode' => $mode,
            'data' => $data,
            'printedAt' => now(),
            'printedBy' => $request->user()?->name,
        ]);
    }
}
