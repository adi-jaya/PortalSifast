<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisMerkRequest;
use App\Http\Requests\UpdateInventarisMerkRequest;
use App\Models\InventarisMerk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisMerkController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');

        try {
            $items = InventarisMerk::query()
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('id_merk', 'like', $search)
                            ->orWhere('nama_merk', 'like', $search);
                    });
                })
                ->orderBy('nama_merk')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
        }

        return Inertia::render('inventaris-merk/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-merk/create');
    }

    public function store(StoreInventarisMerkRequest $request): RedirectResponse
    {
        InventarisMerk::query()->create($request->validated());

        return redirect()
            ->route('inventaris-merk.index')
            ->with('success', 'Merk berhasil ditambahkan.');
    }

    public function edit(InventarisMerk $merk): Response
    {
        return Inertia::render('inventaris-merk/edit', [
            'item' => [
                'id_merk' => $merk->id_merk,
                'nama_merk' => $merk->nama_merk,
            ],
        ]);
    }

    public function update(UpdateInventarisMerkRequest $request, InventarisMerk $merk): RedirectResponse
    {
        $merk->update($request->validated());

        return redirect()
            ->route('inventaris-merk.index')
            ->with('success', 'Merk berhasil diperbarui.');
    }

    public function destroy(InventarisMerk $merk): RedirectResponse
    {
        $merk->delete();

        return redirect()
            ->route('inventaris-merk.index')
            ->with('success', 'Merk berhasil dihapus.');
    }
}
