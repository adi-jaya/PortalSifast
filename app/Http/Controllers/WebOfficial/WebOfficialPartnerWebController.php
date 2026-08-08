<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\StoreWebOfficialPartnerWebRequest;
use App\Http\Requests\WebOfficial\UpdateWebOfficialPartnerWebRequest;
use App\Models\WebOfficialPartner;
use App\Services\WebOfficial\WebOfficialPartnerPersister;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialPartnerWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebOfficialPartner::query()->orderBy('sort_order')->orderBy('name');

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        $partners = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/rekanan/index', [
            'partners' => $partners->through(fn (WebOfficialPartner $partner): array => [
                'id' => $partner->id,
                'slug' => $partner->slug,
                'name' => $partner->name,
                'category' => $partner->category,
                'logo' => $partner->logo_url,
                'website_url' => $partner->website_url,
                'is_active' => $partner->is_active,
                'sort_order' => $partner->sort_order,
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('web-official/rekanan/create', [
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(
        StoreWebOfficialPartnerWebRequest $request,
        WebOfficialPartnerPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $partner = new WebOfficialPartner;
        $validated = $request->validated();

        if ($request->hasFile('logo_file')) {
            unset($validated['logo']);
        }

        $persister->fill($partner, $this->mapWebPayload($validated), isCreate: true);

        if ($request->hasFile('logo_file')) {
            $persister->applyLogoUpload($partner, $request->file('logo_file'), $storage);
        }

        $partner->save();

        return redirect()
            ->route('web-official.rekanan.index')
            ->with('success', 'Rekanan berhasil ditambahkan.');
    }

    public function edit(WebOfficialPartner $rekanan): Response
    {
        return Inertia::render('web-official/rekanan/edit', [
            'partner' => [
                'id' => $rekanan->id,
                'slug' => $rekanan->slug,
                'name' => $rekanan->name,
                'category' => $rekanan->category,
                'description' => $rekanan->description,
                'logo' => $rekanan->logo_url,
                'website_url' => $rekanan->website_url,
                'sort_order' => $rekanan->sort_order,
                'is_active' => $rekanan->is_active,
            ],
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(
        UpdateWebOfficialPartnerWebRequest $request,
        WebOfficialPartner $rekanan,
        WebOfficialPartnerPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $validated = $request->validated();

        if ($request->hasFile('logo_file')) {
            unset($validated['logo']);
        }

        $persister->fill($rekanan, $this->mapWebPayload($validated), isCreate: false);

        if ($request->hasFile('logo_file')) {
            $persister->applyLogoUpload($rekanan, $request->file('logo_file'), $storage);
        }

        $rekanan->save();

        return redirect()
            ->route('web-official.rekanan.index')
            ->with('success', 'Rekanan berhasil diperbarui.');
    }

    public function destroy(WebOfficialPartner $rekanan): RedirectResponse
    {
        $rekanan->is_active = false;
        $rekanan->save();

        return redirect()
            ->route('web-official.rekanan.index')
            ->with('success', 'Rekanan berhasil dinonaktifkan.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'Asuransi', 'label' => 'Asuransi'],
            ['value' => 'Bank', 'label' => 'Bank'],
            ['value' => 'Perusahaan', 'label' => 'Perusahaan'],
            ['value' => 'Lembaga', 'label' => 'Lembaga'],
            ['value' => 'Lainnya', 'label' => 'Lainnya'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapWebPayload(array $validated): array
    {
        if (array_key_exists('logo', $validated)) {
            $validated['logo'] = $validated['logo'];
        }

        return $validated;
    }
}
