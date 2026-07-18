<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\WebOfficial\ExternalRssFeedService;
use Illuminate\Http\JsonResponse;

class ExternalRssFeedAdminController extends Controller
{
    public function status(ExternalRssFeedService $externalRssFeedService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $externalRssFeedService->status(),
        ]);
    }

    public function sync(ExternalRssFeedService $externalRssFeedService): JsonResponse
    {
        $result = $externalRssFeedService->syncFromFeeds();

        return response()->json([
            'success' => $result['success'],
            'data' => [
                'count' => $result['count'],
                'syncedAt' => $result['syncedAt'],
                'sources' => $result['sourceStats'],
            ],
            'error' => $result['success'] ? null : [
                'code' => 'SYNC_FAILED',
                'message' => $result['message'],
            ],
        ], $result['success'] ? 200 : 422);
    }
}
