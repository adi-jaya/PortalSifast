<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetMutasiLokasi;
use App\Models\AsetRuang;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatMutasiLokasiAset
{
    public function __construct(private GeneratorNomorDokumenAset $nomor) {}

    public function handle(
        Aset $aset,
        User $actor,
        int $ruangTujuanId,
        ?int $penerimaUserId,
        ?string $penerimaNik,
        mixed $tanggalMutasi,
        ?string $catatan,
    ): AsetMutasiLokasi {
        $this->assertAktor($penerimaUserId, $penerimaNik);

        return DB::transaction(function () use ($aset, $actor, $ruangTujuanId, $penerimaUserId, $penerimaNik, $tanggalMutasi, $catatan) {
            $aset = Aset::query()->lockForUpdate()->findOrFail($aset->id);

            if ($aset->siklus_hidup !== 'aktif') {
                throw ValidationException::withMessages(['aset_id' => 'Aset harus berstatus aktif.']);
            }
            if ($aset->status_ketersediaan !== 'tersedia') {
                throw ValidationException::withMessages(['aset_id' => 'Aset sedang dipinjam; tidak bisa dimutasi.']);
            }
            if (! $aset->aset_ruang_id) {
                throw ValidationException::withMessages(['aset_id' => 'Aset belum memiliki ruang asal.']);
            }
            if ((int) $aset->aset_ruang_id === $ruangTujuanId) {
                throw ValidationException::withMessages(['aset_ruang_tujuan_id' => 'Ruang tujuan harus berbeda dari ruang asal.']);
            }

            AsetRuang::query()->findOrFail($ruangTujuanId);

            $mutasi = AsetMutasiLokasi::query()->create([
                'nomor' => $this->nomor->mutasi(),
                'aset_id' => $aset->id,
                'aset_ruang_asal_id' => $aset->aset_ruang_id,
                'aset_ruang_tujuan_id' => $ruangTujuanId,
                'penerima_user_id' => $penerimaUserId,
                'penerima_nik' => $penerimaNik,
                'dicatat_oleh_user_id' => $actor->id,
                'tanggal_mutasi' => $tanggalMutasi,
                'catatan' => $catatan,
            ]);

            $aset->update(['aset_ruang_id' => $ruangTujuanId]);

            return $mutasi;
        });
    }

    private function assertAktor(?int $userId, ?string $nik): void
    {
        $hasUser = $userId !== null;
        $hasNik = filled($nik);
        if ($hasUser === $hasNik) {
            throw ValidationException::withMessages([
                'penerima' => 'Pilih tepat satu: user portal atau pegawai (NIK).',
            ]);
        }
    }
}
