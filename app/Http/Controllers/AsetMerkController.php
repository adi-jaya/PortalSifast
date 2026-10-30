<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyAsetMerkRequest;
use App\Http\Requests\StoreAsetMerkRequest;
use App\Http\Requests\UpdateAsetMerkRequest;
use App\Models\AsetMerk;
use App\Services\Inventaris\GeneratorKodeMasterAset;
use App\Services\Inventaris\HapusAsetMerkCascade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetMerkController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetMerk::query()
            ->withCount(['barang', 'jenis'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama_merk', 'like', "%{$q}%")
                        ->orWhere('kode_merk', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_merk')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetMerk $merk) => [
                'id' => $merk->id,
                'kode_merk' => $merk->kode_merk,
                'nama_merk' => $merk->nama_merk,
                'barang_count' => (int) $merk->barang_count,
                'jenis_count' => (int) $merk->jenis_count,
            ]);

        return Inertia::render('aset/master-merk/index', [
            'items' => $items,
            'filters' => ['q' => $q],
            'stats' => [
                'total' => AsetMerk::query()->count(),
            ],
        ]);
    }

    public function store(StoreAsetMerkRequest $request, GeneratorKodeMasterAset $generator): RedirectResponse
    {
        $validated = $request->validated();
        $kode = $validated['kode_merk'] ?? null;

        if ($kode === null) {
            $kode = $generator->generate($validated['nama_merk'], new AsetMerk, 'kode_merk');
        }

        AsetMerk::query()->create([
            'kode_merk' => $kode,
            'nama_merk' => $validated['nama_merk'],
        ]);

        return redirect()
            ->route('aset.master.merk.index')
            ->with('success', 'Merk berhasil ditambahkan.');
    }

    public function update(UpdateAsetMerkRequest $request, AsetMerk $merk): RedirectResponse
    {
        $merk->update($request->validated());

        return redirect()
            ->route('aset.master.merk.index')
            ->with('success', 'Merk berhasil diperbarui.');
    }

    public function destroy(AsetMerk $merk, HapusAsetMerkCascade $cascade): RedirectResponse
    {
        $nama = $merk->nama_merk;
        $stats = $cascade->handle([$merk->id]);

        if ($stats['merk'] === 0) {
            return back()->with(
                'error',
                "Merk \"{$nama}\" masih dipakai barang master dan tidak bisa dihapus.",
            );
        }

        $message = "Merk \"{$nama}\" berhasil dihapus.";
        if ($stats['jenis'] > 0) {
            $message .= " {$stats['jenis']} jenis dilepas dari merk ini.";
        }

        return redirect()
            ->route('aset.master.merk.index')
            ->with('success', $message);
    }

    public function bulkDestroy(BulkDestroyAsetMerkRequest $request, HapusAsetMerkCascade $cascade): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');
        $stats = $cascade->handle($ids);

        if ($stats['merk'] === 0) {
            return back()->with(
                'error',
                'Tidak ada merk yang bisa dihapus — semua yang dipilih masih dipakai barang master.',
            );
        }

        $message = "{$stats['merk']} merk berhasil dihapus.";
        if ($stats['jenis'] > 0) {
            $message .= " {$stats['jenis']} jenis dilepas dari merk terhapus.";
        }
        if ($stats['skipped'] > 0) {
            $message .= " {$stats['skipped']} dilewati karena masih dipakai barang.";
        }

        return back()->with('success', $message);
    }
}
