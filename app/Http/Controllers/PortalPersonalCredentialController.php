<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalCredentialRequest;
use App\Models\Portal;
use App\Services\Portal\PortalPersonalCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PortalPersonalCredentialController extends Controller
{
    public function __construct(
        private PortalPersonalCredentialService $credentialService,
    ) {}

    public function update(UpdatePersonalCredentialRequest $request, Portal $portal): JsonResponse
    {
        Gate::authorize('updatePersonalCredential', $portal);

        $this->credentialService->updatePersonalCredential($request->user(), $portal, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kredensial personal portal berhasil disimpan.',
        ]);
    }
}
