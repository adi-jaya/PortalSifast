<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Support\Facades\DB;

class AdminPortalMappingService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getMappingData(?int $selectedPortalId, ?int $selectedUserId, string $viewMode = 'portal', array $filters = []): array
    {
        $portals = Portal::ordered()->withCount('userCredentials')->get();

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'simrs_nik', 'role', 'dep_id'])
            ->orderBy('name');

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('simrs_nik', 'like', "%{$search}%");
            });
        }

        $department = (string) ($filters['department'] ?? '');
        if ($department !== '' && $department !== '_all') {
            $usersQuery->where('dep_id', $department);
        }

        $role = (string) ($filters['role'] ?? '');
        if ($role !== '' && $role !== '_all') {
            $usersQuery->where('role', $role);
        }

        $departments = User::query()
            ->whereNotNull('dep_id')
            ->where('dep_id', '!=', '')
            ->distinct()
            ->orderBy('dep_id')
            ->pluck('dep_id');

        $targetPortalId = $selectedPortalId ?? $portals->first()?->id ?? 0;
        $selectedPortal = $portals->firstWhere('id', $targetPortalId) ?? $portals->first();

        $selectedUser = ($selectedUserId && $selectedUserId > 0) ? User::find($selectedUserId) : null;

        $portalCredentials = [];
        if ($selectedPortal) {
            $portalCredentials = UserPortalCredential::where('portal_id', $selectedPortal->id)
                ->get()
                ->keyBy('user_id');
        }

        $userCredentials = [];
        if ($selectedUser) {
            $userCredentials = UserPortalCredential::where('user_id', $selectedUser->id)
                ->get()
                ->keyBy('portal_id');
        }

        return [
            'view_mode' => $viewMode,
            'portals' => $portals->map(fn (Portal $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'auth_type' => $p->auth_type,
                'is_active' => $p->is_active,
                'user_credentials_count' => $p->user_credentials_count ?? 0,
            ]),
            'selected_portal' => $selectedPortal ? [
                'id' => $selectedPortal->id,
                'name' => $selectedPortal->name,
                'category' => $selectedPortal->category,
                'auth_type' => $selectedPortal->auth_type,
                'is_active' => $selectedPortal->is_active,
            ] : null,
            'selected_user' => $selectedUser ? [
                'id' => $selectedUser->id,
                'name' => $selectedUser->name,
                'email' => $selectedUser->email,
                'simrs_nik' => $selectedUser->simrs_nik,
                'role' => $selectedUser->role,
                'dep_id' => $selectedUser->dep_id,
            ] : null,
            'users' => $usersQuery->paginate(50)->withQueryString(),
            'portal_credentials' => $portalCredentials,
            'user_credentials' => $userCredentials,
            'departments' => $departments,
            'filters' => [
                'portal_id' => $targetPortalId,
                'user_id' => $selectedUserId ?? 0,
                'search' => $search,
                'department' => $department,
                'role' => $role,
            ],
        ];
    }

    /**
     * @param  array<int, array{user_id: int, has_access: bool, credential_type?: string, notes?: string|null}>  $assignments
     */
    public function syncPortalUsers(Portal $portal, array $assignments): void
    {
        DB::transaction(function () use ($portal, $assignments): void {
            foreach ($assignments as $item) {
                $userId = (int) $item['user_id'];
                $hasAccess = (bool) $item['has_access'];

                if ($hasAccess) {
                    UserPortalCredential::updateOrCreate(
                        [
                            'portal_id' => $portal->id,
                            'user_id' => $userId,
                        ],
                        [
                            'credential_type' => $item['credential_type'] ?? 'use_shared',
                            'is_active' => true,
                            'notes' => $item['notes'] ?? null,
                        ]
                    );
                } else {
                    UserPortalCredential::where('portal_id', $portal->id)
                        ->where('user_id', $userId)
                        ->delete();
                }
            }
        });
    }

    /**
     * @param  array<int, array{portal_id: int, has_access: bool, credential_type?: string, notes?: string|null}>  $assignments
     */
    public function syncUserPortals(User $user, array $assignments): void
    {
        DB::transaction(function () use ($user, $assignments): void {
            foreach ($assignments as $item) {
                $portalId = (int) $item['portal_id'];
                $hasAccess = (bool) $item['has_access'];

                if ($hasAccess) {
                    UserPortalCredential::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'portal_id' => $portalId,
                        ],
                        [
                            'credential_type' => $item['credential_type'] ?? 'use_shared',
                            'is_active' => true,
                            'notes' => $item['notes'] ?? null,
                        ]
                    );
                } else {
                    UserPortalCredential::where('user_id', $user->id)
                        ->where('portal_id', $portalId)
                        ->delete();
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCredential(UserPortalCredential $credential, array $data): UserPortalCredential
    {
        $credential->update($data);

        return $credential;
    }

    public function saveSingleAssignment(int $portalId, int $userId, bool $hasAccess, string $credentialType = 'use_shared', ?string $notes = null): ?UserPortalCredential
    {
        if ($hasAccess) {
            $attributes = [
                'credential_type' => $credentialType,
                'is_active' => true,
            ];

            if ($notes !== null) {
                $attributes['notes'] = $notes;
            }

            return UserPortalCredential::updateOrCreate(
                [
                    'portal_id' => $portalId,
                    'user_id' => $userId,
                ],
                $attributes
            );
        }

        UserPortalCredential::where('portal_id', $portalId)
            ->where('user_id', $userId)
            ->delete();

        return null;
    }

    public function destroyCredential(UserPortalCredential $credential): bool
    {
        return (bool) $credential->delete();
    }
}
