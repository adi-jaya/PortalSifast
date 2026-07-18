<?php

namespace App\Http\Controllers;

use App\Models\AsetNonAlkes;
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
            ->with('parent:id,nama_alat,kode')
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
        ]);
    }
}
