<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalCredentialRequest;
use App\Models\Portal;
use App\Models\UserPortalCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PortalPersonalCredentialController extends Controller
{
    public function update(UpdatePersonalCredentialRequest $request, Portal $portal): JsonResponse
    {
        Gate::authorize('updatePersonalCredential', $portal);

        /** @var UserPortalCredential $credential */
        $credential = UserPortalCredential::where('user_id', $request->user()->id)
            ->where('portal_id', $portal->id)
            ->firstOrFail();

        $updateData = [
            'credential_type' => 'personal',
            'personal_username' => $request->validated('username'),
        ];

        if ($request->filled('password')) {
            $updateData['personal_password'] = $request->validated('password');
        }

        if ($request->has('extra_fields')) {
            $updateData['personal_extra_fields'] = $request->validated('extra_fields');
        }

        $credential->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Kredensial personal portal berhasil disimpan.',
        ]);
    }
}
