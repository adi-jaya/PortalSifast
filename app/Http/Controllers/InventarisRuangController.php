<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisRuangRequest;
use App\Http\Requests\UpdateInventarisRuangRequest;
use App\Models\InventarisRuang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisRuangController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');

        try {
            $items = InventarisRuang::query()
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('id_ruang', 'like', $search)
                            ->orWhere('nama_ruang', 'like', $search);
                    });
                })
                ->orderBy('nama_ruang')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
        }

        return Inertia::render('inventaris-ruang/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-ruang/create');
    }

    public function store(StoreInventarisRuangRequest $request): RedirectResponse
    {
        InventarisRuang::query()->create($request->validated());

        return redirect()
            ->route('inventaris-ruang.index')
            ->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function edit(InventarisRuang $ruang): Response
    {
        return Inertia::render('inventaris-ruang/edit', [
            'item' => [
                'id_ruang' => $ruang->id_ruang,
                'nama_ruang' => $ruang->nama_ruang,
            ],
        ]);
    }

    public function update(UpdateInventarisRuangRequest $request, InventarisRuang $ruang): RedirectResponse
    {
        $ruang->update($request->validated());

        return redirect()
            ->route('inventaris-ruang.index')
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroy(InventarisRuang $ruang): RedirectResponse
    {
        $ruang->delete();

        return redirect()
            ->route('inventaris-ruang.index')
            ->with('success', 'Ruang berhasil dihapus.');
    }
}
