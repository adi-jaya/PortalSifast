<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAsetNonAlkesKategoriRequest;
use App\Http\Requests\UpdateAsetNonAlkesNamaRequest;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetNonAlkesController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $onlyLeaf = $request->boolean('only_leaf');
        $level = $request->query('level');

        $items = AsetNonAlkes::query()
            ->with([
                'kategori:id,nama_kategori',
                'parent:id,nama_alat,kode,parent_id,aset_kategori_id',
                'parent.kategori:id,nama_kategori',
                'parent.parent:id,nama_alat,kode,parent_id,aset_kategori_id',
                'parent.parent.kategori:id,nama_kategori',
                'parent.parent.parent:id,nama_alat,kode,parent_id,aset_kategori_id',
                'parent.parent.parent.kategori:id,nama_kategori',
            ])
            ->withCount(['children as children_count' => fn ($query) => $query->where('deleted', false)])
            ->where('deleted', false)
            ->when($onlyLeaf, fn ($query) => $query->leaf())
            ->when($q !== '', fn ($query) => $query->search($q))
            ->when(
                filled($level) && is_numeric($level),
                fn ($query) => $query->where('level', (int) $level),
            )
            ->orderBy('kode')
            ->orderBy('nama_alat')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetNonAlkes $item) => [
                'id' => $item->id,
                'id_alat' => $item->id_alat,
                'nama_alat' => $item->nama_alat,
                'kode' => $item->kode ?: $item->alat_code,
                'level' => $item->level,
                'sinonim' => $item->sinonim,
                'parent_nama' => $item->parent?->nama_alat,
                'is_leaf' => (int) $item->children_count === 0,
                'aset_kategori_id' => $item->aset_kategori_id,
                'kategori_efektif_id' => $item->resolvedKategoriId(),
                'kategori_efektif_nama' => $item->resolvedKategoriNama(),
            ]);

        return Inertia::render('aset/master-non-alkes/index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'only_leaf' => $onlyLeaf,
                'level' => filled($level) ? (string) $level : '',
            ],
            'stats' => [
                'total' => AsetNonAlkes::query()->where('deleted', false)->count(),
                'leaf' => AsetNonAlkes::query()->leaf()->count(),
            ],
            'kategoriOptions' => AsetKategori::query()
                ->orderBy('nama_kategori')
                ->get(['id', 'nama_kategori'])
                ->map(fn (AsetKategori $k) => [
                    'id' => $k->id,
                    'nama' => $k->nama_kategori,
                ]),
        ]);
    }

    public function updateKategori(UpdateAsetNonAlkesKategoriRequest $request, AsetNonAlkes $nonAlkes): RedirectResponse
    {
        $nonAlkes->update([
            'aset_kategori_id' => $request->validated('aset_kategori_id'),
        ]);

        return back()->with('success', 'Kategori katalog diperbarui.');
    }

    public function updateNama(UpdateAsetNonAlkesNamaRequest $request, AsetNonAlkes $nonAlkes): RedirectResponse
    {
        $nonAlkes->update([
            'nama_alat' => $request->validated('nama_alat'),
        ]);

        return back()->with('success', 'Nama katalog diperbarui.');
    }
}
