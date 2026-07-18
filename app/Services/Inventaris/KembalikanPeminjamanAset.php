<?php

namespace App\Services\Inventaris;

use App\Models\AsetPeminjaman;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KembalikanPeminjamanAset
{
    public function handle(AsetPeminjaman $peminjaman, User $actor, ?string $kondisiKembali = null): AsetPeminjaman
    {
        return DB::transaction(function () use ($peminjaman, $actor, $kondisiKembali) {
            $peminjaman = AsetPeminjaman::query()->lockForUpdate()->findOrFail($peminjaman->id);

            if ($peminjaman->status !== 'dipinjam') {
                throw ValidationException::withMessages(['status' => 'Peminjaman sudah dikembalikan.']);
            }

            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali_aktual' => now(),
                'diterima_kembali_oleh_user_id' => $actor->id,
                'kondisi_kembali' => $kondisiKembali,
            ]);

            $peminjaman->aset()->update(['status_ketersediaan' => 'tersedia']);

            return $peminjaman->fresh(['aset']);
        });
    }
}
