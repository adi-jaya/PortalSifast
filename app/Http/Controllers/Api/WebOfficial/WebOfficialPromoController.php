<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialPromo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialPromoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialPromo::query()
            ->activePublic()
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('limit')) {
            $query->limit(min(max((int) $request->input('limit'), 1), 100));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()->map->toPublicListArray()->values()->all(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $promo = WebOfficialPromo::query()
            ->activePublic()
            ->where('slug', $slug)
            ->first();

        if ($promo === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Promo tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promo->toPublicDetailArray(),
        ]);
    }
}
