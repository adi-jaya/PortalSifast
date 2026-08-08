<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialPartnerRequest;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialPartnerRequest;
use App\Models\WebOfficialPartner;
use App\Services\WebOfficial\WebOfficialPartnerPersister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialPartnerAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialPartner::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('category', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $query->orderBy('sort_order')->orderBy('name');

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

    public function show(WebOfficialPartner $partner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $partner->toAdminDetailArray(),
        ]);
    }

    public function store(StoreWebOfficialPartnerRequest $request, WebOfficialPartnerPersister $persister): JsonResponse
    {
        $partner = new WebOfficialPartner;
        $persister->fill($partner, $request->validated(), isCreate: true);
        $partner->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $partner->id,
                'slug' => $partner->slug,
                'name' => $partner->name,
                'createdAt' => $partner->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(
        StoreWebOfficialPartnerRequest $request,
        WebOfficialPartner $partner,
        WebOfficialPartnerPersister $persister,
    ): JsonResponse {
        $persister->fill($partner, $request->validated(), isCreate: false);
        $partner->save();

        return response()->json([
            'success' => true,
            'data' => $partner->fresh()->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(
        UpdateWebOfficialPartnerRequest $request,
        WebOfficialPartner $partner,
        WebOfficialPartnerPersister $persister,
    ): JsonResponse {
        $persister->fill($partner, $request->validated(), isCreate: false);
        $partner->save();

        return response()->json([
            'success' => true,
            'data' => $partner->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialPartner $partner): JsonResponse
    {
        $partner->is_active = false;
        $partner->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $partner->id,
                'deleted' => true,
            ],
        ]);
    }
}
