<?php

namespace App\Services\Inventaris;

use App\Models\AsetCounterNomor;
use Illuminate\Support\Facades\DB;

class GeneratorKodeAset
{
    public function generate(string $kodeRuang, int $tahun, ?string $awalan = null): string
    {
        $awalan ??= (string) config('aset.awalan_kode', 'INV');
        $kodeRuang = strtoupper(trim($kodeRuang));

        if ($kodeRuang === '') {
            $kodeRuang = (string) config('aset.kode_ruang_fallback', 'TANPA');
        }

        if ($tahun < 1900 || $tahun > 2100) {
            throw new \InvalidArgumentException('Tahun registrasi tidak valid.');
        }

        return DB::transaction(function () use ($awalan, $kodeRuang, $tahun) {
            $counter = AsetCounterNomor::query()
                ->where('awalan', $awalan)
                ->where('kode_ruang', $kodeRuang)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $counter = AsetCounterNomor::query()->create([
                    'awalan' => $awalan,
                    'kode_ruang' => $kodeRuang,
                    'tahun' => $tahun,
                    'terakhir' => 0,
                ]);

                $counter = AsetCounterNomor::query()
                    ->whereKey($counter->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $counter->terakhir = $counter->terakhir + 1;
            $counter->save();

            return sprintf('%s-%s-%d-%04d', $awalan, $kodeRuang, $tahun, $counter->terakhir);
        });
    }
}
