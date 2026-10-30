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
        'nama_barang',
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
     * Terapkan perubahan master untuk satu unit dan kembalikan id master yang harus dipakai unit itu.
     * Field yang tidak ada di $payload dianggap tidak berubah.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $input
     */
    public function terapkanUntukUnit(AsetBarang $barang, array $payload, array $input, ?int $kecualiAsetId = null): int
    {
        $payload = array_merge($barang->only(self::IDENTITY_KEYS), $payload);

        if (! $this->dipakaiUnitLain($barang, $kecualiAsetId)) {
            $barang->update($payload);

            return $barang->id;
        }

        $keys = $this->identityKeys($input);

        if (! $this->berbeda($barang, $payload, $keys)) {
            return $barang->id;
        }

        return ($this->cariIdentik($barang, $payload, $keys) ?? $this->salin($barang, $payload))->id;
    }

    /**
     * Sidik jari spesifikasi lengkap (termasuk umur & residu) untuk mendeteksi master duplikat.
     */
    public function sidikJari(AsetBarang $barang): string
    {
        $keys = [...self::IDENTITY_KEYS, 'umur_ekonomis_bulan', 'nilai_residu'];

        return (string) json_encode(array_map(fn (string $key) => $this->normalize($barang->getAttribute($key)), $keys));
    }

    /**
     * Umur/residu hanya ikut dibandingkan bila diisi manual; nilai default hasil resolve
     * tidak boleh memecah master.
     *
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    private function identityKeys(array $input): array
    {
        $keys = self::IDENTITY_KEYS;

        if (filled($input['umur_ekonomis_bulan'] ?? null)) {
            $keys[] = 'umur_ekonomis_bulan';
        }
        if (filled($input['nilai_residu'] ?? null)) {
            $keys[] = 'nilai_residu';
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function berbeda(AsetBarang $barang, array $payload, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->normalize($barang->getAttribute($key)) !== $this->normalize($payload[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function dipakaiUnitLain(AsetBarang $barang, ?int $kecualiAsetId): bool
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
    private function cariIdentik(AsetBarang $sumber, array $payload, array $keys): ?AsetBarang
    {
        return AsetBarang::query()
            ->whereKeyNot($sumber->id)
            ->where('nama_barang', $payload['nama_barang'])
            ->get()
            ->first(fn (AsetBarang $kandidat) => ! $this->berbeda($kandidat, $payload, $keys));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function salin(AsetBarang $sumber, array $payload): AsetBarang
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
