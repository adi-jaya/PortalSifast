<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetBarang;
use App\Models\AsetDokumen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merapikan master salinan hasil copy-on-write (kode "<kode>U<n>"):
 * memulihkan tautan katalog yang hilang dan menggabungkan master yang spesifikasinya identik.
 */
class RapikanMasterSalinan
{
    public function __construct(private KatalogAsetBarang $katalog) {}

    /**
     * @return array{
     *     tautan_dipulihkan: int,
     *     master_digabung: int,
     *     unit_dipindah: int,
     *     keluarga: list<array{kode_dasar: string, nama_barang: string, tautan_dipulihkan: int, master_digabung: int, unit_dipindah: int}>
     * }
     */
    public function jalankan(bool $terapkan): array
    {
        $laporan = ['tautan_dipulihkan' => 0, 'master_digabung' => 0, 'unit_dipindah' => 0, 'keluarga' => []];

        $keluarga = AsetBarang::query()
            ->orderBy('id')
            ->get()
            ->groupBy(fn (AsetBarang $barang) => $this->kodeDasar((string) $barang->kode_barang))
            ->filter(fn (Collection $anggota) => $anggota->count() > 1);

        foreach ($keluarga as $kodeDasar => $anggota) {
            $hasil = DB::transaction(fn () => $this->rapikanKeluarga((string) $kodeDasar, $anggota, $terapkan));

            if ($hasil['tautan_dipulihkan'] === 0 && $hasil['master_digabung'] === 0) {
                continue;
            }

            $laporan['tautan_dipulihkan'] += $hasil['tautan_dipulihkan'];
            $laporan['master_digabung'] += $hasil['master_digabung'];
            $laporan['unit_dipindah'] += $hasil['unit_dipindah'];
            $laporan['keluarga'][] = $hasil;
        }

        return $laporan;
    }

    /**
     * @param  Collection<int, AsetBarang>  $anggota
     * @return array{kode_dasar: string, nama_barang: string, tautan_dipulihkan: int, master_digabung: int, unit_dipindah: int}
     */
    private function rapikanKeluarga(string $kodeDasar, Collection $anggota, bool $terapkan): array
    {
        $sumber = $anggota->first(fn (AsetBarang $barang) => $barang->kode_barang === $kodeDasar);
        $hasil = [
            'kode_dasar' => $kodeDasar,
            'nama_barang' => (string) ($sumber ?? $anggota->first())->nama_barang,
            'tautan_dipulihkan' => 0,
            'master_digabung' => 0,
            'unit_dipindah' => 0,
        ];

        if ($sumber !== null && ($sumber->aset_non_alkes_id !== null || $sumber->aset_aspak_alat_id !== null)) {
            foreach ($anggota as $barang) {
                if ($barang->is($sumber)
                    || $barang->aset_non_alkes_id !== null
                    || $barang->aset_aspak_alat_id !== null
                    || $barang->nama_barang !== $sumber->nama_barang) {
                    continue;
                }

                $barang->aset_non_alkes_id = $sumber->aset_non_alkes_id;
                $barang->aset_aspak_alat_id = $sumber->aset_aspak_alat_id;
                $hasil['tautan_dipulihkan']++;

                if ($terapkan) {
                    $barang->save();
                }
            }
        }

        foreach ($anggota->groupBy(fn (AsetBarang $barang) => $this->katalog->sidikJari($barang)) as $kelompok) {
            if ($kelompok->count() < 2) {
                continue;
            }

            $penyimpan = $kelompok->first(fn (AsetBarang $barang) => $barang->kode_barang === $kodeDasar) ?? $kelompok->first();

            foreach ($kelompok as $barang) {
                if ($barang->is($penyimpan)) {
                    continue;
                }

                $hasil['master_digabung']++;
                $hasil['unit_dipindah'] += Aset::withTrashed()->where('aset_barang_id', $barang->id)->count();

                if ($terapkan) {
                    Aset::withTrashed()->where('aset_barang_id', $barang->id)->update(['aset_barang_id' => $penyimpan->id]);
                    AsetDokumen::query()->where('aset_barang_id', $barang->id)->update(['aset_barang_id' => $penyimpan->id]);
                    $barang->delete();
                }
            }

            if ($terapkan) {
                AsetBarang::hitungUlangJumlah($penyimpan->id);
            }
        }

        return $hasil;
    }

    private function kodeDasar(string $kode): string
    {
        return preg_replace('/U\d+$/', '', $kode) ?: $kode;
    }
}
