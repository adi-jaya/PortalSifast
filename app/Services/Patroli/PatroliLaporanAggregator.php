<?php

namespace App\Services\Patroli;

use App\Models\PatroliCheckin;
use App\Models\PatroliCheckinItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PatroliLaporanAggregator
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     total_checkin: int,
     *     per_item: list<array{nama_item: string, berfungsi: int, tidak_berfungsi: int, tidak_dicek: int, persen_berfungsi: float|null}>,
     *     per_area: list<array{patroli_area_id: int, nama_area: string|null, total_checkin: int, tidak_berfungsi: int}>,
     *     per_ruang: list<array{patroli_ruang_id: int, kode: string|null, nama_ruang: string|null, nama_area: string|null, total_checkin: int, tidak_berfungsi: int}>,
     *     per_template: list<array{patroli_template_id: int, nama: string|null, total_checkin: int}>,
     *     temuan: list<array{checkin_id: int, checked_at: string, kode: string|null, nama_ruang: string|null, nama_area: string|null, nama_item: string, petugas: string|null}>
     * }
     */
    public function aggregate(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $checkins = PatroliCheckin::query()
            ->with(['items', 'ruang.area', 'user', 'template'])
            ->whereBetween('checked_at', [$from, $to])
            ->orderBy('checked_at')
            ->get();

        $itemStats = [];
        foreach ($checkins as $checkin) {
            foreach ($checkin->items as $item) {
                $key = $item->nama_item;
                if (! isset($itemStats[$key])) {
                    $itemStats[$key] = [
                        'nama_item' => $key,
                        'berfungsi' => 0,
                        'tidak_berfungsi' => 0,
                        'tidak_dicek' => 0,
                    ];
                }
                match ($item->status) {
                    PatroliCheckinItem::STATUS_BERFUNGSI => $itemStats[$key]['berfungsi']++,
                    PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI => $itemStats[$key]['tidak_berfungsi']++,
                    default => $itemStats[$key]['tidak_dicek']++,
                };
            }
        }

        $perItem = collect($itemStats)
            ->map(function (array $row) {
                $den = $row['berfungsi'] + $row['tidak_berfungsi'];
                $row['persen_berfungsi'] = $den > 0
                    ? round(($row['berfungsi'] / $den) * 100, 1)
                    : null;

                return $row;
            })
            ->sortBy('nama_item')
            ->values()
            ->all();

        $perArea = $checkins
            ->groupBy(fn (PatroliCheckin $c) => $c->ruang?->patroli_area_id)
            ->filter(fn ($rows, $areaId) => $areaId !== null)
            ->map(function (Collection $rows, $areaId) {
                $first = $rows->first();

                return [
                    'patroli_area_id' => (int) $areaId,
                    'nama_area' => $first?->ruang?->area?->nama,
                    'total_checkin' => $rows->count(),
                    'tidak_berfungsi' => $rows->sum(
                        fn (PatroliCheckin $c) => $c->items->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI)->count()
                    ),
                ];
            })
            ->sortBy('nama_area')
            ->values()
            ->all();

        $perRuang = $checkins
            ->groupBy('patroli_ruang_id')
            ->map(function (Collection $rows, $ruangId) {
                $first = $rows->first();

                return [
                    'patroli_ruang_id' => (int) $ruangId,
                    'kode' => $first?->ruang?->kode,
                    'nama_ruang' => $first?->ruang?->nama,
                    'nama_area' => $first?->ruang?->area?->nama,
                    'total_checkin' => $rows->count(),
                    'tidak_berfungsi' => $rows->sum(
                        fn (PatroliCheckin $c) => $c->items->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI)->count()
                    ),
                ];
            })
            ->sortBy('nama_ruang')
            ->values()
            ->all();

        $perTemplate = $checkins
            ->groupBy('patroli_template_id')
            ->map(function (Collection $rows, $templateId) {
                $first = $rows->first();

                return [
                    'patroli_template_id' => (int) $templateId,
                    'nama' => $first?->template?->nama,
                    'total_checkin' => $rows->count(),
                ];
            })
            ->sortBy('nama')
            ->values()
            ->all();

        $temuan = [];
        foreach ($checkins as $checkin) {
            foreach ($checkin->items->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI) as $item) {
                $temuan[] = [
                    'checkin_id' => $checkin->id,
                    'checked_at' => $checkin->checked_at?->toDateTimeString(),
                    'kode' => $checkin->ruang?->kode,
                    'nama_ruang' => $checkin->ruang?->nama,
                    'nama_area' => $checkin->ruang?->area?->nama,
                    'nama_item' => $item->nama_item,
                    'petugas' => $checkin->user?->name,
                ];
            }
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_checkin' => $checkins->count(),
            'per_item' => $perItem,
            'per_area' => $perArea,
            'per_ruang' => $perRuang,
            'per_template' => $perTemplate,
            'temuan' => $temuan,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriod(string $periode, ?string $from = null, ?string $to = null): array
    {
        $now = now();

        return match ($periode) {
            'mingguan' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'bulanan' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            '3bulanan' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'tahunan' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($from ?? $now->toDateString())->startOfDay(),
                Carbon::parse($to ?? $now->toDateString())->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
