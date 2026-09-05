<?php

namespace App\Http\Controllers\BerkasKepegawaian;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasKepegawaian\ReplaceBerkasPegawaiRequest;
use App\Http\Requests\BerkasKepegawaian\StoreBerkasPegawaiRequest;
use App\Models\BerkasPegawai;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use App\Services\BerkasKepegawaian\BerkasKepegawaianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;

class BerkasKepegawaianController extends Controller
{
    public function __construct(private BerkasKepegawaianService $service) {}

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $pegawai = Pegawai::query()
            ->where('stts_aktif', 'AKTIF')
            ->withCount('berkasPegawai')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('nik', 'like', "%{$q}%")
                        ->orWhere('nama', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Pegawai $p) => [
                'nik' => $p->nik,
                'nama' => $p->nama,
                'jbtn' => $p->jbtn,
                'berkas_count' => (int) $p->berkas_pegawai_count,
            ]);

        return Inertia::render('berkas-kepegawaian/index', [
            'pegawai' => $pegawai,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function show(string $nik): Response
    {
        $pegawai = Pegawai::query()
            ->where('nik', $nik)
            ->where('stts_aktif', 'AKTIF')
            ->firstOrFail();

        $berkasRows = BerkasPegawai::query()
            ->where('nik', $nik)
            ->with('master')
            ->get()
            ->map(fn (BerkasPegawai $row) => [
                'kode_berkas' => $row->kode_berkas,
                'nama_berkas' => $row->master?->nama_berkas ?? $row->kode_berkas,
                'kategori' => $row->master?->kategori ?? 'Lainnya',
                'no_urut' => (int) ($row->master?->no_urut ?? 9999),
                'tgl_uploud' => $row->tgl_uploud,
                'berkas' => $row->berkas,
                'public_url' => $row->publicUrl(),
            ])
            ->sortBy([
                ['no_urut', 'asc'],
                ['nama_berkas', 'asc'],
            ])
            ->values();

        $sections = $berkasRows
            ->groupBy('kategori')
            ->map(fn ($items, $kategori) => [
                'kategori' => (string) $kategori,
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();

        $masterOptions = MasterBerkasPegawai::query()
            ->orderBy('no_urut')
            ->orderBy('nama_berkas')
            ->get(['kode', 'nama_berkas', 'kategori'])
            ->map(fn (MasterBerkasPegawai $m) => [
                'kode' => $m->kode,
                'nama_berkas' => $m->nama_berkas,
                'kategori' => $m->kategori,
            ])
            ->values()
            ->all();

        return Inertia::render('berkas-kepegawaian/show', [
            'pegawai' => [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'jbtn' => $pegawai->jbtn,
                'departemen' => $pegawai->departemen,
            ],
            'sections' => $sections,
            'masterOptions' => $masterOptions,
            'uploadedKodes' => $berkasRows->pluck('kode_berkas')->values()->all(),
        ]);
    }

    public function store(StoreBerkasPegawaiRequest $request, string $nik): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->service->upload(
                $nik,
                $data['kode_berkas'],
                $data['dokumen'],
                $data['tgl_uploud'],
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil diunggah.');
    }

    public function replace(ReplaceBerkasPegawaiRequest $request, string $nik, string $kode): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->service->replace(
                $nik,
                $kode,
                $data['dokumen'],
                $data['tgl_uploud'],
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil diganti.');
    }

    public function destroy(string $nik, string $kode): RedirectResponse
    {
        try {
            $this->service->delete($nik, $kode);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Berkas berhasil dihapus.');
    }
}
