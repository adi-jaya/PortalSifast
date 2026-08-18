<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsetNonAlkesRequest;
use App\Http\Requests\UpdateAsetNonAlkesKategoriRequest;
use App\Http\Requests\UpdateAsetNonAlkesNamaRequest;
use App\Http\Requests\UpdateAsetNonAlkesRequest;
use App\Models\AsetKategori;
use App\Models\AsetNonAlkes;
use App\Services\Inventaris\GeneratorKodeNonAlkes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetNonAlkesController extends Controller
{
    public function __construct(private GeneratorKodeNonAlkes $kodeGenerator) {}

    public function suggestKode(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id');
        $excludeId = $request->query('exclude_id');

        $parent = filled($parentId)
            ? AsetNonAlkes::query()->where('deleted', false)->find((int) $parentId)
            : null;

        return response()->json([
            'kode' => $this->kodeGenerator->generate(
                $parent,
                filled($excludeId) ? (int) $excludeId : null,
            ),
        ]);
    }

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
            ->withCount([
                'children as children_count' => fn ($query) => $query->where('deleted', false),
                'barang',
            ])
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
                'parent_id' => $item->parent_id,
                'parent_nama' => $item->parent?->nama_alat,
                'is_leaf' => (int) $item->children_count === 0,
                'children_count' => (int) $item->children_count,
                'barang_count' => (int) $item->barang_count,
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
            'parentOptions' => AsetNonAlkes::query()
                ->where('deleted', false)
                ->orderBy('kode')
                ->orderBy('nama_alat')
                ->get(['id', 'kode', 'alat_code', 'nama_alat', 'level'])
                ->map(fn (AsetNonAlkes $item) => [
                    'id' => $item->id,
                    'kode' => $item->kode ?: $item->alat_code,
                    'nama' => $item->nama_alat,
                    'level' => $item->level,
                ]),
        ]);
    }

    public function store(StoreAsetNonAlkesRequest $request): RedirectResponse
    {
        $item = new AsetNonAlkes;
        $item->id_alat = AsetNonAlkes::generateIdAlat();
        $item->deleted = false;
        $this->fillCatalog($item, $request->validated());
        $item->save();

        return redirect()
            ->route('aset.master.non-alkes.index')
            ->with('success', 'Katalog berhasil ditambahkan.');
    }

    public function update(UpdateAsetNonAlkesRequest $request, AsetNonAlkes $nonAlkes): RedirectResponse
    {
        $this->fillCatalog($nonAlkes, $request->validated());
        $nonAlkes->save();
        $nonAlkes->syncDescendantLevels();

        return redirect()
            ->route('aset.master.non-alkes.index')
            ->with('success', 'Katalog berhasil diperbarui.');
    }

    public function destroy(AsetNonAlkes $nonAlkes): RedirectResponse
    {
        $childCount = $nonAlkes->children()->where('deleted', false)->count();
        if ($childCount > 0) {
            return back()->with(
                'error',
                "Katalog \"{$nonAlkes->nama_alat}\" masih punya {$childCount} anak. Hapus atau pindahkan anaknya dulu.",
            );
        }

        $barangCount = $nonAlkes->barang()->count();
        if ($barangCount > 0) {
            return back()->with(
                'error',
                "Katalog \"{$nonAlkes->nama_alat}\" masih dipakai {$barangCount} barang. Tidak bisa dihapus.",
            );
        }

        $nama = $nonAlkes->nama_alat;
        $nonAlkes->update(['deleted' => true]);

        return redirect()
            ->route('aset.master.non-alkes.index')
            ->with('success', "Katalog \"{$nama}\" berhasil dihapus.");
    }

    /**
     * @param  array{nama_alat: string, kode?: string|null, sinonim?: string|null, parent_id?: int|null, aset_kategori_id?: int|null}  $data
     */
    private function fillCatalog(AsetNonAlkes $item, array $data): void
    {
        $parent = isset($data['parent_id'])
            ? AsetNonAlkes::query()->where('deleted', false)->find($data['parent_id'])
            : null;
        $parentChanged = $item->exists && $item->parent_id !== $parent?->id;
        $kode = filled($data['kode'] ?? null) ? $data['kode'] : null;

        if ($kode === null || $parentChanged) {
            $kode = $this->kodeGenerator->generate($parent, $item->exists ? $item->id : null);
        }

        $item->nama_alat = $data['nama_alat'];
        $item->kode = $kode;
        $item->alat_code = $kode;
        $item->sinonim = $data['sinonim'] ?? null;
        $item->aset_kategori_id = $data['aset_kategori_id'] ?? null;
        $item->placeUnder($parent);
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
