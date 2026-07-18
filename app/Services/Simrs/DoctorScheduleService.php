<?php

namespace App\Services\Simrs;

use App\Data\Simrs\DoctorScheduleFilters;
use App\Support\SimrsDayName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DoctorScheduleService
{
    private const LIST_CACHE_MINUTES = 10;

    private const POLIKLINIK_CACHE_MINUTES = 60;

    public function __construct(
        private readonly PegawaiPhotoDataUriService $photoService,
    ) {}

    /**
     * @return list<array{kdPoli: string, namaPoli: string}>
     */
    public function listPoliklinik(): array
    {
        return Cache::remember(
            'simrs.jadwal-dokter.poliklinik',
            now()->addMinutes(self::POLIKLINIK_CACHE_MINUTES),
            fn (): array => DB::connection('dbsimrs')
                ->table('jadwal')
                ->join('poliklinik', 'jadwal.kd_poli', '=', 'poliklinik.kd_poli')
                ->where('poliklinik.nm_poli', '!=', '-')
                ->where('poliklinik.kd_poli', '!=', '-')
                ->select(['poliklinik.kd_poli', 'poliklinik.nm_poli'])
                ->distinct()
                ->orderBy('poliklinik.nm_poli')
                ->get()
                ->map(fn (object $row): array => [
                    'kdPoli' => (string) $row->kd_poli,
                    'namaPoli' => (string) $row->nm_poli,
                ])
                ->values()
                ->all(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(DoctorScheduleFilters $filters): array
    {
        if ($filters->withFoto) {
            return $this->buildList($filters);
        }

        return Cache::remember(
            $this->listCacheKey($filters),
            now()->addMinutes(self::LIST_CACHE_MINUTES),
            fn (): array => $this->buildList($filters),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildList(DoctorScheduleFilters $filters): array
    {
        $rows = $this->query($filters);
        $dataUriCache = $filters->withFoto
            ? $this->photoService->toDataUriMany($rows->pluck('photo_path')->all())
            : [];

        return $rows
            ->map(function (object $row) use ($filters, $dataUriCache): array {
                $photoPath = isset($row->photo_path) ? (string) $row->photo_path : null;
                $photoFields = $this->photoService->resolvePhotoFields(
                    (string) $row->kd_dokter,
                    $photoPath,
                    $filters->withFoto,
                    $dataUriCache,
                );

                return array_merge($this->toPublicArray($row), $photoFields);
            })
            ->values()
            ->all();
    }

    private function listCacheKey(DoctorScheduleFilters $filters): string
    {
        return 'simrs.jadwal-dokter.list.'.md5(json_encode([
            $filters->simrsDay,
            $filters->kdPoli,
            $filters->poli,
            $filters->q,
            $filters->allDays,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return Collection<int, object>
     */
    private function query(DoctorScheduleFilters $filters): Collection
    {
        $query = DB::connection('dbsimrs')
            ->table('jadwal')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->join('poliklinik', 'jadwal.kd_poli', '=', 'poliklinik.kd_poli')
            ->leftJoin('pegawai', 'pegawai.nik', '=', 'dokter.kd_dokter')
            ->select([
                'dokter.kd_dokter',
                'dokter.nm_dokter',
                'poliklinik.kd_poli',
                'poliklinik.nm_poli',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kuota',
                'jadwal.hari_kerja',
                'pegawai.photo as photo_path',
            ]);

        if ($filters->hasDayFilter()) {
            $simrsDay = $filters->simrsDay;
            $query->where(function ($builder) use ($simrsDay): void {
                $builder
                    ->where('jadwal.hari_kerja', $simrsDay)
                    ->orWhere('jadwal.hari_kerja', 'like', '%'.$simrsDay.'%');
            });
        }

        if ($filters->kdPoli !== null && $filters->kdPoli !== '') {
            $query->where('poliklinik.kd_poli', $filters->kdPoli);
        }

        if ($filters->poli !== null && $filters->poli !== '') {
            $query->where('poliklinik.nm_poli', 'like', '%'.$filters->poli.'%');
        }

        if ($filters->q !== null && $filters->q !== '') {
            $query->where('dokter.nm_dokter', 'like', '%'.$filters->q.'%');
        }

        return $query
            ->orderBy('jadwal.hari_kerja')
            ->orderBy('poliklinik.nm_poli')
            ->orderBy('jadwal.jam_mulai')
            ->orderBy('dokter.nm_dokter')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(object $row): array
    {
        $hariKey = strtoupper((string) $row->hari_kerja);

        return [
            'kdDokter' => (string) $row->kd_dokter,
            'namaDokter' => (string) $row->nm_dokter,
            'kdPoli' => (string) $row->kd_poli,
            'namaPoli' => (string) $row->nm_poli,
            'hari' => SimrsDayName::label($hariKey),
            'hariKey' => $hariKey,
            'jamMulai' => $this->formatTime((string) $row->jam_mulai),
            'jamSelesai' => $this->formatTime((string) $row->jam_selesai),
            'kuota' => $row->kuota !== null ? (int) $row->kuota : null,
        ];
    }

    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
