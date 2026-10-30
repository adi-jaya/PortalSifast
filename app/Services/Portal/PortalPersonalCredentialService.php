<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;

class PortalPersonalCredentialService
{
    /**
     * Memperbarui kredensial akun personal milik staf untuk portal yang diizinkan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePersonalCredential(User $user, Portal $portal, array $data): UserPortalCredential
    {
        /** @var UserPortalCredential $credential */
        $credential = UserPortalCredential::where('user_id', $user->id)
            ->where('portal_id', $portal->id)
            ->firstOrFail();

        $updateData = [
            'credential_type' => 'personal',
            'personal_username' => $data['username'],
        ];

        if (filled($data['password'] ?? null)) {
            $updateData['personal_password'] = $data['password'];
        }

        if (array_key_exists('extra_fields', $data)) {
            $updateData['personal_extra_fields'] = $data['extra_fields'];
        }

        $credential->update($updateData);

        return $credential;
    }
}
