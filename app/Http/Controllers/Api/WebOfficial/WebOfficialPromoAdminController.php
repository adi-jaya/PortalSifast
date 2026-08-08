<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialPromoRequest;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialPromoRequest;
use App\Models\WebOfficialPromo;
use App\Services\WebOfficial\WebOfficialPromoPersister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialPromoAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialPromo::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search);
            });
        }

        $query->orderBy('sort_order')->orderByDesc('created_at');

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

    public function show(WebOfficialPromo $promo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $promo->toAdminDetailArray(),
        ]);
    }

    public function store(StoreWebOfficialPromoRequest $request, WebOfficialPromoPersister $persister): JsonResponse
    {
        $promo = new WebOfficialPromo;
        $persister->fill($promo, $request->validated(), isCreate: true);
        $promo->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $promo->id,
                'slug' => $promo->slug,
                'title' => $promo->title,
                'createdAt' => $promo->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(
        StoreWebOfficialPromoRequest $request,
        WebOfficialPromo $promo,
        WebOfficialPromoPersister $persister,
    ): JsonResponse {
        $persister->fill($promo, $request->validated(), isCreate: false);
        $promo->save();

        return response()->json([
            'success' => true,
            'data' => $promo->fresh()->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(
        UpdateWebOfficialPromoRequest $request,
        WebOfficialPromo $promo,
        WebOfficialPromoPersister $persister,
    ): JsonResponse {
        $persister->fill($promo, $request->validated(), isCreate: false);
        $promo->save();

        return response()->json([
            'success' => true,
            'data' => $promo->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialPromo $promo): JsonResponse
    {
        $promo->is_active = false;
        $promo->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $promo->id,
                'deleted' => true,
            ],
        ]);
    }
}
