<?php

namespace App\Services\Patroli;

use App\Models\PatroliCheckin;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuatPatroliCheckin
{
    /**
     * @param  array<int, array{patroli_template_item_id: int, status: string}>  $items
     */
    public function handle(PatroliRuang $ruang, User $actor, array $items, ?string $catatan = null): PatroliCheckin
    {
        $ruang->loadMissing(['patroliTemplate.activeItems', 'area']);

        if (! $ruang->is_active) {
            throw ValidationException::withMessages([
                'patroli_ruang_id' => 'Ruang patroli ini tidak aktif.',
            ]);
        }

        $template = $ruang->patroliTemplate;

        if ($template === null || ! $template->is_active) {
            throw ValidationException::withMessages([
                'patroli_ruang_id' => 'Ruang ini belum memiliki template patroli aktif.',
            ]);
        }

        $activeItems = $template->activeItems;
        if ($activeItems->isEmpty()) {
            throw ValidationException::withMessages([
                'patroli_ruang_id' => 'Template patroli tidak memiliki item aktif.',
            ]);
        }

        $payloadById = collect($items)->keyBy('patroli_template_item_id');
        $missing = $activeItems->filter(fn ($item) => ! $payloadById->has($item->id));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Semua item checklist aktif wajib diisi.',
            ]);
        }

        foreach ($activeItems as $item) {
            $status = $payloadById->get($item->id)['status'] ?? null;
            if (! in_array($status, PatroliCheckinItem::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'items' => "Status tidak valid untuk item {$item->nama}.",
                ]);
            }
        }

        return DB::transaction(function () use ($ruang, $actor, $template, $activeItems, $payloadById, $catatan) {
            $checkin = PatroliCheckin::query()->create([
                'patroli_ruang_id' => $ruang->id,
                'user_id' => $actor->id,
                'patroli_template_id' => $template->id,
                'checked_at' => now(),
                'catatan' => $catatan,
            ]);

            foreach ($activeItems as $item) {
                PatroliCheckinItem::query()->create([
                    'patroli_checkin_id' => $checkin->id,
                    'patroli_template_item_id' => $item->id,
                    'nama_item' => $item->nama,
                    'status' => $payloadById->get($item->id)['status'],
                ]);
            }

            return $checkin->load(['items', 'ruang.area', 'user', 'template']);
        });
    }
}
