<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use Illuminate\Support\Facades\DB;

class UpdateAsetUnit
{
    public function __construct(
        private PengaturanPenyusutanAset $pengaturanPenyusutan,
        private KatalogAsetBarang $katalog,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Aset $aset, array $validated): Aset
    {
        return DB::transaction(function () use ($aset, $validated): Aset {
            $status = PemetaanStatusAset::dariInputForm(
                $validated['status_fungsi'] ?? $aset->status_fungsi,
                $validated['tingkat_kerusakan'] ?? $aset->tingkat_kerusakan,
            );

            $previousBarangId = $aset->aset_barang_id;
            $targetBarangId = isset($validated['aset_barang_id'])
                ? (int) $validated['aset_barang_id']
                : $aset->aset_barang_id;

            if ($targetBarangId) {
                $targetBarangId = $this->applyCatalogChanges(
                    $aset,
                    $targetBarangId,
                    $validated,
                );
            }

            $aset->update([
                'aset_barang_id' => $targetBarangId,
                'aset_ruang_id' => $validated['aset_ruang_id'],
                'aset_distributor_id' => $validated['aset_distributor_id'] ?? null,
                'tahun_registrasi' => $validated['tahun_registrasi'],
                'asal_barang' => $validated['asal_barang'] ?? null,
                'tanggal_pengadaan' => $validated['tanggal_pengadaan'] ?? null,
                'harga' => $validated['harga'] ?? null,
                'kondisi' => $status['kondisi'],
                'status_fungsi' => $status['status_fungsi'],
                'tingkat_kerusakan' => $status['tingkat_kerusakan'],
                'no_seri' => filled($validated['no_seri'] ?? null) ? $validated['no_seri'] : null,
            ]);

            if ($previousBarangId) {
                AsetBarang::hitungUlangJumlah((int) $previousBarangId);
            }
            if ($targetBarangId && $targetBarangId !== $previousBarangId) {
                AsetBarang::hitungUlangJumlah($targetBarangId);
            }

            return $aset->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyCatalogChanges(Aset $aset, int $barangId, array $validated): int
    {
        $barang = AsetBarang::query()->findOrFail($barangId);
        $payload = $this->catalogPayload($validated);
        $keys = $this->katalog->identityKeys($validated, $payload);

        if (! $this->katalog->dipakaiUnitLain($barang, $aset->id) || ! $this->katalog->berbeda($barang, $payload, $keys)) {
            $barang->update($payload);

            return $barang->id;
        }

        return ($this->katalog->cariIdentik($barang, $payload, $keys) ?? $this->katalog->salin($barang, $payload))->id;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function catalogPayload(array $validated): array
    {
        $resolved = $this->pengaturanPenyusutan->resolveUntukAset(
            $validated['harga'] ?? null,
            isset($validated['umur_ekonomis_bulan']) ? (int) $validated['umur_ekonomis_bulan'] : null,
            $validated['nilai_residu'] ?? null,
            $validated['kelas_aset'] ?? null,
        );

        $payload = [
            'kelas_aset' => $validated['kelas_aset'] ?? null,
            'wajib_kalibrasi' => array_key_exists('wajib_kalibrasi', $validated)
                ? (bool) $validated['wajib_kalibrasi']
                : false,
            'umur_ekonomis_bulan' => $resolved['umur_bulan'],
            'aset_kategori_id' => $validated['aset_kategori_id'] ?? null,
            'aset_jenis_id' => $validated['aset_jenis_id'] ?? null,
            'aset_merk_id' => $validated['aset_merk_id'] ?? null,
            'aset_produsen_id' => $validated['aset_produsen_id'] ?? null,
            'aset_aspak_alat_id' => $validated['aset_aspak_alat_id'] ?? null,
            'aset_non_alkes_id' => $validated['aset_non_alkes_id'] ?? null,
            'no_akl_akd' => $validated['no_akl_akd'] ?? null,
            'daya_watt' => $validated['daya_watt'] ?? null,
            'level_teknologi' => $validated['level_teknologi'] ?? null,
            'tahun_produksi' => $validated['tahun_produksi'] ?? null,
            'tahun_mulai_operasi' => $validated['tahun_mulai_operasi'] ?? null,
            'nilai_residu' => $resolved['nilai_residu'],
        ];

        if (array_key_exists('nama_barang', $validated) && filled($validated['nama_barang'])) {
            $payload['nama_barang'] = $validated['nama_barang'];
        }

        return $payload;
    }
}
