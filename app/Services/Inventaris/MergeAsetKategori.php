<?php

namespace App\Services\Inventaris;

use App\Models\AsetBarang;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeAsetKategori
{
    /**
     * Reassign all FK references from $source into $target, then delete $source.
     *
     * @return array{barang: int, non_alkes: int}
     */
    public function handle(AsetKategori $target, AsetKategori $source): array
    {
        if ($target->is($source)) {
            throw ValidationException::withMessages([
                'source_id' => 'Kategori sumber dan tujuan tidak boleh sama.',
            ]);
        }

        return DB::transaction(function () use ($target, $source) {
            $barang = AsetBarang::query()
                ->where('aset_kategori_id', $source->id)
                ->update(['aset_kategori_id' => $target->id]);

            $nonAlkes = AsetNonAlkes::query()
                ->where('aset_kategori_id', $source->id)
                ->update(['aset_kategori_id' => $target->id]);

            $source->delete();

            return [
                'barang' => $barang,
                'non_alkes' => $nonAlkes,
            ];
        });
    }
}
