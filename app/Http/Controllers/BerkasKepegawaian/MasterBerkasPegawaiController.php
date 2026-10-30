<?php

namespace App\Http\Controllers\BerkasKepegawaian;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasKepegawaian\StoreMasterBerkasPegawaiRequest;
use App\Http\Requests\BerkasKepegawaian\UpdateMasterBerkasPegawaiRequest;
use App\Models\BerkasPegawai;
use App\Models\MasterBerkasPegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterBerkasPegawaiController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $items = MasterBerkasPegawai::query()
            ->withCount('berkasPegawai')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('kode', 'like', "%{$q}%")
                        ->orWhere('nama_berkas', 'like', "%{$q}%");
                });
            })
            ->when($kategori !== '', fn ($query) => $query->where('kategori', $kategori))
            ->orderBy('no_urut')
            ->orderBy('nama_berkas')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (MasterBerkasPegawai $item) => [
                'kode' => $item->kode,
                'nama_berkas' => $item->nama_berkas,
                'kategori' => $item->kategori,
                'no_urut' => (int) $item->no_urut,
                'berkas_count' => (int) $item->berkas_pegawai_count,
            ]);

        $kategoriOptions = MasterBerkasPegawai::query()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->values()
            ->all();

        return Inertia::render('berkas-kepegawaian/master/index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'kategori' => $kategori,
            ],
            'kategoriOptions' => $kategoriOptions,
        ]);
    }

    public function store(StoreMasterBerkasPegawaiRequest $request): RedirectResponse
    {
        MasterBerkasPegawai::query()->create($request->validated());

        return redirect()
            ->route('berkas-kepegawaian.master.index')
            ->with('success', 'Jenis berkas berhasil ditambahkan.');
    }

    public function update(UpdateMasterBerkasPegawaiRequest $request, string $kode): RedirectResponse
    {
        $master = MasterBerkasPegawai::query()->findOrFail($kode);
        $master->update($request->validated());

        return redirect()
            ->route('berkas-kepegawaian.master.index')
            ->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function destroy(string $kode): RedirectResponse
    {
        $master = MasterBerkasPegawai::query()->findOrFail($kode);

        $usage = BerkasPegawai::query()->where('kode_berkas', $master->kode)->count();

        if ($usage > 0) {
            return back()->with(
                'error',
                "Jenis berkas \"{$master->nama_berkas}\" masih dipakai ({$usage} berkas). Hapus atau pindahkan berkas sebelum menghapus master.",
            );
        }

        $nama = $master->nama_berkas;
        $master->delete();

        return redirect()
            ->route('berkas-kepegawaian.master.index')
            ->with('success', "Jenis berkas \"{$nama}\" berhasil dihapus.");
    }
}
