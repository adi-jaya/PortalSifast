<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Support\DriverPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverMeController extends Controller
{
    public function __construct(private DriverPetugasResolver $petugasResolver) {}

    public function __invoke(Request $request): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolveIdentity($request);
        if ($error !== null) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $petugas->toApiProfileArray(),
                'can_access_checklist_kendaraan' => $petugas->canAccessChecklistKendaraan(),
                'can_create_driver_pemeriksaan' => $petugas->canCreateDriverPemeriksaan(),
                'nik' => $petugas->simrs_nik,
                'config' => [
                    'min_inspection_per_day' => (int) config('driver.min_inspection_per_day', 1),
                    'max_inspection_per_day' => (int) config('driver.max_inspection_per_day', 2),
                ],
                'hasil_labels' => [
                    'layak' => 'Baik',
                    'tidak_layak' => 'Tidak Baik',
                    'na' => 'Tidak berlaku',
                ],
            ],
        ]);
    }
}
