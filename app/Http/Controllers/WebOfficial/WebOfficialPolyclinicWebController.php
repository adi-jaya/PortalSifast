<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\StoreWebOfficialPolyclinicWebRequest;
use App\Http\Requests\WebOfficial\UpdateWebOfficialPolyclinicWebRequest;
use App\Models\WebOfficialPolyclinic;
use App\Services\Simrs\PolyclinicService;
use App\Services\WebOfficial\WebOfficialPolyclinicPersister;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialPolyclinicWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebOfficialPolyclinic::query()->orderBy('sort_order')->orderBy('simrs_name');

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('simrs_name', 'like', $search)
                    ->orWhere('name_override', 'like', $search)
                    ->orWhere('short_description', 'like', $search);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $polyclinics = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/poliklinik/index', [
            'polyclinics' => $polyclinics->through(fn (WebOfficialPolyclinic $polyclinic): array => [
                'id' => $polyclinic->id,
                'kd_poli' => $polyclinic->kd_poli,
                'slug' => $polyclinic->slug,
                'name' => $polyclinic->displayName(),
                'sort_order' => $polyclinic->sort_order,
                'is_active' => $polyclinic->is_active,
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    public function create(PolyclinicService $polyclinicService): Response
    {
        return Inertia::render('web-official/poliklinik/create', [
            'availablePolyclinics' => $polyclinicService->listAllActive(),
        ]);
    }

    public function store(
        StoreWebOfficialPolyclinicWebRequest $request,
        WebOfficialPolyclinicPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $poliklinik = new WebOfficialPolyclinic;
        $persister->fill($poliklinik, $this->mapWebPayload($request->validated()), isCreate: true);

        if ($request->hasFile('photo_file')) {
            $persister->applyPhotoUpload($poliklinik, $request->file('photo_file'), $storage);
        }

        $poliklinik->save();

        return redirect()
            ->route('web-official.poliklinik.index')
            ->with('success', 'Konten poliklinik berhasil ditambahkan.');
    }

    public function edit(WebOfficialPolyclinic $poliklinik, PolyclinicService $polyclinicService): Response
    {
        return Inertia::render('web-official/poliklinik/edit', [
            'polyclinic' => [
                'id' => $poliklinik->id,
                'kd_poli' => $poliklinik->kd_poli,
                'slug' => $poliklinik->slug,
                'label' => $poliklinik->label,
                'simrs_name' => $poliklinik->simrs_name,
                'name_override' => $poliklinik->name_override,
                'short_description' => $poliklinik->short_description,
                'long_description' => $poliklinik->long_description,
                'photo' => $poliklinik->photo_url,
                'icon' => $poliklinik->icon,
                'sort_order' => $poliklinik->sort_order,
                'is_active' => $poliklinik->is_active,
            ],
            'availablePolyclinics' => $polyclinicService->listAllActive(),
        ]);
    }

    public function update(
        UpdateWebOfficialPolyclinicWebRequest $request,
        WebOfficialPolyclinic $poliklinik,
        WebOfficialPolyclinicPersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $persister->fill($poliklinik, $this->mapWebPayload($request->validated()), isCreate: false);

        if ($request->hasFile('photo_file')) {
            $persister->applyPhotoUpload($poliklinik, $request->file('photo_file'), $storage);
        }

        $poliklinik->save();

        return redirect()
            ->route('web-official.poliklinik.index')
            ->with('success', 'Konten poliklinik berhasil diperbarui.');
    }

    public function destroy(WebOfficialPolyclinic $poliklinik): RedirectResponse
    {
        $poliklinik->is_active = false;
        $poliklinik->save();

        return redirect()
            ->route('web-official.poliklinik.index')
            ->with('success', 'Konten poliklinik berhasil dinonaktifkan.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapWebPayload(array $validated): array
    {
        return [
            'kdPoli' => $validated['kd_poli'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'label' => $validated['label'] ?? null,
            'nameOverride' => $validated['name_override'] ?? null,
            'shortDescription' => $validated['short_description'] ?? null,
            'longDescription' => $validated['long_description'] ?? null,
            'photo' => $validated['photo'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'sortOrder' => $validated['sort_order'] ?? null,
            'isActive' => $validated['is_active'] ?? null,
        ];
    }
}
