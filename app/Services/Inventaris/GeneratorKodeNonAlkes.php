<?php

namespace App\Services\Inventaris;

use App\Models\AsetNonAlkes;

class GeneratorKodeNonAlkes
{
    public function generate(?AsetNonAlkes $parent, ?int $excludeId = null): string
    {
        if ($parent === null) {
            return $this->nextRootKode($excludeId);
        }

        $parentKode = trim((string) ($parent->kode ?: $parent->alat_code));
        if ($parentKode === '') {
            $parentKode = (string) $parent->id;
        }

        $childLevel = min(255, $parent->level + 1);
        $segmentWidth = $childLevel === 2 ? 2 : 3;
        $prefix = $parentKode.'.';

        $maxSegment = 0;

        AsetNonAlkes::query()
            ->where('parent_id', $parent->id)
            ->where('deleted', false)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->get(['kode', 'alat_code'])
            ->each(function (AsetNonAlkes $sibling) use ($prefix, $childLevel, &$maxSegment): void {
                $kode = trim((string) ($sibling->kode ?: $sibling->alat_code));
                if ($kode === '' || ! str_starts_with($kode, $prefix)) {
                    return;
                }

                $suffix = substr($kode, strlen($prefix));
                $segment = $childLevel === 2
                    ? explode('.', $suffix)[0]
                    : (string) (array_slice(explode('.', $suffix), -1)[0] ?? '');

                if ($segment !== '' && ctype_digit($segment)) {
                    $maxSegment = max($maxSegment, (int) $segment);
                }
            });

        return $prefix.str_pad((string) ($maxSegment + 1), $segmentWidth, '0', STR_PAD_LEFT);
    }

    private function nextRootKode(?int $excludeId): string
    {
        $max = 0;

        AsetNonAlkes::query()
            ->whereNull('parent_id')
            ->where('deleted', false)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->get(['kode', 'alat_code'])
            ->each(function (AsetNonAlkes $root) use (&$max): void {
                $kode = trim((string) ($root->kode ?: $root->alat_code));
                if ($kode !== '' && ctype_digit($kode)) {
                    $max = max($max, (int) $kode);
                }
            });

        return (string) ($max + 1);
    }
}
