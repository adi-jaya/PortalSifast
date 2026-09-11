<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMappingRowRequest;
use App\Http\Requests\Admin\SyncPortalUsersRequest;
use App\Http\Requests\Admin\SyncUserPortalsRequest;
use App\Http\Requests\Admin\UpdateMappingCredentialRequest;
use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\AdminPortalMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminPortalMappingController extends Controller
{
    public function __construct(
        private AdminPortalMappingService $mappingService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage', Portal::class);

        $portalId = $request->filled('portal_id') ? (int) $request->input('portal_id') : null;
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $viewMode = (string) $request->input('view_mode', 'portal');

        $data = $this->mappingService->getMappingData($portalId, $userId, $viewMode, $request->all());

        return Inertia::render('admin/portals/mapping', $data);
    }

    public function saveRow(SaveMappingRowRequest $request): JsonResponse|RedirectResponse
    {
        $credential = $this->mappingService->saveSingleAssignment(
            (int) $request->validated('portal_id'),
            (int) $request->validated('user_id'),
            (bool) $request->validated('has_access'),
            (string) ($request->validated('credential_type') ?? 'use_shared'),
            $request->validated('notes')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'credential' => $credential ? [
                    'id' => $credential->id,
                    'portal_id' => $credential->portal_id,
                    'user_id' => $credential->user_id,
                    'credential_type' => $credential->credential_type,
                    'is_active' => $credential->is_active,
                    'notes' => $credential->notes,
                ] : null,
            ]);
        }

        return back()->with('success', 'Mapping hak akses berhasil diperbarui.');
    }

    public function syncPortal(SyncPortalUsersRequest $request): RedirectResponse
    {
        $portal = Portal::findOrFail($request->validated('portal_id'));
        $this->mappingService->syncPortalUsers($portal, $request->validated('assignments'));

        return back()->with('success', 'Mapping hak akses portal berhasil disimpan.');
    }

    public function syncUser(SyncUserPortalsRequest $request): RedirectResponse
    {
        $user = User::findOrFail($request->validated('user_id'));
        $this->mappingService->syncUserPortals($user, $request->validated('assignments'));

        return back()->with('success', 'Hak akses portal petugas berhasil disimpan.');
    }

    public function updateCredential(UpdateMappingCredentialRequest $request, UserPortalCredential $credential): RedirectResponse
    {
        $this->mappingService->updateCredential($credential, $request->validated());

        return back()->with('success', 'Kredensial mapping berhasil diperbarui.');
    }

    public function destroyCredential(UserPortalCredential $credential): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->mappingService->destroyCredential($credential);

        return back()->with('success', 'Hak akses portal berhasil dicabut.');
    }
}
