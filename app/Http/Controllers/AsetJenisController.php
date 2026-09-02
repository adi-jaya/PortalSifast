<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyAsetJenisRequest;
use App\Http\Requests\StoreAsetJenisRequest;
use App\Http\Requests\UpdateAsetJenisMerkRequest;
use App\Http\Requests\UpdateAsetJenisRequest;
use App\Models\AsetJenis;
use App\Models\AsetMerk;
use App\Services\Inventaris\GeneratorKodeMasterAset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetJenisController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $merkId = $request->query('merk_id');
        $onlyUnassigned = $request->boolean('only_unassigned');

        $items = AsetJenis::query()
            ->with('merk:id,nama_merk')
            ->withCount('barang')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama_jenis', 'like', "%{$q}%")
                        ->orWhere('kode_jenis', 'like', "%{$q}%");
                });
            })
            ->when(
                filled($merkId) && is_numeric($merkId),
                fn ($query) => $query->where('aset_merk_id', (int) $merkId),
            )
            ->when($onlyUnassigned, fn ($query) => $query->whereNull('aset_merk_id'))
            ->orderBy('nama_jenis')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetJenis $item) => [
                'id' => $item->id,
                'kode_jenis' => $item->kode_jenis,
                'nama_jenis' => $item->nama_jenis,
                'aset_merk_id' => $item->aset_merk_id,
                'merk_nama' => $item->merk?->nama_merk,
                'barang_count' => (int) $item->barang_count,
            ]);

        $total = AsetJenis::query()->count();
        $unassigned = AsetJenis::query()->whereNull('aset_merk_id')->count();

        return Inertia::render('aset/master-jenis/index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'merk_id' => filled($merkId) ? (string) $merkId : '',
                'only_unassigned' => $onlyUnassigned,
            ],
            'stats' => [
                'total' => $total,
                'unassigned' => $unassigned,
                'assigned' => $total - $unassigned,
            ],
            'merkOptions' => AsetMerk::query()
                ->orderBy('nama_merk')
                ->get(['id', 'nama_merk'])
                ->map(fn (AsetMerk $m) => [
                    'id' => $m->id,
                    'nama' => $m->nama_merk,
                ]),
        ]);
    }

    public function store(StoreAsetJenisRequest $request, GeneratorKodeMasterAset $generator): RedirectResponse
    {
        $validated = $request->validated();
        $kode = $validated['kode_jenis'] ?? null;

        if ($kode === null) {
            $kode = $generator->generate($validated['nama_jenis'], new AsetJenis, 'kode_jenis');
        }

        AsetJenis::query()->create([
            'kode_jenis' => $kode,
            'nama_jenis' => $validated['nama_jenis'],
            'aset_merk_id' => $validated['aset_merk_id'] ?? null,
        ]);

        return redirect()
            ->route('aset.master.jenis.index')
            ->with('success', 'Jenis berhasil ditambahkan.');
    }

    public function update(UpdateAsetJenisRequest $request, AsetJenis $jenis): RedirectResponse
    {
        $jenis->update($request->validated());

        return redirect()
            ->route('aset.master.jenis.index')
            ->with('success', 'Jenis berhasil diperbarui.');
    }

    public function destroy(AsetJenis $jenis): RedirectResponse
    {
        $usage = $jenis->barang()->count();

        if ($usage > 0) {
            return back()->with(
                'error',
                "Jenis \"{$jenis->nama_jenis}\" masih dipakai ({$usage} barang). Ubah referensi barang sebelum menghapus.",
            );
        }

        $nama = $jenis->nama_jenis;
        $jenis->delete();

        return redirect()
            ->route('aset.master.jenis.index')
            ->with('success', "Jenis \"{$nama}\" berhasil dihapus.");
    }

    public function bulkDestroy(BulkDestroyAsetJenisRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');

        $deletableIds = AsetJenis::query()
            ->whereIn('id', $ids)
            ->whereDoesntHave('barang')
            ->pluck('id');

        if ($deletableIds->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada jenis yang bisa dihapus — semua yang dipilih masih dipakai barang.',
            );
        }

        $deleted = AsetJenis::query()->whereIn('id', $deletableIds)->delete();
        $skipped = count($ids) - $deleted;

        if ($skipped > 0) {
            return back()->with(
                'success',
                "{$deleted} jenis berhasil dihapus. {$skipped} dilewati karena masih dipakai barang.",
            );
        }

        return back()->with('success', "{$deleted} jenis berhasil dihapus.");
    }

    public function updateMerk(UpdateAsetJenisMerkRequest $request, AsetJenis $jenis): RedirectResponse
    {
        $jenis->update([
            'aset_merk_id' => $request->validated('aset_merk_id'),
        ]);

        return back()->with('success', 'Merk jenis diperbarui.');
    }
}
