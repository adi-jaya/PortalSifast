<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeAsetKategoriRequest;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use App\Services\Inventaris\MergeAsetKategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetKategoriController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetKategori::query()
            ->withCount(['barang'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama_kategori', 'like', "%{$q}%")
                        ->orWhere('kode_kategori', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_kategori')
            ->paginate(40)
            ->withQueryString()
            ->through(function (AsetKategori $item) {
                $nonAlkesCount = AsetNonAlkes::query()
                    ->where('aset_kategori_id', $item->id)
                    ->count();

                return [
                    'id' => $item->id,
                    'kode_kategori' => $item->kode_kategori,
                    'nama_kategori' => $item->nama_kategori,
                    'barang_count' => (int) $item->barang_count,
                    'non_alkes_count' => $nonAlkesCount,
                    'usage_count' => (int) $item->barang_count + $nonAlkesCount,
                ];
            });

        return Inertia::render('aset/master-kategori/index', [
            'items' => $items,
            'filters' => ['q' => $q],
            'kategoriOptions' => AsetKategori::query()
                ->orderBy('nama_kategori')
                ->get(['id', 'kode_kategori', 'nama_kategori'])
                ->map(fn (AsetKategori $k) => [
                    'id' => $k->id,
                    'kode' => $k->kode_kategori,
                    'nama' => $k->nama_kategori,
                ]),
        ]);
    }

    public function merge(
        MergeAsetKategoriRequest $request,
        AsetKategori $kategori,
        MergeAsetKategori $merger,
    ): RedirectResponse {
        $source = AsetKategori::query()->findOrFail($request->validated('source_id'));
        $sourceNama = $source->nama_kategori;
        $targetNama = $kategori->nama_kategori;
        $stats = $merger->handle($kategori, $source);

        return back()->with(
            'success',
            "Kategori \"{$sourceNama}\" digabung ke \"{$targetNama}\" "
            ."(barang: {$stats['barang']}, katalog: {$stats['non_alkes']}).",
        );
    }
}
