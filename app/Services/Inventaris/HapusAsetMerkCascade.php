<?php

namespace App\Services\Inventaris;

use App\Models\AsetJenis;
use App\Models\AsetMerk;
use Illuminate\Support\Facades\DB;

class HapusAsetMerkCascade
{
    /**
     * @param  list<int>  $ids
     * @return array{merk: int, jenis: int, skipped: int}
     */
    public function handle(array $ids): array
    {
        $stats = [
            'merk' => 0,
            'jenis' => 0,
            'skipped' => 0,
        ];

        DB::transaction(function () use ($ids, &$stats): void {
            $merks = AsetMerk::query()
                ->withCount('barang')
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get();

            foreach ($merks as $merk) {
                if ($merk->barang_count > 0) {
                    $stats['skipped']++;

                    continue;
                }

                $stats['jenis'] += AsetJenis::query()
                    ->where('aset_merk_id', $merk->id)
                    ->update(['aset_merk_id' => null]);

                $merk->delete();
                $stats['merk']++;
            }
        });

        return $stats;
    }
}
