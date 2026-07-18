<?php

namespace App\Services\Inventaris;

use App\Models\AsetPengaturan;
use Illuminate\Support\Facades\Cache;

class PengaturanPenyusutanAset
{
    private const CACHE_KEY = 'aset.pengaturan.penyusutan';

    /**
     * @return array{
     *     metode: string,
     *     residu_persen_default: float,
     *     umur_bulan_medis: int,
     *     umur_bulan_non_medis: int,
     *     umur_bulan_default: int
     * }
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            $defaults = $this->defaultsFromConfig();
            $rows = AsetPengaturan::query()
                ->whereIn('kunci', array_keys($defaults))
                ->pluck('nilai', 'kunci');

            return [
                'metode' => (string) ($rows['metode'] ?? $defaults['metode']),
                'residu_persen_default' => (float) ($rows['residu_persen_default'] ?? $defaults['residu_persen_default']),
                'umur_bulan_medis' => (int) ($rows['umur_bulan_medis'] ?? $defaults['umur_bulan_medis']),
                'umur_bulan_non_medis' => (int) ($rows['umur_bulan_non_medis'] ?? $defaults['umur_bulan_non_medis']),
                'umur_bulan_default' => (int) ($rows['umur_bulan_default'] ?? $defaults['umur_bulan_default']),
            ];
        });
    }

    /**
     * @param  array{
     *     residu_persen_default?: float|int|string,
     *     umur_bulan_medis?: int|string,
     *     umur_bulan_non_medis?: int|string,
     *     umur_bulan_default?: int|string
     * }  $data
     */
    public function update(array $data): array
    {
        $payload = [
            'metode' => 'garis_lurus',
            'residu_persen_default' => (string) ($data['residu_persen_default'] ?? 1),
            'umur_bulan_medis' => (string) ($data['umur_bulan_medis'] ?? 60),
            'umur_bulan_non_medis' => (string) ($data['umur_bulan_non_medis'] ?? 48),
            'umur_bulan_default' => (string) ($data['umur_bulan_default'] ?? 60),
        ];

        foreach ($payload as $kunci => $nilai) {
            AsetPengaturan::query()->updateOrCreate(
                ['kunci' => $kunci],
                ['nilai' => $nilai],
            );
        }

        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }

    public function umurDefaultUntuk(?string $kelasAset): int
    {
        $all = $this->all();

        return match ($kelasAset) {
            'medis' => $all['umur_bulan_medis'],
            'non_medis' => $all['umur_bulan_non_medis'],
            default => $all['umur_bulan_default'],
        };
    }

    public function residuDefaultDariHarga(float|string|null $harga): ?float
    {
        $perolehan = is_numeric($harga) ? (float) $harga : null;
        if ($perolehan === null || $perolehan <= 0) {
            return null;
        }

        $persen = $this->all()['residu_persen_default'];

        return round($perolehan * ($persen / 100), 2);
    }

    /**
     * Resolve umur & residu: nilai eksplisit aset menang, kosong pakai default setting.
     *
     * @return array{
     *     umur_bulan: int|null,
     *     nilai_residu: float|null,
     *     memakai_default_umur: bool,
     *     memakai_default_residu: bool
     * }
     */
    public function resolveUntukAset(
        float|string|null $harga,
        ?int $umurEksplisit,
        float|string|null $residuEksplisit,
        ?string $kelasAset,
    ): array {
        $memakaiDefaultUmur = $umurEksplisit === null || $umurEksplisit <= 0;
        $umur = $memakaiDefaultUmur ? $this->umurDefaultUntuk($kelasAset) : $umurEksplisit;

        $residuAngka = is_numeric($residuEksplisit) ? (float) $residuEksplisit : null;
        $memakaiDefaultResidu = $residuAngka === null;
        $residu = $memakaiDefaultResidu
            ? $this->residuDefaultDariHarga($harga)
            : $residuAngka;

        return [
            'umur_bulan' => $umur,
            'nilai_residu' => $residu,
            'memakai_default_umur' => $memakaiDefaultUmur,
            'memakai_default_residu' => $memakaiDefaultResidu,
        ];
    }

    /**
     * @return array{
     *     metode: string,
     *     residu_persen_default: float,
     *     umur_bulan_medis: int,
     *     umur_bulan_non_medis: int,
     *     umur_bulan_default: int
     * }
     */
    private function defaultsFromConfig(): array
    {
        return [
            'metode' => (string) config('aset.penyusutan.metode', 'garis_lurus'),
            'residu_persen_default' => (float) config('aset.penyusutan.residu_persen_default', 1),
            'umur_bulan_medis' => (int) config('aset.penyusutan.umur_bulan_medis', 60),
            'umur_bulan_non_medis' => (int) config('aset.penyusutan.umur_bulan_non_medis', 48),
            'umur_bulan_default' => (int) config('aset.penyusutan.umur_bulan_default', 60),
        ];
    }
}
