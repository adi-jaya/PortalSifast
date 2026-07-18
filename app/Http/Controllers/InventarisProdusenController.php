<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisProdusenRequest;
use App\Http\Requests\UpdateInventarisProdusenRequest;
use App\Models\InventarisProdusen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisProdusenController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');

        try {
            $items = InventarisProdusen::query()
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('kode_produsen', 'like', $search)
                            ->orWhere('nama_produsen', 'like', $search);
                    });
                })
                ->orderBy('nama_produsen')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
        }

        return Inertia::render('inventaris-produsen/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-produsen/create');
    }

    public function store(StoreInventarisProdusenRequest $request): RedirectResponse
    {
        InventarisProdusen::query()->create($request->validated());

        return redirect()
            ->route('inventaris-produsen.index')
            ->with('success', 'Produsen berhasil ditambahkan.');
    }

    public function edit(InventarisProdusen $produsen): Response
    {
        return Inertia::render('inventaris-produsen/edit', [
            'item' => [
                'kode_produsen' => $produsen->kode_produsen,
                'nama_produsen' => $produsen->nama_produsen,
                'alamat_produsen' => $produsen->alamat_produsen,
                'no_telp' => $produsen->no_telp,
                'email' => $produsen->email,
                'website_produsen' => $produsen->website_produsen,
            ],
        ]);
    }

    public function update(UpdateInventarisProdusenRequest $request, InventarisProdusen $produsen): RedirectResponse
    {
        $produsen->update($request->validated());

        return redirect()
            ->route('inventaris-produsen.index')
            ->with('success', 'Produsen berhasil diperbarui.');
    }

    public function destroy(InventarisProdusen $produsen): RedirectResponse
    {
        $produsen->delete();

        return redirect()
            ->route('inventaris-produsen.index')
            ->with('success', 'Produsen berhasil dihapus.');
    }
}
