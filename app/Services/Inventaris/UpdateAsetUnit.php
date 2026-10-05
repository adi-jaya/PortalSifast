<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use Illuminate\Support\Facades\DB;

class UpdateAsetUnit
{
    private const CATALOG_FIELDS = [
        'kelas_aset',
        'aset_kategori_id',
        'aset_jenis_id',
        'aset_merk_id',
        'aset_produsen_id',
        'aset_aspak_alat_id',
        'aset_non_alkes_id',
        'no_akl_akd',
        'daya_watt',
        'level_teknologi',
        'tahun_produksi',
        'tahun_mulai_operasi',
    ];

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
                $barang = AsetBarang::query()->findOrFail($targetBarangId);
                $targetBarangId = $this->katalog->terapkanUntukUnit(
                    $barang,
                    $this->catalogPayload($barang, $validated),
                    $validated,
                    $aset->id,
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
     * Hanya field yang dikirim form yang ikut diubah; field yang tidak dikirim
     * (mis. aset_non_alkes_id, form edit tidak punya input-nya) tetap memakai nilai master.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function catalogPayload(AsetBarang $barang, array $validated): array
    {
        $payload = array_intersect_key($validated, array_flip(self::CATALOG_FIELDS));

        if (array_key_exists('wajib_kalibrasi', $validated)) {
            $payload['wajib_kalibrasi'] = (bool) $validated['wajib_kalibrasi'];
        }

        if (filled($validated['nama_barang'] ?? null)) {
            $payload['nama_barang'] = $validated['nama_barang'];
        }

        $resolved = $this->pengaturanPenyusutan->resolveUntukAset(
            $validated['harga'] ?? null,
            isset($validated['umur_ekonomis_bulan']) ? (int) $validated['umur_ekonomis_bulan'] : null,
            $validated['nilai_residu'] ?? null,
            $payload['kelas_aset'] ?? $barang->kelas_aset,
        );

        $payload['umur_ekonomis_bulan'] = $resolved['umur_bulan'];
        $payload['nilai_residu'] = $resolved['nilai_residu'];

        return $payload;
    }
}
