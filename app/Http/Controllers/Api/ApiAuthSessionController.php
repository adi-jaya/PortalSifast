<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthSessionController extends Controller
{
    /**
     * Hapus token Sanctum yang sedang dipakai (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->revokeBearerToken($request);
        $this->logoutWebSession($request);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Perpanjang sesi: hapus token lama, terbitkan token baru.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $this->revokeBearerToken($request);
        $this->logoutWebSession($request);

        $newToken = $user->createToken('api-login')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $newToken,
            ],
            'message' => 'Token berhasil diperbarui.',
        ]);
    }

    private function revokeBearerToken(Request $request): void
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken === null) {
            $request->user()?->currentAccessToken()?->delete();

            return;
        }

        PersonalAccessToken::findToken($bearerToken)?->delete();
    }

    private function logoutWebSession(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
