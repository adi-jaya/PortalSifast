<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PortalDispatchService
{
    /**
     * Membangun payload aman one-time transfer kredensial untuk browser extension.
     *
     * @return array<string, mixed>
     */
    public function dispatch(User $user, Portal $portal): array
    {
        if (! $portal->is_active) {
            throw new AccessDeniedHttpException('Portal pelaporan ini sedang nonaktif.');
        }

        /** @var UserPortalCredential|null $credential */
        $credential = $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // Admin diizinkan menggunakan shared credential jika belum memiliki mapping eksplisit
        if (! $credential && ($user->isAdmin() || $user->isSuperAdmin())) {
            if (! $portal->supportsShared()) {
                throw new AccessDeniedHttpException('Portal ini bertipe personal dan memerlukan konfigurasi akun personal.');
            }

            return $this->buildSharedPayload($portal);
        }

        if (! $credential) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin akses aktif ke portal ini.');
        }

        if ($credential->isPersonal() && $portal->supportsPersonal()) {
            return [
                'success' => true,
                'portal' => $this->formatPortalMeta($portal),
                'credentials' => [
                    'type' => 'personal',
                    'username' => (string) $credential->personal_username,
                    'password' => (string) $credential->personal_password,
                    'extra_fields' => $credential->personal_extra_fields ?? [],
                ],
                'dispatched_at' => now()->toIso8601String(),
            ];
        }

        if (! $portal->supportsShared()) {
            throw new AccessDeniedHttpException('Portal ini bertipe personal dan memerlukan konfigurasi kredensial personal.');
        }

        return $this->buildSharedPayload($portal);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSharedPayload(Portal $portal): array
    {
        return [
            'success' => true,
            'portal' => $this->formatPortalMeta($portal),
            'credentials' => [
                'type' => 'shared',
                'username' => (string) $portal->shared_username,
                'password' => (string) $portal->shared_password,
                'extra_fields' => $portal->shared_extra_fields ?? [],
            ],
            'dispatched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPortalMeta(Portal $portal): array
    {
        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'slug' => $portal->slug,
            'category' => $portal->category,
            'url' => $portal->url,
            'url_pattern' => $portal->url_pattern,
            'form_config' => $portal->form_config ?? [],
        ];
    }
}
