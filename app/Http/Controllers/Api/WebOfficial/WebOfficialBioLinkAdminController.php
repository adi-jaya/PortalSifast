<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\SaveBioLinkRequest;
use App\Http\Requests\WebOfficial\SaveBioSocialsRequest;
use App\Models\WebOfficialBioLink;
use App\Models\WebOfficialBioSocial;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialBioLinkAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $links = WebOfficialBioLink::query()->orderBy('sort_order')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $links->map->toAdminArray()->values()->all(),
        ]);
    }

    public function show(WebOfficialBioLink $bioLink): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $bioLink->toAdminArray()]);
    }

    public function store(SaveBioLinkRequest $request, WebOfficialMediaStorageService $storage): JsonResponse
    {
        $link = WebOfficialBioLink::query()->create([
            'sort_order' => WebOfficialBioLink::nextSortOrder(),
            ...$request->toAttributes($storage),
        ]);

        return response()->json(['success' => true, 'data' => $link->fresh()->toAdminArray()], 201);
    }

    public function update(SaveBioLinkRequest $request, WebOfficialBioLink $bioLink, WebOfficialMediaStorageService $storage): JsonResponse
    {
        $bioLink->update($request->toAttributes($storage));

        return response()->json(['success' => true, 'data' => $bioLink->fresh()->toAdminArray()]);
    }

    public function destroy(WebOfficialBioLink $bioLink): JsonResponse
    {
        $bioLink->delete();

        return response()->json(['success' => true, 'data' => ['id' => $bioLink->id, 'deleted' => true]]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid', 'distinct', 'exists:web_official_bio_links,id'],
        ]);

        WebOfficialBioLink::reorder($validated['ids']);

        return $this->index();
    }

    public function socials(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => WebOfficialBioSocial::adminList()]);
    }

    public function updateSocials(SaveBioSocialsRequest $request): JsonResponse
    {
        WebOfficialBioSocial::saveAll($request->validated('socials'));

        return $this->socials();
    }
}
