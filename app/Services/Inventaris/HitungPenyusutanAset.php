<?php

namespace App\Services\Inventaris;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HitungPenyusutanAset
{
    /**
     * Hitung penyusutan metode garis lurus (straight-line).
     *
     * Penyusutan/bulan = (perolehan - residu) / umur manfaat (bulan).
     * Nilai buku tidak turun di bawah nilai residu.
     *
     * @return array{
     *     metode: string,
     *     dapat_dihitung: bool,
     *     nilai_perolehan: float|null,
     *     nilai_residu: float,
     *     umur_manfaat_bulan: int|null,
     *     penyusutan_per_bulan: float|null,
     *     bulan_berjalan: int,
     *     akumulasi_penyusutan: float|null,
     *     nilai_buku: float|null,
     *     sisa_umur_bulan: int|null,
     *     persen_sisa: float|null
     * }
     */
    public function hitung(
        float|string|null $hargaPerolehan,
        ?CarbonInterface $tanggalMulai,
        ?int $umurManfaatBulan,
        float|string|null $nilaiResidu = null,
        ?CarbonInterface $sampai = null,
    ): array {
        $perolehan = $this->toFloat($hargaPerolehan);
        $residu = $this->toFloat($nilaiResidu) ?? 0.0;
        $sampai ??= Carbon::now();

        $kosong = [
            'metode' => 'garis_lurus',
            'dapat_dihitung' => false,
            'nilai_perolehan' => $perolehan,
            'nilai_residu' => $residu,
            'umur_manfaat_bulan' => $umurManfaatBulan,
            'penyusutan_per_bulan' => null,
            'bulan_berjalan' => 0,
            'akumulasi_penyusutan' => null,
            'nilai_buku' => $perolehan,
            'sisa_umur_bulan' => $umurManfaatBulan,
            'persen_sisa' => null,
        ];

        if ($perolehan === null || $tanggalMulai === null || ! $umurManfaatBulan || $umurManfaatBulan <= 0) {
            return $kosong;
        }

        $dasarPenyusutan = max(0.0, $perolehan - $residu);
        $penyusutanPerBulan = $dasarPenyusutan / $umurManfaatBulan;

        $bulanBerjalan = max(0, (int) $tanggalMulai->copy()->startOfDay()->diffInMonths($sampai->copy()->startOfDay()));
        $bulanEfektif = min($bulanBerjalan, $umurManfaatBulan);

        $akumulasi = round($penyusutanPerBulan * $bulanEfektif, 2);
        $nilaiBuku = round(max($residu, $perolehan - $akumulasi), 2);
        $sisaUmur = max(0, $umurManfaatBulan - $bulanBerjalan);
        $persenSisa = $perolehan > 0 ? round(($nilaiBuku / $perolehan) * 100, 1) : null;

        return [
            'metode' => 'garis_lurus',
            'dapat_dihitung' => true,
            'nilai_perolehan' => round($perolehan, 2),
            'nilai_residu' => round($residu, 2),
            'umur_manfaat_bulan' => $umurManfaatBulan,
            'penyusutan_per_bulan' => round($penyusutanPerBulan, 2),
            'bulan_berjalan' => $bulanBerjalan,
            'akumulasi_penyusutan' => $akumulasi,
            'nilai_buku' => $nilaiBuku,
            'sisa_umur_bulan' => $sisaUmur,
            'persen_sisa' => $persenSisa,
        ];
    }

    private function toFloat(float|string|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
