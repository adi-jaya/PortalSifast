<?php

namespace App\Services\Driver;

use App\Models\DriverPemeriksaan;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BatalkanPemeriksaanDriver
{
    public function handle(DriverPemeriksaan $pemeriksaan, User $actor): DriverPemeriksaan
    {
        if (! $pemeriksaan->isSelesai()) {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Pemeriksaan ini sudah dibatalkan.',
            ]);
        }

        $canCancel = $actor->canManageDriverMaster()
            || $actor->canCoordinateChecklistKendaraan()
            || $pemeriksaan->petugas_id === $actor->id;

        if (! $canCancel) {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Anda tidak berhak membatalkan pemeriksaan ini.',
            ]);
        }

        $pemeriksaan->update([
            'status' => DriverPemeriksaan::STATUS_DIBATALKAN,
        ]);

        return $pemeriksaan->fresh(['details.checklistItem', 'kendaraan', 'petugas']);
    }
}
