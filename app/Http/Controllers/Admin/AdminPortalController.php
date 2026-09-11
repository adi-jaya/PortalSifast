<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortalRequest;
use App\Models\Portal;
use App\Services\Portal\AdminPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminPortalController extends Controller
{
    public function __construct(
        private AdminPortalService $portalService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage', Portal::class);

        $data = $this->portalService->paginatePortals($request->all());

        return Inertia::render('admin/portals/index', $data);
    }

    public function create(): Response
    {
        Gate::authorize('manage', Portal::class);

        return Inertia::render('admin/portals/create', $this->portalService->getFormData());
    }

    public function store(PortalRequest $request): RedirectResponse
    {
        $this->portalService->storePortal($request->validated());

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil ditambahkan.');
    }

    public function edit(Portal $portal): Response
    {
        Gate::authorize('manage', Portal::class);

        return Inertia::render('admin/portals/edit', [
            'portal' => $this->portalService->formatPortalForEdit($portal),
            'categories' => $this->portalService->getFormData()['categories'],
        ]);
    }

    public function update(PortalRequest $request, Portal $portal): RedirectResponse
    {
        $this->portalService->updatePortal($portal, $request->validated());

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil diperbarui.');
    }

    public function destroy(Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->destroyPortal($portal);

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil dihapus.');
    }

    public function toggleActive(Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->toggleActive($portal);

        return back()->with('success', 'Status portal berhasil diubah.');
    }
}
