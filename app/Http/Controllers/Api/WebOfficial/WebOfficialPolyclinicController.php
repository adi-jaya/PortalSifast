<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialPolyclinic;
use App\Services\Simrs\DoctorProfileService;
use Illuminate\Http\JsonResponse;

class WebOfficialPolyclinicController extends Controller
{
    public function index(): JsonResponse
    {
        $polyclinics = WebOfficialPolyclinic::query()
            ->activePublic()
            ->orderBy('sort_order')
            ->orderBy('simrs_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $polyclinics->map->toPublicListArray()->values()->all(),
        ]);
    }

    public function show(string $slug, DoctorProfileService $doctorProfileService): JsonResponse
    {
        $polyclinic = WebOfficialPolyclinic::query()
            ->activePublic()
            ->where('slug', $slug)
            ->first();

        if ($polyclinic === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Poliklinik tidak ditemukan.',
                ],
            ], 404);
        }

        $doctors = [];
        try {
            $doctors = $doctorProfileService->listByPoli($polyclinic->kd_poli, withFoto: false);
        } catch (\Throwable) {
            $doctors = [];
        }

        return response()->json([
            'success' => true,
            'data' => $polyclinic->toPublicDetailArray($doctors),
        ]);
    }
}
