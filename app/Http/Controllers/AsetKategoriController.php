<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeAsetKategoriRequest;
use App\Http\Requests\StoreAsetKategoriRequest;
use App\Http\Requests\UpdateAsetKategoriRequest;
use App\Models\AsetKategori;
use App\Services\Inventaris\GeneratorKodeMasterAset;
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
            ->withCount(['barang', 'nonAlkes'])
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
                $nonAlkesCount = (int) $item->non_alkes_count;

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

    public function store(StoreAsetKategoriRequest $request, GeneratorKodeMasterAset $generator): RedirectResponse
    {
        $validated = $request->validated();
        $kode = $validated['kode_kategori'] ?? null;

        if ($kode === null) {
            $kode = $generator->generate($validated['nama_kategori'], new AsetKategori, 'kode_kategori');
        }

        AsetKategori::query()->create([
            'kode_kategori' => $kode,
            'nama_kategori' => $validated['nama_kategori'],
        ]);

        return redirect()
            ->route('aset.master.kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(UpdateAsetKategoriRequest $request, AsetKategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()
            ->route('aset.master.kategori.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(AsetKategori $kategori): RedirectResponse
    {
        $usage = $kategori->barang()->count() + $kategori->nonAlkes()->count();

        if ($usage > 0) {
            return back()->with(
                'error',
                "Kategori \"{$kategori->nama_kategori}\" masih dipakai ({$usage} referensi). Gabungkan ke kategori lain sebelum menghapus.",
            );
        }

        $nama = $kategori->nama_kategori;
        $kategori->delete();

        return redirect()
            ->route('aset.master.kategori.index')
            ->with('success', "Kategori \"{$nama}\" berhasil dihapus.");
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
