<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

final class TelegramRequesterSearch
{
    /**
     * Cari user portal (sama kriteria tickets/search-for-user), max hasil untuk tombol Telegram.
     *
     * @return Collection<int, User>
     */
    public function search(string $query, int $limit = 5): Collection
    {
        $q = trim($query);
        if ($q === '' || mb_strlen($q) < 2) {
            return collect();
        }

        return User::query()
            ->where(function ($queryBuilder) use ($q) {
                $queryBuilder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('simrs_nik', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'simrs_nik', 'role', 'dep_id']);
    }

    public function buttonLabel(User $user): string
    {
        $meta = $user->simrs_nik ?: ($user->dep_id ?: $user->role);
        $label = $user->name.($meta ? ' · '.$meta : '');

        return mb_strlen($label) > 64 ? mb_substr($label, 0, 61).'...' : $label;
    }
}
