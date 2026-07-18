<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisBarangRequest;
use App\Http\Requests\UpdateInventarisBarangRequest;
use App\Models\InventarisBarang;
use App\Models\InventarisJenis;
use App\Models\InventarisKategori;
use App\Models\InventarisMerk;
use App\Models\InventarisProdusen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarisBarangController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');
        $idKategori = (string) $request->query('id_kategori', '');
        $idJenis = (string) $request->query('id_jenis', '');

        try {
            $query = InventarisBarang::query()
                ->with(['produsen', 'merk', 'kategori', 'jenis'])
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('kode_barang', 'like', $search)
                            ->orWhere('nama_barang', 'like', $search)
                            ->orWhereHas('produsen', fn ($p) => $p->where('nama_produsen', 'like', $search))
                            ->orWhereHas('merk', fn ($m) => $m->where('nama_merk', 'like', $search));
                    });
                })
                ->when($idKategori !== '', fn ($query) => $query->where('id_kategori', $idKategori))
                ->when($idJenis !== '', fn ($query) => $query->where('id_jenis', $idJenis))
                ->orderBy('nama_barang');

            $barang = $query->paginate(20)
                ->withQueryString()
                ->through(fn (InventarisBarang $item) => [
                    'kode_barang' => $item->kode_barang,
                    'nama_barang' => $item->nama_barang,
                    'jml_barang' => $item->jml_barang,
                    'nama_produsen' => $item->produsen?->nama_produsen ?? null,
                    'nama_merk' => $item->merk?->nama_merk ?? null,
                    'thn_produksi' => $item->thn_produksi,
                    'nama_kategori' => $item->kategori?->nama_kategori ?? null,
                    'nama_jenis' => $item->jenis?->nama_jenis ?? null,
                ]);

            $kategori = InventarisKategori::query()
                ->orderBy('nama_kategori')
                ->whereNotNull('id_kategori')
                ->where('id_kategori', '!=', '')
                ->where('id_kategori', '!=', '-')
                ->get(['id_kategori', 'nama_kategori']);

            $jenis = InventarisJenis::query()
                ->orderBy('nama_jenis')
                ->whereNotNull('id_jenis')
                ->where('id_jenis', '!=', '')
                ->where('id_jenis', '!=', '-')
                ->get(['id_jenis', 'nama_jenis']);
        } catch (\Throwable $e) {
            $barang = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
            $kategori = collect();
            $jenis = collect();
        }

        return Inertia::render('inventaris-barang/index', [
            'barang' => $barang,
            'kategori' => $kategori,
            'jenis' => $jenis,
            'filters' => [
                'q' => $q,
                'id_kategori' => $idKategori,
                'id_jenis' => $idJenis,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventaris-barang/create', $this->lookupOptions());
    }

    public function store(StoreInventarisBarangRequest $request): RedirectResponse
    {
        $v = $request->validated();

        InventarisBarang::query()->create([
            'kode_barang' => $v['kode_barang'],
            'nama_barang' => $v['nama_barang'],
            'jml_barang' => isset($v['jml_barang']) ? $v['jml_barang'] : null,
            'kode_produsen' => $v['kode_produsen'] ?? null,
            'id_merk' => $v['id_merk'] ?? null,
            'thn_produksi' => $v['thn_produksi'] ?? null,
            'isbn' => $v['isbn'] ?? null,
            'id_kategori' => $v['id_kategori'] ?? null,
            'id_jenis' => $v['id_jenis'] ?? null,
        ]);

        return redirect()
            ->route('inventaris-barang.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function show(InventarisBarang $barang): Response
    {
        $barang->load(['produsen', 'merk', 'kategori', 'jenis']);

        $units = $barang->inventaris()
            ->with('ruang')
            ->orderBy('no_inventaris')
            ->limit(100)
            ->get()
            ->map(fn ($inv) => [
                'no_inventaris' => $inv->no_inventaris,
                'status_barang' => $inv->status_barang,
                'nama_ruang' => $inv->ruang?->nama_ruang,
            ]);

        return Inertia::render('inventaris-barang/show', [
            'barang' => [
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'jml_barang' => $barang->jml_barang,
                'kode_produsen' => $barang->kode_produsen,
                'nama_produsen' => $barang->produsen?->nama_produsen ?? null,
                'alamat_produsen' => $barang->produsen?->alamat_produsen ?? null,
                'no_telp_produsen' => $barang->produsen?->no_telp ?? null,
                'email_produsen' => $barang->produsen?->email ?? null,
                'website_produsen' => $barang->produsen?->website_produsen ?? null,
                'nama_merk' => $barang->merk?->nama_merk ?? null,
                'thn_produksi' => $barang->thn_produksi,
                'isbn' => $barang->isbn,
                'nama_kategori' => $barang->kategori?->nama_kategori ?? null,
                'nama_jenis' => $barang->jenis?->nama_jenis ?? null,
            ],
            'units' => $units,
        ]);
    }

    public function edit(InventarisBarang $barang): Response
    {
        $barang->load(['produsen', 'merk', 'kategori', 'jenis']);

        return Inertia::render('inventaris-barang/edit', array_merge($this->lookupOptions(), [
            'barang' => [
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'jml_barang' => $barang->jml_barang,
                'kode_produsen' => $barang->kode_produsen,
                'nama_produsen' => $barang->produsen?->nama_produsen ?? null,
                'id_merk' => $barang->id_merk,
                'nama_merk' => $barang->merk?->nama_merk ?? null,
                'thn_produksi' => $barang->thn_produksi,
                'isbn' => $barang->isbn,
                'id_kategori' => $barang->id_kategori,
                'nama_kategori' => $barang->kategori?->nama_kategori ?? null,
                'id_jenis' => $barang->id_jenis,
                'nama_jenis' => $barang->jenis?->nama_jenis ?? null,
            ],
        ]));
    }

    public function update(UpdateInventarisBarangRequest $request, InventarisBarang $barang): RedirectResponse
    {
        $barang->update($request->validated());

        return redirect()
            ->route('inventaris-barang.show', $barang)
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(InventarisBarang $barang): RedirectResponse
    {
        $barang->delete();

        return redirect()
            ->route('inventaris-barang.index')
            ->with('success', 'Barang berhasil dihapus.');
    }

    /**
     * @return array{produsen: \Illuminate\Support\Collection, merk: \Illuminate\Support\Collection, kategori: \Illuminate\Support\Collection, jenis: \Illuminate\Support\Collection}
     */
    private function lookupOptions(): array
    {
        try {
            return [
                'produsen' => InventarisProdusen::query()
                    ->orderBy('nama_produsen')
                    ->whereNotNull('kode_produsen')
                    ->where('kode_produsen', '!=', '')
                    ->where('kode_produsen', '!=', '-')
                    ->get(['kode_produsen', 'nama_produsen']),
                'merk' => InventarisMerk::query()
                    ->orderBy('nama_merk')
                    ->whereNotNull('id_merk')
                    ->where('id_merk', '!=', '')
                    ->where('id_merk', '!=', '-')
                    ->get(['id_merk', 'nama_merk']),
                'kategori' => InventarisKategori::query()
                    ->orderBy('nama_kategori')
                    ->whereNotNull('id_kategori')
                    ->where('id_kategori', '!=', '')
                    ->where('id_kategori', '!=', '-')
                    ->get(['id_kategori', 'nama_kategori']),
                'jenis' => InventarisJenis::query()
                    ->orderBy('nama_jenis')
                    ->whereNotNull('id_jenis')
                    ->where('id_jenis', '!=', '')
                    ->where('id_jenis', '!=', '-')
                    ->get(['id_jenis', 'nama_jenis']),
            ];
        } catch (\Throwable $e) {
            return [
                'produsen' => collect(),
                'merk' => collect(),
                'kategori' => collect(),
                'jenis' => collect(),
            ];
        }
    }
}
