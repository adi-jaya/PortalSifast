<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\SaveBioLinkRequest;
use App\Http\Requests\WebOfficial\SaveBioSocialsRequest;
use App\Models\WebOfficialBioLink;
use App\Models\WebOfficialBioSocial;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialBioLinkWebController extends Controller
{
    public function index(): Response
    {
        $links = WebOfficialBioLink::query()->orderBy('sort_order')->orderBy('created_at')->get();

        return Inertia::render('web-official/bio-links/index', [
            'links' => $links->map->toAdminArray()->values()->all(),
            'socials' => WebOfficialBioSocial::adminList(),
            'icons' => WebOfficialBioLink::ICONS,
            'maxActive' => WebOfficialBioLink::MAX_ACTIVE,
        ]);
    }

    public function store(SaveBioLinkRequest $request, WebOfficialMediaStorageService $storage): RedirectResponse
    {
        WebOfficialBioLink::query()->create([
            'sort_order' => WebOfficialBioLink::nextSortOrder(),
            ...$request->toAttributes($storage),
        ]);

        return back()->with('success', 'Tautan berhasil ditambahkan.');
    }

    public function update(SaveBioLinkRequest $request, WebOfficialBioLink $bioLink, WebOfficialMediaStorageService $storage): RedirectResponse
    {
        $bioLink->update($request->toAttributes($storage));

        return back()->with('success', 'Tautan berhasil diperbarui.');
    }

    public function destroy(WebOfficialBioLink $bioLink): RedirectResponse
    {
        $bioLink->delete();

        return back()->with('success', 'Tautan berhasil dihapus.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid', 'distinct', 'exists:web_official_bio_links,id'],
        ]);

        WebOfficialBioLink::reorder($validated['ids']);

        return back();
    }

    public function updateSocials(SaveBioSocialsRequest $request): RedirectResponse
    {
        WebOfficialBioSocial::saveAll($request->validated('socials'));

        return back()->with('success', 'Media sosial berhasil disimpan.');
    }
}
