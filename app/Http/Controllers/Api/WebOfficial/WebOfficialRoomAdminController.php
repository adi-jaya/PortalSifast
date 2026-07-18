<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialRoomRequest;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialRoomRequest;
use App\Models\WebOfficialRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialRoomAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialRoom::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $sort = $request->string('sort', 'sort_order')->toString();
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowedSorts = ['sort_order', 'created_at', 'name', 'price'];
        if (! in_array($column, $allowedSorts, true)) {
            $column = 'sort_order';
            $direction = 'asc';
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

    public function show(WebOfficialRoom $room): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $room->toAdminDetailArray(),
        ]);
    }

    public function store(StoreWebOfficialRoomRequest $request): JsonResponse
    {
        $room = new WebOfficialRoom;
        $this->fillRoom($room, $request->validated(), isCreate: true);
        $room->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $room->id,
                'slug' => $room->slug,
                'name' => $room->name,
                'price' => $room->price,
                'isActive' => $room->is_active,
                'createdAt' => $room->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(StoreWebOfficialRoomRequest $request, WebOfficialRoom $room): JsonResponse
    {
        $this->fillRoom($room, $request->validated(), isCreate: false);
        $room->save();

        return response()->json([
            'success' => true,
            'data' => $room->fresh()->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(UpdateWebOfficialRoomRequest $request, WebOfficialRoom $room): JsonResponse
    {
        $this->fillRoom($room, $request->validated(), isCreate: false);
        $room->save();

        return response()->json([
            'success' => true,
            'data' => $room->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialRoom $room): JsonResponse
    {
        $room->is_active = false;
        $room->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $room->id,
                'deleted' => true,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillRoom(WebOfficialRoom $room, array $validated, bool $isCreate): void
    {
        if (array_key_exists('name', $validated)) {
            $room->name = $validated['name'];
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $room->slug = $validated['slug'];
        } elseif ($isCreate && isset($validated['name'])) {
            $room->slug = WebOfficialRoom::generateUniqueSlug($validated['name']);
        } elseif (array_key_exists('name', $validated) && ! array_key_exists('slug', $validated)) {
            $room->slug = WebOfficialRoom::generateUniqueSlug($validated['name'], $room->id);
        }

        if (array_key_exists('tagline', $validated)) {
            $room->tagline = $validated['tagline'];
        }

        if (array_key_exists('badge', $validated)) {
            $room->badge = $validated['badge'];
        }

        if (array_key_exists('description', $validated)) {
            $room->description = $validated['description'];
        }

        if (array_key_exists('price', $validated)) {
            $room->price = $validated['price'];
        }

        if (array_key_exists('photo', $validated)) {
            $room->photo_url = $validated['photo'];
        }

        if (array_key_exists('facilities', $validated)) {
            $room->facilities = $validated['facilities'];
        }

        if (array_key_exists('sortOrder', $validated)) {
            $room->sort_order = $validated['sortOrder'];
        } elseif ($isCreate) {
            $room->sort_order = WebOfficialRoom::nextSortOrder();
        }

        if (array_key_exists('isActive', $validated)) {
            $room->is_active = $validated['isActive'];
        } elseif ($isCreate) {
            $room->is_active = true;
        }
    }
}
