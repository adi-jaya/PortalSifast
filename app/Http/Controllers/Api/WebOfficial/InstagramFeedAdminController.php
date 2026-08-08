<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\Instagram\InstagramFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramFeedAdminController extends Controller
{
    public function status(InstagramFeedService $instagramFeedService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $instagramFeedService->status(),
        ]);
    }

    public function sync(Request $request, InstagramFeedService $instagramFeedService): JsonResponse
    {
        $limit = $request->filled('limit')
            ? min(max((int) $request->input('limit'), 1), 50)
            : null;

        $result = $instagramFeedService->syncFromApi($limit);

        return response()->json([
            'success' => $result['success'],
            'data' => [
                'count' => $result['count'],
                'syncedAt' => $result['syncedAt'],
            ],
            'error' => $result['success'] ? null : [
                'code' => 'SYNC_FAILED',
                'message' => $result['message'],
            ],
        ], $result['success'] ? 200 : 422);
    }
}
