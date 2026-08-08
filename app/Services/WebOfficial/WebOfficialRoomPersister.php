<?php

namespace App\Services\WebOfficial;

use App\Models\WebOfficialRoom;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\UploadedFile;

final class WebOfficialRoomPersister
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function fill(WebOfficialRoom $room, array $validated, bool $isCreate): void
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

        if (array_key_exists('sort_order', $validated)) {
            $room->sort_order = $validated['sort_order'];
        } elseif ($isCreate) {
            $room->sort_order = WebOfficialRoom::nextSortOrder();
        }

        if (array_key_exists('is_active', $validated)) {
            $room->is_active = (bool) $validated['is_active'];
        } elseif ($isCreate) {
            $room->is_active = true;
        }
    }

    public function applyPhotoUpload(WebOfficialRoom $room, UploadedFile $file, WebOfficialMediaStorageService $storage): void
    {
        $result = $storage->store($file, 'kamar-inap');
        $room->photo_url = $result['url'];
    }

    /**
     * @return list<string>
     */
    public static function parseFacilitiesInput(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', $raw) ?: []
        )));
    }
}
