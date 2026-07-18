<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetPeminjaman;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatPeminjamanAset
{
    public function __construct(private GeneratorNomorDokumenAset $nomor) {}

    public function handle(
        Aset $aset,
        User $actor,
        ?int $peminjamUserId,
        ?string $peminjamNik,
        mixed $tanggalPinjam,
        ?string $tanggalKembaliRencana,
        ?string $catatan,
    ): AsetPeminjaman {
        $this->assertAktor($peminjamUserId, $peminjamNik);

        return DB::transaction(function () use ($aset, $actor, $peminjamUserId, $peminjamNik, $tanggalPinjam, $tanggalKembaliRencana, $catatan) {
            $aset = Aset::query()->lockForUpdate()->findOrFail($aset->id);

            if ($aset->siklus_hidup !== 'aktif') {
                throw ValidationException::withMessages(['aset_id' => 'Aset harus berstatus aktif.']);
            }
            if ($aset->status_ketersediaan !== 'tersedia') {
                throw ValidationException::withMessages(['aset_id' => 'Aset sedang tidak tersedia untuk dipinjam.']);
            }
            if ($aset->peminjaman()->where('status', 'dipinjam')->exists()) {
                throw ValidationException::withMessages(['aset_id' => 'Aset masih memiliki peminjaman aktif.']);
            }

            $row = AsetPeminjaman::query()->create([
                'nomor' => $this->nomor->peminjaman(),
                'aset_id' => $aset->id,
                'peminjam_user_id' => $peminjamUserId,
                'peminjam_nik' => $peminjamNik,
                'diserahkan_oleh_user_id' => $actor->id,
                'tanggal_pinjam' => $tanggalPinjam,
                'tanggal_kembali_rencana' => $tanggalKembaliRencana,
                'status' => 'dipinjam',
                'catatan' => $catatan,
            ]);

            $aset->update(['status_ketersediaan' => 'dipinjam']);

            return $row;
        });
    }

    private function assertAktor(?int $userId, ?string $nik): void
    {
        $hasUser = $userId !== null;
        $hasNik = filled($nik);
        if ($hasUser === $hasNik) {
            throw ValidationException::withMessages([
                'peminjam' => 'Pilih tepat satu: user portal atau pegawai (NIK).',
            ]);
        }
    }
}
