<?php

namespace App\Policies;

use App\Models\Portal;
use App\Models\User;

class PortalPolicy
{
    /**
     * Menentukan apakah user berhak mengelola master portal (Admin/SuperAdmin).
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function viewAny(User $user): bool
    {
        return true; // Setiap user login dapat membuka halaman portal agregator
    }

    public function viewAnyAdmin(User $user): bool
    {
        return $this->manage($user);
    }

    /**
     * Menentukan apakah user berhak melihat detail portal tertentu.
     */
    public function view(User $user, Portal $portal): bool
    {
        if (! $portal->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Portal $portal): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Portal $portal): bool
    {
        return $this->manage($user);
    }

    /**
     * Menentukan apakah user berhak menerima dispatch token & kredensial ke ekstensi.
     */
    public function dispatchToken(User $user, Portal $portal): bool
    {
        if (! $portal->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Menentukan apakah user berhak memperbarui kredensial personal untuk portal ini.
     */
    public function updatePersonalCredential(User $user, Portal $portal): bool
    {
        if (! $portal->is_active || ! $portal->supportsPersonal()) {
            return false;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
