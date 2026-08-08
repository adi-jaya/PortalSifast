<?php

namespace App\Services\WebOfficial;

use App\Models\WebOfficialPartner;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\UploadedFile;

final class WebOfficialPartnerPersister
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function fill(WebOfficialPartner $partner, array $validated, bool $isCreate): void
    {
        if (array_key_exists('name', $validated)) {
            $partner->name = $validated['name'];
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $partner->slug = $validated['slug'];
        } elseif ($isCreate && isset($validated['name'])) {
            $partner->slug = WebOfficialPartner::generateUniqueSlug($validated['name']);
        } elseif (array_key_exists('name', $validated) && ! array_key_exists('slug', $validated)) {
            $partner->slug = WebOfficialPartner::generateUniqueSlug($validated['name'], $partner->id);
        }

        if (array_key_exists('category', $validated)) {
            $partner->category = $validated['category'];
        }

        if (array_key_exists('description', $validated)) {
            $partner->description = $validated['description'];
        }

        if (array_key_exists('logo', $validated)) {
            $partner->logo_url = $validated['logo'];
        }

        if (array_key_exists('website_url', $validated) || array_key_exists('websiteUrl', $validated)) {
            $partner->website_url = $validated['website_url'] ?? $validated['websiteUrl'] ?? null;
        }

        if (array_key_exists('sort_order', $validated) || array_key_exists('sortOrder', $validated)) {
            $partner->sort_order = $validated['sort_order'] ?? $validated['sortOrder'];
        } elseif ($isCreate) {
            $partner->sort_order = WebOfficialPartner::nextSortOrder();
        }

        if (array_key_exists('is_active', $validated) || array_key_exists('isActive', $validated)) {
            $partner->is_active = (bool) ($validated['is_active'] ?? $validated['isActive']);
        } elseif ($isCreate) {
            $partner->is_active = true;
        }
    }

    public function applyLogoUpload(
        WebOfficialPartner $partner,
        UploadedFile $file,
        WebOfficialMediaStorageService $storage,
    ): void {
        $result = $storage->store($file, 'rekanan');
        $partner->logo_url = $result['url'];
    }
}
