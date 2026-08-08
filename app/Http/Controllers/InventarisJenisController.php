<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisJenisRequest;
use App\Http\Requests\UpdateInventarisJenisRequest;
use App\Models\InventarisJenis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisJenisController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');

        try {
            $items = InventarisJenis::query()
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('id_jenis', 'like', $search)
                            ->orWhere('nama_jenis', 'like', $search);
                    });
                })
                ->orderBy('nama_jenis')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
        }

        return Inertia::render('inventaris-jenis/index', [
            'items' => $items,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-jenis/create');
    }

    public function store(StoreInventarisJenisRequest $request): RedirectResponse
    {
        InventarisJenis::query()->create($request->validated());

        return redirect()
            ->route('inventaris-jenis.index')
            ->with('success', 'Jenis berhasil ditambahkan.');
    }

    public function edit(InventarisJenis $jenis): Response
    {
        return Inertia::render('inventaris-jenis/edit', [
            'item' => [
                'id_jenis' => $jenis->id_jenis,
                'nama_jenis' => $jenis->nama_jenis,
            ],
        ]);
    }

    public function update(UpdateInventarisJenisRequest $request, InventarisJenis $jenis): RedirectResponse
    {
        $jenis->update($request->validated());

        return redirect()
            ->route('inventaris-jenis.index')
            ->with('success', 'Jenis berhasil diperbarui.');
    }

    public function destroy(InventarisJenis $jenis): RedirectResponse
    {
        $jenis->delete();

        return redirect()
            ->route('inventaris-jenis.index')
            ->with('success', 'Jenis berhasil dihapus.');
    }
}
