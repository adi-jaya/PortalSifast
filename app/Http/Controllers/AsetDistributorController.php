<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyAsetDistributorRequest;
use App\Http\Requests\StoreAsetDistributorRequest;
use App\Http\Requests\UpdateAsetDistributorRequest;
use App\Models\AsetDistributor;
use App\Services\Inventaris\GeneratorKodeMasterAset;
use App\Services\Inventaris\HapusAsetDistributor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetDistributorController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetDistributor::query()
            ->withCount('aset')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama_distributor', 'like', "%{$q}%")
                        ->orWhere('kode_distributor', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('no_telp', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama_distributor')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetDistributor $distributor) => [
                'id' => $distributor->id,
                'kode_distributor' => $distributor->kode_distributor,
                'nama_distributor' => $distributor->nama_distributor,
                'alamat' => $distributor->alamat,
                'no_telp' => $distributor->no_telp,
                'email' => $distributor->email,
                'aset_count' => (int) $distributor->aset_count,
            ]);

        return Inertia::render('aset/master-distributor/index', [
            'items' => $items,
            'filters' => ['q' => $q],
            'stats' => [
                'total' => AsetDistributor::query()->count(),
            ],
        ]);
    }

    public function store(StoreAsetDistributorRequest $request, GeneratorKodeMasterAset $generator): RedirectResponse
    {
        $validated = $request->validated();
        $kode = $validated['kode_distributor'] ?? null;

        if ($kode === null) {
            $kode = $generator->generate($validated['nama_distributor'], new AsetDistributor, 'kode_distributor');
        }

        AsetDistributor::query()->create([
            'kode_distributor' => $kode,
            'nama_distributor' => $validated['nama_distributor'],
            'alamat' => $validated['alamat'] ?? null,
            'no_telp' => $validated['no_telp'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        return redirect()
            ->route('aset.master.distributor.index')
            ->with('success', 'Distributor berhasil ditambahkan.');
    }

    public function update(UpdateAsetDistributorRequest $request, AsetDistributor $distributor): RedirectResponse
    {
        $distributor->update($request->validated());

        return redirect()
            ->route('aset.master.distributor.index')
            ->with('success', 'Distributor berhasil diperbarui.');
    }

    public function destroy(AsetDistributor $distributor, HapusAsetDistributor $hapus): RedirectResponse
    {
        $nama = $distributor->nama_distributor;
        $stats = $hapus->handle([$distributor->id]);

        if ($stats['distributor'] === 0) {
            return back()->with(
                'error',
                "Distributor \"{$nama}\" masih dipakai aset dan tidak bisa dihapus.",
            );
        }

        return redirect()
            ->route('aset.master.distributor.index')
            ->with('success', "Distributor \"{$nama}\" berhasil dihapus.");
    }

    public function bulkDestroy(BulkDestroyAsetDistributorRequest $request, HapusAsetDistributor $hapus): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = $request->validated('ids');
        $stats = $hapus->handle($ids);

        if ($stats['distributor'] === 0) {
            return back()->with(
                'error',
                'Tidak ada distributor yang bisa dihapus — semua yang dipilih masih dipakai aset.',
            );
        }

        $message = "{$stats['distributor']} distributor berhasil dihapus.";
        if ($stats['skipped'] > 0) {
            $message .= " {$stats['skipped']} dilewati karena masih dipakai aset.";
        }

        return back()->with('success', $message);
    }
}
