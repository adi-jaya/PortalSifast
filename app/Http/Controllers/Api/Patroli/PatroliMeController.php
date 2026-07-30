<?php

namespace App\Http\Controllers\Api\Patroli;

use App\Http\Controllers\Controller;
use App\Support\PatroliPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatroliMeController extends Controller
{
    public function __construct(private PatroliPetugasResolver $petugasResolver) {}

    public function __invoke(Request $request): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $petugas->toApiProfileArray(),
                'can_access_patroli' => $petugas->canAccessPatroli(),
                'nik' => $petugas->simrs_nik,
            ],
        ]);
    }
}
