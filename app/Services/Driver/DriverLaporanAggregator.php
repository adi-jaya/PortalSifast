<?php

namespace App\Services\Driver;

use App\Models\DriverKendaraan;
use App\Models\DriverPemeriksaan;
use App\Models\DriverPemeriksaanDetail;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DriverLaporanAggregator
{
    /**
     * @return array{
     *     tanggal: string,
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function harian(CarbonInterface $tanggal): array
    {
        $date = $tanggal->toDateString();
        $kendaraanList = DriverKendaraan::query()
            ->where('status', DriverKendaraan::STATUS_AKTIF)
            ->orderBy('nama')
            ->get();

        $pemeriksaan = DriverPemeriksaan::query()
            ->with('details')
            ->whereDate('tanggal', $date)
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->get()
            ->groupBy('driver_kendaraan_id');

        $rows = [];
        $belum = 0;
        $sudah = 0;
        $adaTemuan = 0;

        foreach ($kendaraanList as $kendaraan) {
            /** @var Collection<int, DriverPemeriksaan> $list */
            $list = $pemeriksaan->get($kendaraan->id, collect());
            $check1 = $list->firstWhere('pemeriksaan_ke', 1);
            $check2 = $list->firstWhere('pemeriksaan_ke', 2);
            $temuanCount = $list->sum(
                fn (DriverPemeriksaan $p) => $p->details->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK)->count()
            );

            $status = 'Belum';
            if ($list->isEmpty()) {
                $belum++;
            } elseif ($temuanCount > 0) {
                $status = $check2 ? 'Diperiksa 2× — Ada Temuan' : 'Ada Temuan';
                $adaTemuan++;
                $sudah++;
            } elseif ($check2) {
                $status = 'Diperiksa 2×';
                $sudah++;
            } else {
                $status = 'Sudah diperiksa';
                $sudah++;
            }

            $rows[] = [
                'kendaraan_id' => $kendaraan->id,
                'nama' => $kendaraan->nama,
                'foto_url' => $kendaraan->fotoUrl(),
                'check_1' => $check1 !== null,
                'check_2' => $check2 !== null,
                'temuan' => $temuanCount,
                'status' => $status,
            ];
        }

        return [
            'tanggal' => $date,
            'rows' => $rows,
            'summary' => [
                'total_kendaraan' => $kendaraanList->count(),
                'belum' => $belum,
                'sudah' => $sudah,
                'ada_temuan' => $adaTemuan,
            ],
        ];
    }

    /**
     * @return array{
     *     periode: array{mulai: string, selesai: string},
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, int|float>
     * }
     */
    public function mingguan(CarbonInterface $mulai, CarbonInterface $selesai): array
    {
        $start = $mulai->toDateString();
        $end = $selesai->toDateString();
        $hari = $mulai->diffInDays($selesai) + 1;

        $kendaraanList = DriverKendaraan::query()
            ->where('status', DriverKendaraan::STATUS_AKTIF)
            ->orderBy('nama')
            ->get();

        $pemeriksaan = DriverPemeriksaan::query()
            ->with('details')
            ->whereBetween('tanggal', [$start, $end])
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->get()
            ->groupBy('driver_kendaraan_id');

        $rows = [];
        $totalTarget = 0;
        $totalHariTerpenuhi = 0;
        $totalAktual = 0;
        $totalKe2 = 0;
        $totalLayakHari = 0;
        $totalTidakLayak = 0;

        foreach ($kendaraanList as $kendaraan) {
            /** @var Collection<int, DriverPemeriksaan> $list */
            $list = $pemeriksaan->get($kendaraan->id, collect());
            $target = $hari;
            $byDate = $list->groupBy(fn (DriverPemeriksaan $p) => $p->tanggal->toDateString());

            $hariTerpenuhi = $byDate->filter(
                fn (Collection $dayList) => $dayList->contains(fn (DriverPemeriksaan $p) => $p->pemeriksaan_ke >= 1)
            )->count();

            $aktual = $list->count();
            $ke2 = $list->where('pemeriksaan_ke', '>', 1)->count();
            $tidakLayak = $list->sum(
                fn (DriverPemeriksaan $p) => $p->details->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK)->count()
            );
            $hariLayak = $byDate->filter(function (Collection $dayList) {
                return $dayList->every(
                    fn (DriverPemeriksaan $p) => $p->details->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK)->isEmpty()
                );
            })->count();

            $kepatuhan = $target > 0 ? round(($hariTerpenuhi / $target) * 100, 1) : 0.0;

            $rows[] = [
                'kendaraan_id' => $kendaraan->id,
                'nama' => $kendaraan->nama,
                'foto_url' => $kendaraan->fotoUrl(),
                'target' => $target,
                'aktual' => $aktual,
                'hari_terpenuhi' => $hariTerpenuhi,
                'layak' => $hariLayak,
                'tidak_layak' => $tidakLayak,
                'pemeriksaan_ke_2' => $ke2,
                'kepatuhan' => $kepatuhan,
            ];

            $totalTarget += $target;
            $totalHariTerpenuhi += $hariTerpenuhi;
            $totalAktual += $aktual;
            $totalKe2 += $ke2;
            $totalLayakHari += $hariLayak;
            $totalTidakLayak += $tidakLayak;
        }

        return [
            'periode' => [
                'mulai' => $start,
                'selesai' => $end,
            ],
            'rows' => $rows,
            'summary' => [
                'target_minimal' => $totalTarget,
                'pemeriksaan_aktual' => $totalAktual,
                'hari_terpenuhi' => $totalHariTerpenuhi,
                'kepatuhan' => $totalTarget > 0 ? round(($totalHariTerpenuhi / $totalTarget) * 100, 1) : 0.0,
                'pemeriksaan_ke_2' => $totalKe2,
                'hari_layak' => $totalLayakHari,
                'temuan_tidak_layak' => $totalTidakLayak,
            ],
        ];
    }
}
