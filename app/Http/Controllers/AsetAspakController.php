<?php

namespace App\Http\Controllers;

use App\Models\AsetAspakAlat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetAspakController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $onlyLeaf = $request->boolean('only_leaf');

        $items = AsetAspakAlat::query()
            ->with('parent:id,nama_alat,kode')
            ->withCount('children as children_count')
            ->when($onlyLeaf, fn ($query) => $query->leaf())
            ->when($q !== '', fn ($query) => $query->search($q))
            ->orderBy('kode')
            ->orderBy('nama_alat')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetAspakAlat $item) => [
                'id' => $item->id,
                'id_alat_aspak' => $item->id_alat_aspak,
                'nama_alat' => $item->nama_alat,
                'kode' => $item->kode ?: $item->alat_code,
                'sinonim' => $item->sinonim,
                'parent_nama' => $item->parent?->nama_alat,
                'wajib_kalibrasi' => (bool) $item->wajib_kalibrasi,
                'is_leaf' => (int) $item->children_count === 0,
            ]);

        return Inertia::render('aset/master-aspak/index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'only_leaf' => $onlyLeaf,
            ],
            'stats' => [
                'total' => AsetAspakAlat::query()->count(),
                'leaf' => AsetAspakAlat::query()->leaf()->count(),
            ],
        ]);
    }
}
