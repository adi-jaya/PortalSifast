<?php

namespace App\Services\Simrs;

use App\Support\SimrsDayName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DoctorProfileService
{
    private const LIST_CACHE_MINUTES = 10;

    public function __construct(
        private readonly PegawaiPhotoDataUriService $photoService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        ?string $q = null,
        ?string $kdPoli = null,
        ?string $poli = null,
        bool $withFoto = false,
    ): array {
        if ($withFoto) {
            return $this->buildList($q, $kdPoli, $poli, true);
        }

        return Cache::remember(
            $this->listCacheKey($q, $kdPoli, $poli),
            now()->addMinutes(self::LIST_CACHE_MINUTES),
            fn (): array => $this->buildList($q, $kdPoli, $poli, false),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByPoli(string $kdPoli, bool $withFoto = false): array
    {
        $rows = $this->baseQuery(kdPoli: $kdPoli)->get();

        return $this->groupProfiles($rows, $withFoto, includeJadwal: true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $kdDokter, bool $withFoto = false): ?array
    {
        $rows = $this->baseQuery()
            ->where('dokter.kd_dokter', $kdDokter)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $profiles = $this->groupProfiles($rows, $withFoto, includeJadwal: true);

        return $profiles[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildList(?string $q, ?string $kdPoli, ?string $poli, bool $withFoto): array
    {
        $rows = $this->baseQuery($q, $kdPoli, $poli)->get();

        return $this->groupProfiles($rows, $withFoto, includeJadwal: false);
    }

    private function listCacheKey(?string $q, ?string $kdPoli, ?string $poli): string
    {
        return 'simrs.dokter.list.'.md5(json_encode([$q, $kdPoli, $poli], JSON_THROW_ON_ERROR));
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function baseQuery(?string $q = null, ?string $kdPoli = null, ?string $poli = null)
    {
        $query = DB::connection('dbsimrs')
            ->table('jadwal')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->join('poliklinik', 'jadwal.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('pegawai', 'pegawai.nik', '=', 'dokter.kd_dokter')
            ->where('pegawai.stts_aktif', 'AKTIF')
            ->where('dokter.kd_dokter', '!=', '-')
            ->where('poliklinik.kd_poli', '!=', '-')
            ->select([
                'dokter.kd_dokter',
                'dokter.nm_dokter',
                'pegawai.stts_aktif',
                'pegawai.photo as photo_path',
                'poliklinik.kd_poli',
                'poliklinik.nm_poli',
                'jadwal.hari_kerja',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kuota',
            ])
            ->orderBy('dokter.nm_dokter')
            ->orderByRaw("FIELD(jadwal.hari_kerja, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'MINGGU')")
            ->orderBy('poliklinik.nm_poli')
            ->orderBy('jadwal.jam_mulai');

        if ($kdPoli !== null && $kdPoli !== '') {
            $query->where('poliklinik.kd_poli', $kdPoli);
        }

        if ($poli !== null && $poli !== '') {
            $query->where('poliklinik.nm_poli', 'like', '%'.$poli.'%');
        }

        if ($q !== null && $q !== '') {
            $query->where('dokter.nm_dokter', 'like', '%'.$q.'%');
        }

        return $query;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function groupProfiles(Collection $rows, bool $withFoto, bool $includeJadwal): array
    {
        $grouped = $rows->groupBy('kd_dokter');
        $dataUriCache = $withFoto
            ? $this->photoService->toDataUriMany($rows->pluck('photo_path')->all())
            : [];

        return $grouped
            ->map(function (Collection $doctorRows) use ($withFoto, $includeJadwal, $dataUriCache): array {
                $first = $doctorRows->first();
                $photoPath = isset($first->photo_path) ? (string) $first->photo_path : null;
                $photoFields = $this->photoService->resolvePhotoFields(
                    (string) $first->kd_dokter,
                    $photoPath,
                    $withFoto,
                    $dataUriCache,
                );

                $poli = $doctorRows
                    ->unique('kd_poli')
                    ->map(fn (object $row): array => [
                        'kdPoli' => (string) $row->kd_poli,
                        'namaPoli' => (string) $row->nm_poli,
                    ])
                    ->values()
                    ->all();

                $jadwal = $doctorRows
                    ->map(fn (object $row): array => $this->jadwalEntry($row))
                    ->values()
                    ->all();

                $profile = array_merge([
                    'kdDokter' => (string) $first->kd_dokter,
                    'namaDokter' => (string) $first->nm_dokter,
                    'sttsAktif' => (string) $first->stts_aktif,
                    'isAktif' => strtoupper((string) $first->stts_aktif) === 'AKTIF',
                    'poli' => $poli,
                ], $photoFields);

                if ($includeJadwal) {
                    $profile['jadwal'] = $jadwal;
                } else {
                    $profile['jumlahJadwal'] = count($jadwal);
                }

                return $profile;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function jadwalEntry(object $row): array
    {
        $hariKey = strtoupper((string) $row->hari_kerja);

        return [
            'hari' => SimrsDayName::label($hariKey),
            'hariKey' => $hariKey,
            'kdPoli' => (string) $row->kd_poli,
            'namaPoli' => (string) $row->nm_poli,
            'jamMulai' => substr((string) $row->jam_mulai, 0, 5),
            'jamSelesai' => substr((string) $row->jam_selesai, 0, 5),
            'kuota' => $row->kuota !== null ? (int) $row->kuota : null,
        ];
    }
}
