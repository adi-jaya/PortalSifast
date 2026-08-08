<?php

namespace App\Support;

use App\Models\User;
use App\Services\SyncUserSimrsNikFromEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatroliPetugasResolver
{
    /**
     * Resolve petugas patroli by NIK (query/header) or authenticated user simrs_nik.
     * Pola sama dengan payroll/tiket (service token + nik).
     *
     * @return array{0: User|null, 1: JsonResponse|null}
     */
    public function resolve(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser && blank($authUser->simrs_nik) && ! $authUser->isPayrollServiceIntegrationAccount()) {
            app(SyncUserSimrsNikFromEmailService::class)($authUser);
            $authUser->refresh();
        }

        $nikParam = $this->nikFromRequest($request);
        $nik = $nikParam !== '' ? $nikParam : (string) ($authUser?->simrs_nik ?? '');

        if (trim($nik) === '') {
            return [null, response()->json([
                'success' => false,
                'message' => 'Parameter nik wajib diisi atau akun Anda belum terhubung dengan NIK kepegawaian.',
                'hint' => 'Token service wajib kirim NIK: query ?nik= atau ?simrs_nik=, atau header X-Sifast-Nik / X-Nik. Alternatif: Bearer token user yang punya simrs_nik.',
                'example_query' => '/api/sifast/patroli/checkin?nik=03.09.07.1998',
            ], 422)];
        }

        $petugas = User::query()->where('simrs_nik', $nik)->first();

        if ($petugas === null) {
            return [null, response()->json([
                'success' => false,
                'message' => 'User portal dengan NIK tersebut tidak ditemukan.',
                'hint' => 'Pastikan pegawai sudah punya akun PortalSifast dengan simrs_nik terisi.',
            ], 404)];
        }

        if (! $petugas->canAccessPatroli()) {
            return [null, response()->json([
                'success' => false,
                'message' => 'Pegawai ini belum diberi akses patroli.',
                'hint' => 'Superadmin harus mengaktifkan flag Akses Patroli pada user tersebut di PortalSifast.',
            ], 403)];
        }

        return [$petugas, null];
    }

    public function nikFromRequest(Request $request): string
    {
        foreach (['nik', 'simrs_nik'] as $key) {
            $v = trim($request->string($key)->toString());
            if ($v !== '') {
                return $v;
            }
        }

        foreach (['X-Sifast-Nik', 'X-Nik'] as $headerName) {
            $fromHeader = $request->header($headerName);
            if (is_string($fromHeader) && trim($fromHeader) !== '') {
                return trim($fromHeader);
            }
        }

        return '';
    }
}
