<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\Instagram\InstagramFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramFeedController extends Controller
{
    public function index(Request $request, InstagramFeedService $instagramFeedService): JsonResponse
    {
        if (! config('services.instagram.enabled')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'profileUrl' => (string) config('services.instagram.profile_url'),
                    'posts' => [],
                ],
                'meta' => [
                    'enabled' => false,
                    'syncedAt' => null,
                ],
            ]);
        }

        $limit = $request->filled('limit')
            ? min(max((int) $request->input('limit'), 1), 50)
            : null;

        $payload = $instagramFeedService->publicEnvelope($limit);

        return response()->json([
            'success' => true,
            'data' => [
                'profileUrl' => $payload['profileUrl'],
                'posts' => $payload['posts'],
            ],
            'meta' => [
                'enabled' => true,
                'syncedAt' => $payload['syncedAt'],
                'source' => $payload['source'],
            ],
        ]);
    }
}
