<?php

namespace App\Services\WebOfficial;

use App\Models\WebOfficialPolyclinic;
use App\Services\Simrs\PolyclinicService;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\UploadedFile;

class WebOfficialPolyclinicPersister
{
    public function __construct(
        private readonly PolyclinicService $polyclinicService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function fill(WebOfficialPolyclinic $polyclinic, array $validated, bool $isCreate): void
    {
        if (array_key_exists('kdPoli', $validated)) {
            $polyclinic->kd_poli = (string) $validated['kdPoli'];
        }

        if (array_key_exists('label', $validated)) {
            $polyclinic->label = filled($validated['label']) ? (string) $validated['label'] : 'KLINIK SPESIALIS';
        } elseif ($isCreate && blank($polyclinic->label)) {
            $polyclinic->label = 'KLINIK SPESIALIS';
        }

        if (array_key_exists('nameOverride', $validated)) {
            $polyclinic->name_override = filled($validated['nameOverride']) ? (string) $validated['nameOverride'] : null;
        }

        if (array_key_exists('shortDescription', $validated)) {
            $polyclinic->short_description = (string) $validated['shortDescription'];
        }

        if (array_key_exists('longDescription', $validated)) {
            $polyclinic->long_description = filled($validated['longDescription']) ? (string) $validated['longDescription'] : null;
        }

        if (array_key_exists('photo', $validated)) {
            $polyclinic->photo_url = filled($validated['photo']) ? (string) $validated['photo'] : null;
        }

        if (array_key_exists('icon', $validated)) {
            $polyclinic->icon = filled($validated['icon']) ? (string) $validated['icon'] : null;
        }

        if (array_key_exists('sortOrder', $validated)) {
            $polyclinic->sort_order = (int) $validated['sortOrder'];
        } elseif ($isCreate) {
            $polyclinic->sort_order = WebOfficialPolyclinic::nextSortOrder();
        }

        if (array_key_exists('isActive', $validated)) {
            $polyclinic->is_active = (bool) $validated['isActive'];
        } elseif ($isCreate) {
            $polyclinic->is_active = true;
        }

        $effectiveName = $this->resolveSimrsName($polyclinic->kd_poli);
        if ($effectiveName !== null) {
            $polyclinic->simrs_name = $effectiveName;
        } elseif ($isCreate && blank($polyclinic->simrs_name)) {
            $polyclinic->simrs_name = (string) ($polyclinic->name_override ?: $polyclinic->kd_poli);
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $polyclinic->slug = (string) $validated['slug'];
        } else {
            $slugSource = $polyclinic->name_override ?: $polyclinic->simrs_name;
            if ($isCreate || array_key_exists('nameOverride', $validated) || ! array_key_exists('slug', $validated)) {
                $polyclinic->slug = WebOfficialPolyclinic::generateUniqueSlug((string) $slugSource, $polyclinic->id);
            }
        }
    }

    public function applyPhotoUpload(
        WebOfficialPolyclinic $polyclinic,
        UploadedFile $file,
        WebOfficialMediaStorageService $storage,
    ): void {
        $stored = $storage->store($file, 'poliklinik');
        $polyclinic->photo_url = $stored['url'];
    }

    private function resolveSimrsName(?string $kdPoli): ?string
    {
        if ($kdPoli === null || $kdPoli === '') {
            return null;
        }

        try {
            return $this->polyclinicService->findName($kdPoli);
        } catch (\Throwable) {
            return null;
        }
    }
}
