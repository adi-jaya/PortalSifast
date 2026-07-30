<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patroli\StorePatroliCheckinRequest;
use App\Models\PatroliCheckin;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Services\Patroli\BuatPatroliCheckin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatroliCheckinController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $temuan = (string) $request->query('temuan', 'all');

        $rows = PatroliCheckin::query()
            ->with(['ruang.area', 'user', 'template', 'items'])
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('ruang', function ($ruang) use ($search) {
                        $ruang->where('kode', 'like', $search)
                            ->orWhere('nama', 'like', $search)
                            ->orWhereHas('area', fn ($area) => $area->where('nama', 'like', $search));
                    })->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search));
                });
            })
            ->when($temuan === 'ya', function ($query) {
                $query->whereHas('items', fn ($item) => $item->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI));
            })
            ->orderByDesc('checked_at')
            ->paginate(20)
            ->withQueryString();

        $rows->setCollection(
            $rows->getCollection()->map(function (PatroliCheckin $row) {
                return [
                    'id' => $row->id,
                    'checked_at' => $row->checked_at?->toDateTimeString(),
                    'kode' => $row->ruang?->kode,
                    'nama_ruang' => $row->ruang?->nama,
                    'nama_area' => $row->ruang?->area?->nama,
                    'petugas' => $row->user?->name,
                    'template' => $row->template?->nama,
                    'jumlah_item' => $row->items->count(),
                    'jumlah_tidak_berfungsi' => $row->items->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI)->count(),
                ];
            })
        );

        return Inertia::render('patroli/checkin/index', [
            'rows' => $rows,
            'filters' => [
                'q' => $q,
                'temuan' => $temuan,
            ],
        ]);
    }

    public function scan(PatroliRuang $ruang): Response|RedirectResponse
    {
        $ruang->load(['patroliTemplate.activeItems', 'area']);

        if (! $ruang->is_active) {
            return redirect()
                ->route('patroli.checkin.index')
                ->with('error', 'Ruang patroli ini tidak aktif.');
        }

        if ($ruang->patroliTemplate === null || ! $ruang->patroliTemplate->is_active) {
            return redirect()
                ->route('patroli.checkin.index')
                ->with('error', 'Ruang ini belum memiliki template patroli aktif.');
        }

        $items = $ruang->patroliTemplate->activeItems;
        if ($items->isEmpty()) {
            return redirect()
                ->route('patroli.checkin.index')
                ->with('error', 'Template patroli tidak memiliki item aktif.');
        }

        return Inertia::render('patroli/checkin/scan', [
            'ruang' => [
                'id' => $ruang->id,
                'kode' => $ruang->kode,
                'nama' => $ruang->nama,
                'nama_area' => $ruang->area?->nama,
            ],
            'template' => [
                'id' => $ruang->patroliTemplate->id,
                'nama' => $ruang->patroliTemplate->nama,
            ],
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'urutan' => $item->urutan,
            ])->values(),
            'statuses' => PatroliCheckinItem::STATUSES,
        ]);
    }

    public function store(StorePatroliCheckinRequest $request, BuatPatroliCheckin $service): RedirectResponse
    {
        $validated = $request->validated();
        $ruang = PatroliRuang::query()->findOrFail($validated['patroli_ruang_id']);

        $checkin = $service->handle(
            ruang: $ruang,
            actor: $request->user(),
            items: $validated['items'],
            catatan: $validated['catatan'] ?? null,
        );

        return redirect()
            ->route('patroli.checkin.show', $checkin)
            ->with('success', 'Check-in patroli tersimpan.');
    }

    public function show(PatroliCheckin $checkin): Response
    {
        $checkin->load(['ruang.area', 'user', 'template', 'items']);

        return Inertia::render('patroli/checkin/show', [
            'checkin' => [
                'id' => $checkin->id,
                'checked_at' => $checkin->checked_at?->toDateTimeString(),
                'catatan' => $checkin->catatan,
                'kode' => $checkin->ruang?->kode,
                'nama_ruang' => $checkin->ruang?->nama,
                'nama_area' => $checkin->ruang?->area?->nama,
                'petugas' => $checkin->user?->name,
                'template' => $checkin->template?->nama,
                'items' => $checkin->items->map(fn (PatroliCheckinItem $item) => [
                    'id' => $item->id,
                    'nama_item' => $item->nama_item,
                    'status' => $item->status,
                ])->values(),
            ],
        ]);
    }
}
