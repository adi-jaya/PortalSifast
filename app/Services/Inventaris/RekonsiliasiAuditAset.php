<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AuditAset;
use App\Models\AuditAsetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekonsiliasiAuditAset
{
    public function setujui(AuditAset $audit, User $penyetuju, ?string $catatan = null): AuditAset
    {
        if ($audit->status === 'disetujui') {
            return $audit;
        }

        if ($audit->status !== 'selesai') {
            throw ValidationException::withMessages([
                'status' => 'Audit harus berstatus selesai sebelum disetujui.',
            ]);
        }

        $belumDicek = $audit->items()->whereNull('hasil')->count();
        if ($belumDicek > 0) {
            throw ValidationException::withMessages([
                'status' => "Masih ada {$belumDicek} item belum dicek.",
            ]);
        }

        return DB::transaction(function () use ($audit, $penyetuju, $catatan) {
            $audit->items()->with('aset')->each(function (AuditAsetItem $item) use ($penyetuju, $audit) {
                $this->terapkanItem($item, $penyetuju, $audit);
            });

            $ringkasan = $audit->hitungRingkasan();

            $audit->update([
                'status' => 'disetujui',
                'disetujui_oleh' => $penyetuju->id,
                'disetujui_pada' => now(),
                'catatan' => $catatan ?? $audit->catatan,
                'ringkasan' => $ringkasan,
            ]);

            return $audit->fresh(['ruang', 'items', 'pemula', 'penyetuju']);
        });
    }

    private function terapkanItem(AuditAsetItem $item, User $penyetuju, AuditAset $audit): void
    {
        $aset = $item->aset;
        if (! $aset instanceof Aset) {
            return;
        }

        $lama = [
            'kondisi' => $aset->kondisi,
            'siklus_hidup' => $aset->siklus_hidup,
            'aset_ruang_id' => $aset->aset_ruang_id,
        ];

        $baru = $lama;
        $keterangan = null;

        match ($item->hasil) {
            'ditemukan' => $this->terapkanDitemukan($aset, $item, $baru, $keterangan),
            'tidak_ditemukan' => $this->terapkanTidakDitemukan($aset, $baru, $keterangan),
            'salah_ruang' => $this->terapkanSalahRuang($aset, $item, $baru, $keterangan),
            default => null,
        };

        if ($baru === $lama) {
            return;
        }

        $aset->fill([
            'kondisi' => $baru['kondisi'],
            'siklus_hidup' => $baru['siklus_hidup'],
            'aset_ruang_id' => $baru['aset_ruang_id'],
        ])->save();

        $aset->riwayat()->create([
            'pengguna_id' => $penyetuju->id,
            'jenis_peristiwa' => 'audit_disetujui',
            'nilai_lama' => $lama,
            'nilai_baru' => array_merge($baru, [
                'hasil_audit' => $item->hasil,
                'audit_aset_id' => $audit->id,
            ]),
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array{kondisi: ?string, siklus_hidup: string, aset_ruang_id: ?int}  $baru
     */
    private function terapkanDitemukan(Aset $aset, AuditAsetItem $item, array &$baru, ?string &$keterangan): void
    {
        if ($item->kondisi_aktual) {
            $baru['kondisi'] = $item->kondisi_aktual;
        }

        if ($aset->siklus_hidup === 'hilang') {
            $baru['siklus_hidup'] = 'aktif';
        }

        $keterangan = 'Hasil audit: ditemukan';
    }

    /**
     * @param  array{kondisi: ?string, siklus_hidup: string, aset_ruang_id: ?int}  $baru
     */
    private function terapkanTidakDitemukan(Aset $aset, array &$baru, ?string &$keterangan): void
    {
        $baru['kondisi'] = 'Hilang';
        $baru['siklus_hidup'] = 'hilang';
        $keterangan = 'Hasil audit: tidak ditemukan';
    }

    /**
     * @param  array{kondisi: ?string, siklus_hidup: string, aset_ruang_id: ?int}  $baru
     */
    private function terapkanSalahRuang(Aset $aset, AuditAsetItem $item, array &$baru, ?string &$keterangan): void
    {
        if ($item->aset_ruang_ditemukan_id) {
            $baru['aset_ruang_id'] = $item->aset_ruang_ditemukan_id;
        }

        if ($item->kondisi_aktual) {
            $baru['kondisi'] = $item->kondisi_aktual;
        }

        if ($aset->siklus_hidup === 'hilang') {
            $baru['siklus_hidup'] = 'aktif';
        }

        $keterangan = 'Hasil audit: salah ruang (kode aset tetap)';
    }
}
