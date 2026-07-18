<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Enums\WebOfficialArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialArticleRequest;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialArticleRequest;
use App\Models\WebOfficialArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialArticleAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialArticle::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search);
            });
        }

        $sort = $request->string('sort', '-published_at')->toString();
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowedSorts = ['published_at', 'created_at', 'title'];
        if (! in_array($column, $allowedSorts, true)) {
            $column = 'published_at';
            $direction = 'desc';
        }
        $query->orderBy($column, $direction);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map->toAdminListArray()->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(WebOfficialArticle $article): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $article->toAdminDetailArray(),
        ]);
    }

    public function store(StoreWebOfficialArticleRequest $request): JsonResponse
    {
        $article = new WebOfficialArticle;
        $this->fillArticle($article, $request->validated(), isCreate: true);
        $article->applyPublishedTimestamp();
        $article->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'status' => $article->status?->value,
                'createdAt' => $article->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(StoreWebOfficialArticleRequest $request, WebOfficialArticle $article): JsonResponse
    {
        $this->fillArticle($article, $request->validated(), isCreate: false);
        $article->applyPublishedTimestamp();
        $article->save();

        return response()->json([
            'success' => true,
            'data' => $article->fresh()->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(UpdateWebOfficialArticleRequest $request, WebOfficialArticle $article): JsonResponse
    {
        $this->fillArticle($article, $request->validated(), isCreate: false);
        $article->applyPublishedTimestamp();
        $article->save();

        return response()->json([
            'success' => true,
            'data' => $article->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialArticle $article): JsonResponse
    {
        $article->status = WebOfficialArticleStatus::Archived;
        $article->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $article->id,
                'deleted' => true,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillArticle(WebOfficialArticle $article, array $validated, bool $isCreate): void
    {
        if (array_key_exists('title', $validated)) {
            $article->title = $validated['title'];
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $article->slug = $validated['slug'];
        } elseif ($isCreate && isset($validated['title'])) {
            $article->slug = WebOfficialArticle::generateUniqueSlug($validated['title']);
        } elseif (array_key_exists('title', $validated) && ! array_key_exists('slug', $validated)) {
            $article->slug = WebOfficialArticle::generateUniqueSlug($validated['title'], $article->id);
        }

        if (array_key_exists('category', $validated)) {
            $article->category = $validated['category'];
        }

        if (array_key_exists('excerpt', $validated)) {
            $article->excerpt = $validated['excerpt'];
        }

        if (array_key_exists('body', $validated)) {
            $article->body = $validated['body'];
        }

        if (array_key_exists('cover', $validated)) {
            $article->cover_url = $validated['cover'];
        }

        if (array_key_exists('validUntil', $validated)) {
            $article->valid_until = $validated['validUntil'];
        }

        if (array_key_exists('status', $validated)) {
            $article->status = $validated['status'];
        } elseif ($isCreate) {
            $article->status = WebOfficialArticleStatus::Draft;
        }
    }
}
