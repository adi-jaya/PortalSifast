<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\DoctorProfileIndexRequest;
use App\Services\Simrs\DoctorProfileService;
use App\Services\Simrs\PegawaiPhotoDataUriService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorProfileController extends Controller
{
    public function index(
        DoctorProfileIndexRequest $request,
        DoctorProfileService $profileService,
    ): JsonResponse {
        $profiles = $profileService->list(
            q: $request->filled('q') ? $request->string('q')->toString() : null,
            kdPoli: $request->filled('kd_poli') ? $request->string('kd_poli')->toString() : null,
            poli: $request->filled('poli') ? $request->string('poli')->toString() : null,
            withFoto: $request->boolean('with_foto', false),
        );

        return response()->json([
            'success' => true,
            'meta' => [
                'q' => $request->filled('q') ? $request->string('q')->toString() : null,
                'kdPoli' => $request->filled('kd_poli') ? $request->string('kd_poli')->toString() : null,
                'poli' => $request->filled('poli') ? $request->string('poli')->toString() : null,
                'total' => count($profiles),
            ],
            'data' => $profiles,
        ]);
    }

    public function show(
        string $kdDokter,
        Request $request,
        DoctorProfileService $profileService,
    ): JsonResponse {
        $profile = $profileService->find(
            $kdDokter,
            withFoto: $request->boolean('with_foto', false),
        );

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Profil dokter tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function photo(string $kdDokter, PegawaiPhotoDataUriService $photoService): Response
    {
        $binary = $photoService->getBinaryForDokter($kdDokter);

        if ($binary === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Foto dokter tidak ditemukan.',
                ],
            ], 404);
        }

        return response($binary['content'], 200, [
            'Content-Type' => $binary['mime'],
            'Cache-Control' => 'public, max-age=43200',
        ]);
    }
}
