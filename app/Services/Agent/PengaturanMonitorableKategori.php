<?php

namespace App\Services\Agent;

use App\Models\AsetPengaturan;
use Illuminate\Support\Facades\Cache;

class PengaturanMonitorableKategori
{
    public const SETTING_KEY = 'monitoring.monitorable_kategori_codes';

    private const CACHE_KEY = 'agent.pengaturan.monitorable_kategori_codes';

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): array {
            $stored = AsetPengaturan::query()
                ->where('kunci', self::SETTING_KEY)
                ->value('nilai');

            if (is_string($stored) && $stored !== '') {
                $decoded = json_decode($stored, true);
                if (is_array($decoded)) {
                    return $this->normalizeCodes($decoded);
                }
            }

            return $this->defaultsFromConfig();
        });
    }

    /**
     * @param  list<string>|array<int, mixed>  $codes
     * @return list<string>
     */
    public function update(array $codes): array
    {
        $normalized = $this->normalizeCodes($codes);

        AsetPengaturan::query()->updateOrCreate(
            ['kunci' => self::SETTING_KEY],
            ['nilai' => json_encode($normalized, JSON_THROW_ON_ERROR)],
        );

        Cache::forget(self::CACHE_KEY);

        return $this->codes();
    }

    /**
     * @return list<string>
     */
    public function defaultsFromConfig(): array
    {
        /** @var list<string>|array<int, mixed> $codes */
        $codes = config('agent.monitorable_kategori_codes', []);

        return $this->normalizeCodes(is_array($codes) ? $codes : []);
    }

    /**
     * @param  array<int, mixed>  $codes
     * @return list<string>
     */
    private function normalizeCodes(array $codes): array
    {
        $normalized = [];

        foreach ($codes as $code) {
            if (! is_string($code) && ! is_numeric($code)) {
                continue;
            }

            $value = strtoupper(trim((string) $code));
            if ($value === '') {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }
}
