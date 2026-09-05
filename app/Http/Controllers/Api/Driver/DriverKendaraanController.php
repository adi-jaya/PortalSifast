<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverChecklistItem;
use App\Models\DriverKendaraan;
use App\Models\DriverKendaraanItem;
use App\Models\DriverPemeriksaan;
use App\Support\DriverPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverKendaraanController extends Controller
{
    public function __construct(private DriverPetugasResolver $petugasResolver) {}

    public function index(Request $request): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);

        $todayCounts = DriverPemeriksaan::query()
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->selectRaw('driver_kendaraan_id, count(*) as total')
            ->groupBy('driver_kendaraan_id')
            ->pluck('total', 'driver_kendaraan_id');

        $rows = DriverKendaraan::query()
            ->where('status', DriverKendaraan::STATUS_AKTIF)
            ->orderBy('nama')
            ->get()
            ->map(function (DriverKendaraan $kendaraan) use ($todayCounts, $maxPerDay) {
                $count = (int) ($todayCounts[$kendaraan->id] ?? 0);

                return [
                    'id' => $kendaraan->id,
                    'nama' => $kendaraan->nama,
                    'no_polisi' => $kendaraan->no_polisi,
                    'merk' => $kendaraan->merk,
                    'model' => $kendaraan->model,
                    'tahun' => $kendaraan->tahun,
                    'jumlah_hari_ini' => $count,
                    'bisa_buat_baru' => $count < $maxPerDay,
                    'pemeriksaan_ke_berikutnya' => $count < $maxPerDay ? $count + 1 : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'max_per_day' => $maxPerDay,
                'petugas_id' => $petugas->id,
            ],
        ]);
    }

    public function form(Request $request, DriverKendaraan $kendaraan): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        if (! $kendaraan->isAktif()) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan tidak aktif.',
            ], 404);
        }

        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);
        $existingCount = DriverPemeriksaan::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->count();

        if ($existingCount >= $maxPerDay) {
            return response()->json([
                'success' => false,
                'message' => "Pemeriksaan hari ini sudah mencapai batas maksimal ({$maxPerDay}×).",
            ], 422);
        }

        $pivots = DriverKendaraanItem::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->get()
            ->keyBy('driver_checklist_item_id');

        $items = DriverChecklistItem::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get()
            ->map(function (DriverChecklistItem $item) use ($pivots) {
                $berlaku = $pivots->isEmpty()
                    ? true
                    : (bool) ($pivots->get($item->id)?->berlaku ?? false);

                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kategori' => $item->kategori,
                    'urutan' => $item->urutan,
                    'berlaku' => $berlaku,
                    'hasil_options' => $berlaku
                        ? [
                            ['value' => 'layak', 'label' => 'Baik'],
                            ['value' => 'tidak_layak', 'label' => 'Tidak Baik'],
                        ]
                        : [
                            ['value' => 'na', 'label' => 'Tidak berlaku'],
                        ],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'kendaraan' => [
                    'id' => $kendaraan->id,
                    'nama' => $kendaraan->nama,
                    'no_polisi' => $kendaraan->no_polisi,
                    'merk' => $kendaraan->merk,
                    'model' => $kendaraan->model,
                ],
                'pemeriksaan_ke' => $existingCount + 1,
                'tanggal' => now()->toDateString(),
                'waktu' => now()->format('H:i'),
                'petugas' => [
                    'id' => $petugas->id,
                    'name' => $petugas->name,
                    'simrs_nik' => $petugas->simrs_nik,
                ],
                'items' => $items,
            ],
        ]);
    }
}
