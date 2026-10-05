<?php

namespace App\Services\Inventaris;

use App\Models\AsetDistributor;
use Illuminate\Support\Facades\DB;

class HapusAsetDistributor
{
    /**
     * @param  list<int>  $ids
     * @return array{distributor: int, skipped: int}
     */
    public function handle(array $ids): array
    {
        $stats = [
            'distributor' => 0,
            'skipped' => 0,
        ];

        DB::transaction(function () use ($ids, &$stats): void {
            $distributors = AsetDistributor::query()
                ->withCount('aset')
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get();

            foreach ($distributors as $distributor) {
                if ($distributor->aset_count > 0) {
                    $stats['skipped']++;

                    continue;
                }

                $distributor->delete();
                $stats['distributor']++;
            }
        });

        return $stats;
    }
}
