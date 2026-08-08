<?php

namespace App\Services\Inventaris;

use App\Models\AsetMutasiLokasi;
use App\Models\AsetPeminjaman;
use Illuminate\Support\Facades\DB;

class GeneratorNomorDokumenAset
{
    public function peminjaman(): string
    {
        return $this->next('PINJ', AsetPeminjaman::class);
    }

    public function mutasi(): string
    {
        return $this->next('MUT', AsetMutasiLokasi::class);
    }

    /**
     * @param  class-string<AsetPeminjaman|AsetMutasiLokasi>  $model
     */
    private function next(string $prefix, string $model): string
    {
        $date = now()->format('Ymd');
        $like = "{$prefix}-{$date}-%";

        return DB::transaction(function () use ($prefix, $date, $like, $model) {
            $last = $model::query()
                ->where('nomor', 'like', $like)
                ->orderByDesc('nomor')
                ->lockForUpdate()
                ->value('nomor');

            $seq = 1;
            if ($last && preg_match('/-(\d+)$/', $last, $m)) {
                $seq = (int) $m[1] + 1;
            }

            return sprintf('%s-%s-%04d', $prefix, $date, $seq);
        });
    }
}
