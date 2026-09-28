<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;

/**
 * Copy-on-write untuk master barang: master yang dipakai unit lain tidak boleh ditimpa
 * spesifikasinya (merk, tipe, produsen, dll.) karena akan mengubah semua unit tersebut.
 */
class KatalogAsetBarang
{
    private const IDENTITY_KEYS = [
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

    /**
     * Umur/residu hanya ikut dibandingkan bila diisi manual; nilai default hasil resolve
     * tidak boleh memecah master.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public function identityKeys(array $input, array $payload): array
    {
        $keys = self::IDENTITY_KEYS;

        if (filled($payload['nama_barang'] ?? null)) {
            $keys[] = 'nama_barang';
        }
        if (filled($input['umur_ekonomis_bulan'] ?? null)) {
            $keys[] = 'umur_ekonomis_bulan';
        }
        if (filled($input['nilai_residu'] ?? null)) {
            $keys[] = 'nilai_residu';
        }

        return array_values(array_filter($keys, fn (string $key) => array_key_exists($key, $payload)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    public function berbeda(AsetBarang $barang, array $payload, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->normalize($barang->getAttribute($key)) !== $this->normalize($payload[$key])) {
                return true;
            }
        }

        return false;
    }

    public function dipakaiUnitLain(AsetBarang $barang, ?int $kecualiAsetId = null): bool
    {
        return Aset::query()
            ->where('aset_barang_id', $barang->id)
            ->when($kecualiAsetId !== null, fn ($q) => $q->where('id', '!=', $kecualiAsetId))
            ->exists();
    }

    /**
     * Cari master lain dengan spesifikasi identik agar tidak membuat salinan duplikat.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    public function cariIdentik(AsetBarang $sumber, array $payload, array $keys): ?AsetBarang
    {
        return AsetBarang::query()
            ->whereKeyNot($sumber->id)
            ->where('nama_barang', $payload['nama_barang'] ?? $sumber->nama_barang)
            ->get()
            ->first(fn (AsetBarang $kandidat) => ! $this->berbeda($kandidat, $payload, $keys));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function salin(AsetBarang $sumber, array $payload): AsetBarang
    {
        unset($payload['kode_barang']);

        $salinan = $sumber->replicate();
        $salinan->kode_barang = $this->kodeBarangUnik((string) $sumber->kode_barang);
        $salinan->jumlah = 0;
        $salinan->hash_sumber = null;
        $salinan->disinkron_pada = null;
        $salinan->sumber_hilang_pada = null;
        $salinan->fill($payload);
        $salinan->save();

        return $salinan;
    }

    private function kodeBarangUnik(string $sumber): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9.\-]/', '', $sumber) ?: 'CLONE';
        $base = preg_replace('/U\d+$/', '', $cleaned) ?: $cleaned;
        $n = 1;

        do {
            $suffix = 'U'.$n;
            $kode = substr($base, 0, max(1, 20 - strlen($suffix))).$suffix;
            $n++;
        } while (AsetBarang::withTrashed()->where('kode_barang', $kode)->exists());

        return $kode;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            $number = $value + 0;

            return is_float($number) && floor($number) !== $number
                ? round($number, 2)
                : (int) $number;
        }

        return $value;
    }
}
