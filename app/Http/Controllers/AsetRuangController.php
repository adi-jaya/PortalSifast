<?php

namespace App\Http\Controllers;

use App\Models\AsetRuang;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetRuangController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetRuang::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('kode_ruang', 'like', $like)
                        ->orWhere('nama_ruang', 'like', $like);
                });
            })
            ->orderBy('kode_ruang')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetRuang $ruang) => [
                'id' => $ruang->id,
                'kode_ruang' => $ruang->kode_ruang,
                'nama_ruang' => $ruang->nama_ruang,
            ]);

        return Inertia::render('aset/master-ruang/index', [
            'items' => $items,
            'filters' => ['q' => $q],
            'stats' => [
                'total' => AsetRuang::query()->count(),
            ],
        ]);
    }
}
