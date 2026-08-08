<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\StoreWebOfficialPromoWebRequest;
use App\Http\Requests\WebOfficial\UpdateWebOfficialPromoWebRequest;
use App\Models\WebOfficialPromo;
use App\Services\WebOfficial\WebOfficialPromoPersister;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialPromoWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebOfficialPromo::query()->orderBy('sort_order')->orderByDesc('updated_at');

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $promos = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/promosi/index', [
            'promos' => $promos->through(fn (WebOfficialPromo $promo): array => [
                'id' => $promo->id,
                'slug' => $promo->slug,
                'title' => $promo->title,
                'label' => $promo->label,
                'is_featured' => $promo->is_featured,
                'is_active' => $promo->is_active,
                'sort_order' => $promo->sort_order,
                'start_date' => $promo->start_date?->toIso8601String(),
                'end_date' => $promo->end_date?->toIso8601String(),
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('web-official/promosi/create');
    }

    public function store(
        StoreWebOfficialPromoWebRequest $request,
        WebOfficialPromoPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $promo = new WebOfficialPromo;
        $persister->fill($promo, $request->validated(), isCreate: true);

        if ($request->hasFile('cover_file')) {
            $persister->applyCoverUpload($promo, $request->file('cover_file'), $storage);
        }

        $promo->save();

        return redirect()
            ->route('web-official.promosi.index')
            ->with('success', 'Promo berhasil ditambahkan.');
    }

    public function edit(WebOfficialPromo $promosi): Response
    {
        return Inertia::render('web-official/promosi/edit', [
            'promo' => [
                'id' => $promosi->id,
                'slug' => $promosi->slug,
                'title' => $promosi->title,
                'label' => $promosi->label,
                'excerpt' => $promosi->excerpt,
                'body' => $promosi->body,
                'cover' => $promosi->cover_url,
                'start_date' => $promosi->start_date?->format('Y-m-d\TH:i'),
                'end_date' => $promosi->end_date?->format('Y-m-d\TH:i'),
                'is_featured' => $promosi->is_featured,
                'sort_order' => $promosi->sort_order,
                'is_active' => $promosi->is_active,
            ],
        ]);
    }

    public function update(
        UpdateWebOfficialPromoWebRequest $request,
        WebOfficialPromo $promosi,
        WebOfficialPromoPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $persister->fill($promosi, $request->validated(), isCreate: false);

        if ($request->hasFile('cover_file')) {
            $persister->applyCoverUpload($promosi, $request->file('cover_file'), $storage);
        }

        $promosi->save();

        return redirect()
            ->route('web-official.promosi.index')
            ->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(WebOfficialPromo $promosi): RedirectResponse
    {
        $promosi->is_active = false;
        $promosi->save();

        return redirect()
            ->route('web-official.promosi.index')
            ->with('success', 'Promo berhasil dinonaktifkan.');
    }
}
