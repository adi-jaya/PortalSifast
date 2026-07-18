<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialRoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialRoom::query()
            ->activePublic()
            ->orderBy('sort_order');

        if ($request->boolean('featured')) {
            $query->whereNotNull('badge')->where('badge', '!=', '');
        }

        if ($request->filled('limit')) {
            $query->limit(max((int) $request->input('limit'), 1));
        }

        $rooms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rooms->map->toPublicArray()->values()->all(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $room = WebOfficialRoom::query()
            ->activePublic()
            ->where('slug', $slug)
            ->first();

        if ($room === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Kamar tidak ditemukan',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $room->toPublicArray(),
        ]);
    }
}
