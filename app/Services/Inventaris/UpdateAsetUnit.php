<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use Illuminate\Support\Facades\DB;

class UpdateAsetUnit
{
    public function __construct(
        private PengaturanPenyusutanAset $pengaturanPenyusutan,
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

        $sharedWithOthers = Aset::query()
            ->where('aset_barang_id', $barang->id)
            ->where('id', '!=', $aset->id)
            ->exists();

        if (! $sharedWithOthers || ! $this->identityCatalogChanged($barang, $payload, $validated)) {
            $barang->update($payload);

            return $barang->id;
        }

        $clone = $barang->replicate();
        $clone->kode_barang = $this->kodeBarangUnik((string) $barang->kode_barang);
        $clone->jumlah = 0;
        $clone->hash_sumber = null;
        $clone->disinkron_pada = null;
        $clone->sumber_hilang_pada = null;
        $clone->fill($payload);
        $clone->save();

        return $clone->id;
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

    /**
     * Clone hanya jika field identitas katalog berubah.
     * Umur/residu hasil resolve default tidak memicu clone (hindari pecah master saat edit SN/harga saja).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    private function identityCatalogChanged(AsetBarang $barang, array $payload, array $validated): bool
    {
        $keys = [
            'kelas_aset',
            'wajib_kalibrasi',
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

        if (array_key_exists('nama_barang', $payload)) {
            $keys[] = 'nama_barang';
        }
        if (array_key_exists('umur_ekonomis_bulan', $validated)) {
            $keys[] = 'umur_ekonomis_bulan';
        }
        if (array_key_exists('nilai_residu', $validated)) {
            $keys[] = 'nilai_residu';
        }

        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            if ($this->normalizeComparable($barang->getAttribute($key)) !== $this->normalizeComparable($payload[$key])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeComparable(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value) && ! is_string($value)) {
            return is_float($value + 0) && ! is_int($value + 0)
                ? round((float) $value, 2)
                : (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.')
                ? round((float) $value, 2)
                : (int) $value;
        }

        return $value;
    }

    private function kodeBarangUnik(string $sumber): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9.\-]/', '', $sumber) ?: 'CLONE';
        $base = substr($cleaned, 0, 14);
        $n = 1;

        do {
            $suffix = 'U'.$n;
            $kode = substr($base, 0, max(1, 20 - strlen($suffix))).$suffix;
            $n++;
        } while (AsetBarang::withTrashed()->where('kode_barang', $kode)->exists());

        return $kode;
    }
}
