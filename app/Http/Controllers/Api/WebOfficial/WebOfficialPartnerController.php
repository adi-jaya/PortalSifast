<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialPartnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialPartner::query()
            ->activePublic()
            ->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('limit')) {
            $query->limit(max((int) $request->input('limit'), 1));
        }

        $partners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $partners->map->toPublicArray()->values()->all(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $partner = WebOfficialPartner::query()
            ->activePublic()
            ->where('slug', $slug)
            ->first();

        if ($partner === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Rekanan tidak ditemukan',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partner->toPublicArray(),
        ]);
    }
}
