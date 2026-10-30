<?php

namespace App\Support;

use App\Models\User;
use App\Services\SyncUserSimrsNikFromEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverPetugasResolver
{
    /**
     * Resolve identitas petugas (NIK) tanpa mengecek flag Driver.
     * Dipakai GET /me agar client bisa membaca can_* = false.
     *
     * @return array{0: User|null, 1: JsonResponse|null}
     */
    public function resolveIdentity(Request $request): array
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
                'hint' => 'Token service wajib kirim NIK: query ?nik= atau ?simrs_nik=, atau header X-Sifast-Nik / X-Nik. Pola sama tiket/payroll.',
                'example_query' => '/api/sifast/driver/me?nik=03.09.07.1998',
            ], 422)];
        }

        $petugas = User::query()->where('simrs_nik', $nik)->first();

        if ($petugas === null) {
            return [null, response()->json([
                'success' => false,
                'message' => 'User portal dengan NIK tersebut tidak ditemukan.',
                'hint' => 'Pastikan pegawai sudah punya akun PortalSifast dengan simrs_nik terisi (sama seperti tiket). Superadmin lalu aktifkan flag Driver.',
            ], 404)];
        }

        return [$petugas, null];
    }

    /**
     * Resolve petugas yang boleh input pemeriksaan Driver.
     *
     * @return array{0: User|null, 1: JsonResponse|null}
     */
    public function resolve(Request $request): array
    {
        [$petugas, $error] = $this->resolveIdentity($request);
        if ($error !== null) {
            return [null, $error];
        }

        if (! $petugas->canCreateDriverPemeriksaan()) {
            return [null, response()->json([
                'success' => false,
                'message' => 'Pegawai ini belum diberi akses input checklist kendaraan.',
                'hint' => 'Di PortalSifast → Users → centang "Izinkan input pemeriksaan (petugas)". Setelah itu GET /api/sifast/driver/me?nik=... harus can_create_driver_pemeriksaan=true.',
                'data' => [
                    'can_access_checklist_kendaraan' => $petugas->canAccessChecklistKendaraan(),
                    'can_create_driver_pemeriksaan' => false,
                    'nik' => $petugas->simrs_nik,
                ],
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
