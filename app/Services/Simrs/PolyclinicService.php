<?php

namespace App\Services\Simrs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PolyclinicService
{
    private const CACHE_MINUTES = 60;

    /**
     * @return list<array{kdPoli: string, namaPoli: string}>
     */
    public function listAllActive(): array
    {
        $cacheKey = 'simrs.polyclinic.active';

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (is_array($cached)) {
            Cache::forget($cacheKey);
        }

        try {
            $result = $this->fetchAllActiveFromDatabase();
        } catch (\Throwable) {
            return [];
        }

        if ($result !== []) {
            Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_MINUTES));
        }

        return $result;
    }

    /**
     * @return list<array{kdPoli: string, namaPoli: string}>
     */
    private function fetchAllActiveFromDatabase(): array
    {
        $query = DB::connection('dbsimrs')
            ->table('poliklinik')
            ->where('nm_poli', '!=', '-')
            ->where('kd_poli', '!=', '-')
            ->select(['kd_poli', 'nm_poli'])
            ->orderBy('nm_poli');

        if ($this->hasStatusColumn()) {
            $query->whereIn('status', ['1', 'AKTIF', 'aktif']);
        }

        return $query->get()
            ->map(fn (object $row): array => [
                'kdPoli' => (string) $row->kd_poli,
                'namaPoli' => (string) $row->nm_poli,
            ])
            ->values()
            ->all();
    }

    public function findName(string $kdPoli): ?string
    {
        $match = collect($this->listAllActive())->firstWhere('kdPoli', $kdPoli);

        if (is_array($match) && array_key_exists('namaPoli', $match)) {
            return (string) $match['namaPoli'];
        }

        try {
            $row = DB::connection('dbsimrs')
                ->table('poliklinik')
                ->where('kd_poli', $kdPoli)
                ->select('nm_poli')
                ->first();

            return $row !== null ? (string) $row->nm_poli : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasStatusColumn(): bool
    {
        try {
            return Schema::connection('dbsimrs')->hasColumn('poliklinik', 'status');
        } catch (\Throwable) {
            return false;
        }
    }
}
