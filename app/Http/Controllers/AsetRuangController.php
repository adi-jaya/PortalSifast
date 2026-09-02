<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyAsetRuangRequest;
use App\Http\Requests\StoreAsetRuangRequest;
use App\Http\Requests\UpdateAsetRuangRequest;
use App\Models\AsetRuang;
use App\Services\Inventaris\HapusAsetRuangCascade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetRuangController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetRuang::query()
            ->withCount('aset')
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
                'aset_count' => (int) $ruang->aset_count,
            ]);

        return Inertia::render('aset/master-ruang/index', [
            'items' => $items,
            'filters' => ['q' => $q],
            'stats' => [
                'total' => AsetRuang::query()->count(),
            ],
        ]);
    }

    public function store(StoreAsetRuangRequest $request): RedirectResponse
    {
        AsetRuang::query()->create($request->validated());

        return redirect()
            ->route('aset.master.ruang.index')
            ->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function update(UpdateAsetRuangRequest $request, AsetRuang $ruang): RedirectResponse
    {
        $ruang->update($request->validated());

        return redirect()
            ->route('aset.master.ruang.index')
            ->with('success', 'Ruang berhasil diperbarui.');
    }

    public function destroy(AsetRuang $ruang, HapusAsetRuangCascade $cascade): RedirectResponse
    {
        $nama = $ruang->nama_ruang;
        $asetCount = $ruang->aset()->count();
        $stats = $cascade->handle([$ruang->id]);

        $message = "Ruang \"{$nama}\" berhasil dihapus.";
        if ($stats['aset'] > 0) {
            $message .= " {$stats['aset']} aset di ruang ini ikut dihapus.";
        }

        return redirect()
            ->route('aset.master.ruang.index')
            ->with('success', $message);
    }

    public function bulkDestroy(BulkDestroyAsetRuangRequest $request, HapusAsetRuangCascade $cascade): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');
        $stats = $cascade->handle($ids);

        $message = "{$stats['ruang']} ruang berhasil dihapus.";
        if ($stats['aset'] > 0) {
            $message .= " {$stats['aset']} aset ikut dihapus (cascade).";
        }

        return back()->with('success', $message);
    }
}
