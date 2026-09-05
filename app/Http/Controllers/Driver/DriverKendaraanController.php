<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverKendaraanRequest;
use App\Http\Requests\Driver\SyncDriverKendaraanItemRequest;
use App\Http\Requests\Driver\UpdateDriverKendaraanRequest;
use App\Models\DriverChecklistItem;
use App\Models\DriverKendaraan;
use App\Models\DriverKendaraanItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DriverKendaraanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canManageDriverMaster(), 403);

        $q = trim((string) $request->query('q', ''));

        $items = DriverKendaraan::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', "%{$q}%")
                        ->orWhere('no_polisi', 'like', "%{$q}%")
                        ->orWhere('merk', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (DriverKendaraan $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'no_polisi' => $item->no_polisi,
                'merk' => $item->merk,
                'model' => $item->model,
                'tahun' => $item->tahun,
                'status' => $item->status,
            ]);

        return Inertia::render('driver/kendaraan/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(StoreDriverKendaraanRequest $request): RedirectResponse
    {
        DriverKendaraan::query()->create($request->validated());

        return redirect()
            ->route('driver.kendaraan.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function update(UpdateDriverKendaraanRequest $request, DriverKendaraan $kendaraan): RedirectResponse
    {
        $kendaraan->update($request->validated());

        return redirect()
            ->route('driver.kendaraan.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function editItems(Request $request, DriverKendaraan $kendaraan): Response
    {
        abort_unless($request->user()?->canManageDriverMaster(), 403);

        $pivots = DriverKendaraanItem::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->get()
            ->keyBy('driver_checklist_item_id');

        $items = DriverChecklistItem::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get()
            ->map(fn (DriverChecklistItem $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori,
                'berlaku' => $pivots->has($item->id)
                    ? (bool) $pivots->get($item->id)->berlaku
                    : true,
            ]);

        return Inertia::render('driver/kendaraan/items', [
            'kendaraan' => [
                'id' => $kendaraan->id,
                'nama' => $kendaraan->nama,
            ],
            'items' => $items,
        ]);
    }

    public function syncItems(SyncDriverKendaraanItemRequest $request, DriverKendaraan $kendaraan): RedirectResponse
    {
        DB::transaction(function () use ($request, $kendaraan) {
            foreach ($request->validated('items') as $row) {
                DriverKendaraanItem::query()->updateOrCreate(
                    [
                        'driver_kendaraan_id' => $kendaraan->id,
                        'driver_checklist_item_id' => $row['driver_checklist_item_id'],
                    ],
                    ['berlaku' => (bool) $row['berlaku']],
                );
            }
        });

        return redirect()
            ->route('driver.kendaraan.items.edit', $kendaraan)
            ->with('success', 'Konfigurasi item berhasil disimpan.');
    }
}
