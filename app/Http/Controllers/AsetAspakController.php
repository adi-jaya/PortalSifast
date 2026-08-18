<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsetAspakRequest;
use App\Http\Requests\UpdateAsetAspakRequest;
use App\Models\AsetAspakAlat;
use App\Services\Inventaris\GeneratorKodeAspak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetAspakController extends Controller
{
    public function __construct(private GeneratorKodeAspak $kodeGenerator) {}

    public function suggestKode(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id');
        $excludeId = $request->query('exclude_id');

        $parent = filled($parentId)
            ? AsetAspakAlat::query()->find((int) $parentId)
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

        $items = AsetAspakAlat::query()
            ->with('parent:id,nama_alat,kode,alat_code')
            ->withCount(['children as children_count', 'barang'])
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
                'parent_id' => $item->parent_id,
                'parent_nama' => $item->parent?->nama_alat,
                'wajib_kalibrasi' => (bool) $item->wajib_kalibrasi,
                'durasi_kalibrasi_hari' => $item->durasi_kalibrasi_hari,
                'is_leaf' => (int) $item->children_count === 0,
                'children_count' => (int) $item->children_count,
                'barang_count' => (int) $item->barang_count,
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
            'parentOptions' => AsetAspakAlat::query()
                ->orderBy('kode')
                ->orderBy('nama_alat')
                ->get(['id', 'kode', 'alat_code', 'nama_alat'])
                ->map(fn (AsetAspakAlat $item) => [
                    'id' => $item->id,
                    'kode' => $item->kode ?: $item->alat_code,
                    'nama' => $item->nama_alat,
                ]),
        ]);
    }

    public function store(StoreAsetAspakRequest $request): RedirectResponse
    {
        $item = new AsetAspakAlat;
        $item->id_alat_aspak = AsetAspakAlat::generateIdAspak();
        $this->fillCatalog($item, $request->validated());
        $item->save();

        return redirect()
            ->route('aset.master.aspak.index')
            ->with('success', 'Katalog ASPAK berhasil ditambahkan.');
    }

    public function update(UpdateAsetAspakRequest $request, AsetAspakAlat $aspak): RedirectResponse
    {
        $this->fillCatalog($aspak, $request->validated());
        $aspak->save();

        return redirect()
            ->route('aset.master.aspak.index')
            ->with('success', 'Katalog ASPAK berhasil diperbarui.');
    }

    public function destroy(AsetAspakAlat $aspak): RedirectResponse
    {
        $childCount = $aspak->children()->count();
        if ($childCount > 0) {
            return back()->with(
                'error',
                "Katalog \"{$aspak->nama_alat}\" masih punya {$childCount} anak. Hapus atau pindahkan anaknya dulu.",
            );
        }

        $barangCount = $aspak->barang()->count();
        if ($barangCount > 0) {
            return back()->with(
                'error',
                "Katalog \"{$aspak->nama_alat}\" masih dipakai {$barangCount} barang. Tidak bisa dihapus.",
            );
        }

        $nama = $aspak->nama_alat;
        $aspak->delete();

        return redirect()
            ->route('aset.master.aspak.index')
            ->with('success', "Katalog \"{$nama}\" berhasil dihapus.");
    }

    /**
     * @param  array{nama_alat: string, sinonim?: string|null, parent_id?: int|null, wajib_kalibrasi?: bool, durasi_kalibrasi_hari?: int|null}  $data
     */
    private function fillCatalog(AsetAspakAlat $item, array $data): void
    {
        $parent = isset($data['parent_id'])
            ? AsetAspakAlat::query()->find($data['parent_id'])
            : null;
        $parentChanged = $item->exists && $item->parent_id !== $parent?->id;

        $parentChanged = $item->exists && $item->parent_id !== $parent?->id;

        if (! $item->exists || $parentChanged) {
            $kode = $this->kodeGenerator->generate($parent, $item->exists ? $item->id : null);
        } else {
            $kode = $item->kode ?: $item->alat_code;
        }

        $wajibKalibrasi = (bool) ($data['wajib_kalibrasi'] ?? false);

        $item->nama_alat = $data['nama_alat'];
        $item->kode = $kode;
        $item->alat_code = $kode;
        $item->sinonim = $data['sinonim'] ?? null;
        $item->wajib_kalibrasi = $wajibKalibrasi;
        $item->durasi_kalibrasi_hari = $wajibKalibrasi
            ? ($data['durasi_kalibrasi_hari'] ?? $item->durasi_kalibrasi_hari ?? 700)
            : ($data['durasi_kalibrasi_hari'] ?? 700);
        $item->placeUnder($parent);
    }
}
