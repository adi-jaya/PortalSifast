<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisKategoriRequest;
use App\Http\Requests\UpdateInventarisKategoriRequest;
use App\Models\InventarisKategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisKategoriController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');

        try {
            $items = InventarisKategori::query()
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('id_kategori', 'like', $search)
                            ->orWhere('nama_kategori', 'like', $search);
                    });
                })
                ->orderBy('nama_kategori')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
        }

        return Inertia::render('inventaris-kategori/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-kategori/create');
    }

    public function store(StoreInventarisKategoriRequest $request): RedirectResponse
    {
        InventarisKategori::query()->create($request->validated());

        return redirect()
            ->route('inventaris-kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(InventarisKategori $kategori): Response
    {
        return Inertia::render('inventaris-kategori/edit', [
            'item' => [
                'id_kategori' => $kategori->id_kategori,
                'nama_kategori' => $kategori->nama_kategori,
            ],
        ]);
    }

    public function update(UpdateInventarisKategoriRequest $request, InventarisKategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()
            ->route('inventaris-kategori.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(InventarisKategori $kategori): RedirectResponse
    {
        $kategori->delete();

        return redirect()
            ->route('inventaris-kategori.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
