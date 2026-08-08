<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialPolyclinicRequest;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialPolyclinicRequest;
use App\Models\WebOfficialPolyclinic;
use App\Services\Simrs\PolyclinicService;
use App\Services\WebOfficial\WebOfficialPolyclinicPersister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialPolyclinicAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialPolyclinic::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('simrs_name', 'like', $search)
                    ->orWhere('name_override', 'like', $search)
                    ->orWhere('short_description', 'like', $search);
            });
        }

        $query->orderBy('sort_order')->orderBy('simrs_name');
        $paginator = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

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

    public function available(PolyclinicService $polyclinicService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $polyclinicService->listAllActive(),
        ]);
    }

    public function show(WebOfficialPolyclinic $poliklinik): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $poliklinik->toAdminDetailArray(),
        ]);
    }

    public function store(
        StoreWebOfficialPolyclinicRequest $request,
        WebOfficialPolyclinicPersister $persister,
    ): JsonResponse {
        $poliklinik = new WebOfficialPolyclinic;
        $persister->fill($poliklinik, $request->validated(), isCreate: true);
        $poliklinik->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $poliklinik->id,
                'slug' => $poliklinik->slug,
                'kdPoli' => $poliklinik->kd_poli,
                'createdAt' => $poliklinik->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(
        StoreWebOfficialPolyclinicRequest $request,
        WebOfficialPolyclinic $poliklinik,
        WebOfficialPolyclinicPersister $persister,
    ): JsonResponse {
        $persister->fill($poliklinik, $request->validated(), isCreate: false);
        $poliklinik->save();

        return response()->json([
            'success' => true,
            'data' => $poliklinik->fresh()->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(
        UpdateWebOfficialPolyclinicRequest $request,
        WebOfficialPolyclinic $poliklinik,
        WebOfficialPolyclinicPersister $persister,
    ): JsonResponse {
        $persister->fill($poliklinik, $request->validated(), isCreate: false);
        $poliklinik->save();

        return response()->json([
            'success' => true,
            'data' => $poliklinik->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialPolyclinic $poliklinik): JsonResponse
    {
        $poliklinik->is_active = false;
        $poliklinik->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $poliklinik->id,
                'deleted' => true,
            ],
        ]);
    }
}
