<?php

namespace App\Policies;

use App\Enums\DokumenStatus;
use App\Models\Dokumen;
use App\Models\User;

class DokumenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessTatanaskahModule();
    }

    public function view(User $user, Dokumen $dokumen): bool
    {
        return $user->canAccessTatanaskahModule();
    }

    public function create(User $user): bool
    {
        return $user->canBuatDokumen();
    }

    public function update(User $user, Dokumen $dokumen): bool
    {
        return $user->canBuatDokumen()
            && $dokumen->status === DokumenStatus::Draft
            && ($user->canManageTatanaskah() || $dokumen->dibuat_oleh === $user->id);
    }

    public function delete(User $user, Dokumen $dokumen): bool
    {
        return $user->canManageTatanaskah()
            && $dokumen->status === DokumenStatus::Draft;
    }

    public function transition(User $user, Dokumen $dokumen): bool
    {
        return match ($dokumen->status) {
            DokumenStatus::Draft => $user->canBuatDokumen()
                && ($user->canManageTatanaskah() || $dokumen->dibuat_oleh === $user->id),
            DokumenStatus::ReviewUnit, DokumenStatus::ReviewMutu => $user->canManageTatanaskah()
                || $user->canTteDokumen()
                || $dokumen->dibuat_oleh === $user->id,
            DokumenStatus::MenungguTte => $user->canTteDokumen()
                || $user->canManageTatanaskah()
                || $user->isPenandatanganFor($dokumen),
            default => $user->canManageTatanaskah(),
        };
    }
}
