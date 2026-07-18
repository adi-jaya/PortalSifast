<?php

namespace App\Services\WebOfficial;

use App\Models\WebOfficialPromo;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\UploadedFile;

class WebOfficialPromoPersister
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function fill(WebOfficialPromo $promo, array $validated, bool $isCreate): void
    {
        if (array_key_exists('title', $validated)) {
            $promo->title = $validated['title'];
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $promo->slug = $validated['slug'];
        } elseif ($isCreate && isset($validated['title'])) {
            $promo->slug = WebOfficialPromo::generateUniqueSlug($validated['title']);
        } elseif (array_key_exists('title', $validated) && ! array_key_exists('slug', $validated)) {
            $promo->slug = WebOfficialPromo::generateUniqueSlug($validated['title'], $promo->id);
        }

        if (array_key_exists('label', $validated)) {
            $promo->label = $validated['label'] ?: 'PROMO SPESIAL';
        } elseif ($isCreate && blank($promo->label)) {
            $promo->label = 'PROMO SPESIAL';
        }

        if (array_key_exists('excerpt', $validated)) {
            $promo->excerpt = $validated['excerpt'];
        }

        if (array_key_exists('body', $validated)) {
            $promo->body = $validated['body'];
        }

        if (array_key_exists('cover', $validated)) {
            $promo->cover_url = $validated['cover'];
        }

        if (array_key_exists('start_date', $validated)) {
            $promo->start_date = $validated['start_date'];
        } elseif (array_key_exists('startDate', $validated)) {
            $promo->start_date = $validated['startDate'];
        }

        if (array_key_exists('end_date', $validated)) {
            $promo->end_date = $validated['end_date'];
        } elseif (array_key_exists('endDate', $validated)) {
            $promo->end_date = $validated['endDate'];
        }

        if (array_key_exists('is_featured', $validated)) {
            $promo->is_featured = (bool) $validated['is_featured'];
        } elseif (array_key_exists('isFeatured', $validated)) {
            $promo->is_featured = (bool) $validated['isFeatured'];
        } elseif ($isCreate) {
            $promo->is_featured = false;
        }

        if (array_key_exists('sort_order', $validated)) {
            $promo->sort_order = (int) $validated['sort_order'];
        } elseif (array_key_exists('sortOrder', $validated)) {
            $promo->sort_order = (int) $validated['sortOrder'];
        } elseif ($isCreate) {
            $promo->sort_order = WebOfficialPromo::nextSortOrder();
        }

        if (array_key_exists('is_active', $validated)) {
            $promo->is_active = (bool) $validated['is_active'];
        } elseif (array_key_exists('isActive', $validated)) {
            $promo->is_active = (bool) $validated['isActive'];
        } elseif ($isCreate) {
            $promo->is_active = true;
        }
    }

    public function applyCoverUpload(
        WebOfficialPromo $promo,
        UploadedFile $file,
        WebOfficialMediaStorageService $storage,
    ): void {
        $stored = $storage->store($file, 'promosi');
        $promo->cover_url = $stored['url'];
    }
}
