<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverLaporanAggregator;
use App\Support\DriverPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverHariIniController extends Controller
{
    public function __construct(private DriverPetugasResolver $petugasResolver) {}

    public function __invoke(Request $request, DriverLaporanAggregator $aggregator): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $harian = $aggregator->harian(now());
        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);

        $kendaraan = collect($harian['rows'])->map(function (array $row) use ($maxPerDay) {
            $count = ($row['check_1'] ? 1 : 0) + ($row['check_2'] ? 1 : 0);

            return [
                'kendaraan_id' => $row['kendaraan_id'],
                'nama' => $row['nama'],
                'check_1' => $row['check_1'],
                'check_2' => $row['check_2'],
                'temuan' => $row['temuan'],
                'status' => $row['status'],
                'jumlah_pemeriksaan' => $count,
                'bisa_buat_baru' => $count < $maxPerDay,
                'pemeriksaan_ke_berikutnya' => $count < $maxPerDay ? $count + 1 : null,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $harian['tanggal'],
                'summary' => $harian['summary'],
                'kendaraan' => $kendaraan,
                'petugas' => [
                    'id' => $petugas->id,
                    'name' => $petugas->name,
                    'simrs_nik' => $petugas->simrs_nik,
                ],
                'max_per_day' => $maxPerDay,
            ],
        ]);
    }
}
