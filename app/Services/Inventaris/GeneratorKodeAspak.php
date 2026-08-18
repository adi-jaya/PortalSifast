<?php

namespace App\Services\Inventaris;

use App\Models\AsetAspakAlat;

class GeneratorKodeAspak
{
    public function generate(?AsetAspakAlat $parent, ?int $excludeId = null): string
    {
        if ($parent === null) {
            return $this->nextRootKode($excludeId);
        }

        $parentKode = trim((string) ($parent->kode ?: $parent->alat_code));
        if ($parentKode === '') {
            $parentKode = (string) $parent->id;
        }

        $segmentWidth = 3;
        $maxSegment = 0;

        AsetAspakAlat::query()
            ->where('parent_id', $parent->id)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->get(['kode', 'alat_code'])
            ->each(function (AsetAspakAlat $sibling) use ($parentKode, &$maxSegment, &$segmentWidth): void {
                $kode = trim((string) ($sibling->kode ?: $sibling->alat_code));
                if ($kode === '' || ! str_starts_with($kode, $parentKode)) {
                    return;
                }

                $suffix = substr($kode, strlen($parentKode));
                if ($suffix === '' || ! ctype_digit($suffix)) {
                    return;
                }

                $segmentWidth = max($segmentWidth, strlen($suffix));
                $maxSegment = max($maxSegment, (int) $suffix);
            });

        return $parentKode.str_pad((string) ($maxSegment + 1), $segmentWidth, '0', STR_PAD_LEFT);
    }

    private function nextRootKode(?int $excludeId): string
    {
        $max = 0;

        AsetAspakAlat::query()
            ->whereNull('parent_id')
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->get(['kode', 'alat_code'])
            ->each(function (AsetAspakAlat $root) use (&$max): void {
                $kode = trim((string) ($root->kode ?: $root->alat_code));
                if ($kode !== '' && ctype_digit($kode)) {
                    $max = max($max, (int) $kode);
                }
            });

        if ($max === 0) {
            return '10001';
        }

        return (string) ($max >= 10000 ? $max + 100 : $max + 1);
    }
}
