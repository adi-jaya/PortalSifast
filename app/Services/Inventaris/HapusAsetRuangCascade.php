<?php

namespace App\Services\Inventaris;

use App\Models\Aset;
use App\Models\AsetMutasiLokasi;
use App\Models\AsetRuang;
use Illuminate\Support\Facades\DB;

class HapusAsetRuangCascade
{
    /**
     * @param  list<int>  $ids
     * @return array{ruang: int, aset: int, mutasi: int}
     */
    public function handle(array $ids): array
    {
        $stats = [
            'ruang' => 0,
            'aset' => 0,
            'mutasi' => 0,
        ];

        DB::transaction(function () use ($ids, &$stats): void {
            $ruangs = AsetRuang::query()->whereIn('id', $ids)->orderBy('id')->get();

            foreach ($ruangs as $ruang) {
                $stats['mutasi'] += AsetMutasiLokasi::query()
                    ->where(function ($query) use ($ruang) {
                        $query->where('aset_ruang_asal_id', $ruang->id)
                            ->orWhere('aset_ruang_tujuan_id', $ruang->id);
                    })
                    ->delete();

                $stats['aset'] += Aset::query()
                    ->where('aset_ruang_id', $ruang->id)
                    ->delete();

                $ruang->delete();
                $stats['ruang']++;
            }
        });

        return $stats;
    }
}
