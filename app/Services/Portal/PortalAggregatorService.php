<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Database\Eloquent\Builder;

class PortalAggregatorService
{
    /**
     * Mengambil daftar portal pelaporan aktif yang berhak diakses oleh user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserPortals(User $user, ?string $category = null, ?string $search = null): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $query = Portal::query()->where('is_active', true);

        if (! $isAdmin) {
            $query->whereHas('userCredentials', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->where('is_active', true);
            });
        }

        $query->with(['userCredentials' => function ($q) use ($user): void {
            $q->where('user_id', $user->id)
                ->where('is_active', true);
        }]);

        if (filled($category) && $category !== 'all') {
            $query->where('category', trim((string) $category));
        }

        if (filled($search)) {
            $term = trim((string) $search);
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        $portals = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return $portals->map(function (Portal $portal) use ($user): array {
            /** @var UserPortalCredential|null $credential */
            $credential = $portal->userCredentials->first();

            return $this->formatPortalCard($portal, $user, $credential);
        })->values()->all();
    }

    /**
     * Mengambil daftar kategori unik yang tersedia dari portal yang diizinkan untuk user ini.
     *
     * @return list<string>
     */
    public function getCategoriesForUser(User $user): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $query = Portal::query()->where('is_active', true);

        if (! $isAdmin) {
            $query->whereHas('userCredentials', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->where('is_active', true);
            });
        }

        /** @var list<string> $categories */
        $categories = $query->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn (string $cat): string => trim($cat))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $categories;
    }

    /**
     * Memformat objek Portal menjadi payload kartu responsif antarmuka pengguna.
     *
     * @return array<string, mixed>
     */
    public function formatPortalCard(Portal $portal, User $user, ?UserPortalCredential $credential = null): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $credentialType = $credential?->credential_type;

        // Fallback untuk admin: jika belum ada mapping eksplisit dan portal mendukung shared
        if (! $credentialType && $isAdmin && $portal->supportsShared()) {
            $credentialType = 'use_shared';
        }

        $canConfigurePersonal = $portal->supportsPersonal()
            && $credential !== null
            && (bool) $credential->is_active;

        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'slug' => $portal->slug,
            'category' => $portal->category,
            'url' => $portal->url,
            'url_pattern' => $portal->url_pattern,
            'icon_path' => $portal->icon_path,
            'icon_url' => $portal->icon_url,
            'description' => $portal->description,
            'auth_type' => $portal->auth_type,
            'credential_type' => $credentialType,
            'personal_username' => $credential?->personal_username,
            'has_personal_credential' => filled($credential?->personal_username),
            'can_configure_personal' => $canConfigurePersonal,
            'sort_order' => $portal->sort_order,
        ];
    }
}
