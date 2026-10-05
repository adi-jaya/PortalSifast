<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverChecklistItemRequest;
use App\Http\Requests\Driver\UpdateDriverChecklistItemRequest;
use App\Models\DriverChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverChecklistItemController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canManageDriverMaster(), 403);

        $q = trim((string) $request->query('q', ''));

        $items = DriverChecklistItem::query()
            ->when($q !== '', fn ($query) => $query->where('nama', 'like', "%{$q}%"))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (DriverChecklistItem $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori,
                'urutan' => $item->urutan,
                'aktif' => $item->aktif,
            ]);

        return Inertia::render('driver/item-checklist/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(StoreDriverChecklistItemRequest $request): RedirectResponse
    {
        DriverChecklistItem::query()->create($request->validated());

        return redirect()
            ->route('driver.item-checklist.index')
            ->with('success', 'Item checklist berhasil ditambahkan.');
    }

    public function update(UpdateDriverChecklistItemRequest $request, DriverChecklistItem $itemChecklist): RedirectResponse
    {
        $itemChecklist->update($request->validated());

        return redirect()
            ->route('driver.item-checklist.index')
            ->with('success', 'Item checklist berhasil diperbarui.');
    }
}
