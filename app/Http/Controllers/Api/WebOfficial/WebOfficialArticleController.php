<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Models\WebOfficialArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialArticle::query()
            ->publishedPublic()
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $limit = min(max((int) $request->input('limit', 20), 1), 50);
        $articles = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $articles->map->toPublicListArray()->values()->all(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = WebOfficialArticle::query()
            ->publishedPublic()
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Artikel tidak ditemukan',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $article->toPublicDetailArray(),
        ]);
    }
}
