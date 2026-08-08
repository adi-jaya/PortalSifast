<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\WebOfficial\ExternalRssFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalRssFeedController extends Controller
{
    public function index(Request $request, ExternalRssFeedService $externalRssFeedService): JsonResponse
    {
        if (! $externalRssFeedService->isEnabled()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'sources' => [],
                    'items' => [],
                ],
                'meta' => [
                    'enabled' => false,
                    'syncedAt' => null,
                ],
            ]);
        }

        $limit = $request->filled('limit')
            ? min(max((int) $request->input('limit'), 1), 50)
            : min(max((int) config('services.external_rss.default_limit', 20), 1), 50);

        $sourceId = $request->filled('source')
            ? $request->string('source')->toString()
            : null;

        $payload = $externalRssFeedService->publicEnvelope($limit, $sourceId);

        return response()->json([
            'success' => true,
            'data' => [
                'sources' => $payload['sources'],
                'items' => $payload['items'],
            ],
            'meta' => [
                'enabled' => true,
                'syncedAt' => $payload['syncedAt'],
                'source' => $payload['source'],
            ],
        ]);
    }
}
