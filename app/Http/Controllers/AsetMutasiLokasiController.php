<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsetMutasiLokasiRequest;
use App\Models\Aset;
use App\Models\AsetMutasiLokasi;
use App\Models\AsetRuang;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\Inventaris\BuatMutasiLokasiAset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetMutasiLokasiController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $rows = AsetMutasiLokasi::query()
            ->with(['aset.barang', 'ruangAsal', 'ruangTujuan', 'penerimaUser', 'dicatatOleh'])
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->where('nomor', 'like', $search)
                        ->orWhere('penerima_nik', 'like', $search)
                        ->orWhereHas('aset', fn ($a) => $a->where('kode_aset', 'like', $search));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $niks = $rows->getCollection()->pluck('penerima_nik')->filter()->unique()->values();
        $pegawaiNama = $niks->isEmpty()
            ? collect()
            : Pegawai::query()->whereIn('nik', $niks)->pluck('nama', 'nik');

        $rows->setCollection(
            $rows->getCollection()->map(function (AsetMutasiLokasi $row) use ($pegawaiNama) {
                return [
                    'id' => $row->id,
                    'nomor' => $row->nomor,
                    'tanggal_mutasi' => $row->tanggal_mutasi?->toDateTimeString(),
                    'kode_aset' => $row->aset?->kode_aset,
                    'nama_barang' => $row->aset?->barang?->nama_barang,
                    'ruang_asal' => $row->ruangAsal?->nama_ruang,
                    'ruang_tujuan' => $row->ruangTujuan?->nama_ruang,
                    'penerima_label' => $row->penerimaUser?->name
                        ?? ($row->penerima_nik ? ($pegawaiNama[$row->penerima_nik] ?? $row->penerima_nik) : '–'),
                    'dicatat_oleh' => $row->dicatatOleh?->name,
                ];
            })
        );

        return Inertia::render('aset-mutasi-lokasi/index', [
            'rows' => $rows,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(Request $request): Response
    {
        $asetId = $request->integer('aset_id') ?: null;
        $aset = $asetId
            ? Aset::query()->with(['barang', 'ruang'])->find($asetId)
            : null;

        return Inertia::render('aset-mutasi-lokasi/create', [
            'initialAset' => $aset ? [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'nama_barang' => $aset->barang?->nama_barang,
                'aset_ruang_id' => $aset->aset_ruang_id,
                'nama_ruang' => $aset->ruang?->nama_ruang,
                'status_ketersediaan' => $aset->status_ketersediaan,
            ] : null,
            'ruangOptions' => AsetRuang::query()
                ->orderBy('nama_ruang')
                ->get(['id', 'kode_ruang', 'nama_ruang']),
        ]);
    }

    public function store(StoreAsetMutasiLokasiRequest $request, BuatMutasiLokasiAset $service): RedirectResponse
    {
        $validated = $request->validated();
        $aset = Aset::query()->findOrFail($validated['aset_id']);

        $mutasi = $service->handle(
            aset: $aset,
            actor: $request->user(),
            ruangTujuanId: (int) $validated['aset_ruang_tujuan_id'],
            penerimaUserId: isset($validated['penerima_user_id']) ? (int) $validated['penerima_user_id'] : null,
            penerimaNik: $validated['penerima_nik'] ?? null,
            tanggalMutasi: $validated['tanggal_mutasi'],
            catatan: $validated['catatan'] ?? null,
        );

        return redirect()
            ->route('aset-mutasi-lokasi.show', $mutasi)
            ->with('success', 'Mutasi lokasi dicatat.');
    }

    public function show(AsetMutasiLokasi $mutasi): Response
    {
        $mutasi->load(['aset.barang', 'ruangAsal', 'ruangTujuan', 'penerimaUser', 'dicatatOleh']);

        $penerimaLabel = $mutasi->penerimaUser?->name;
        if (! $penerimaLabel && $mutasi->penerima_nik) {
            $penerimaLabel = Pegawai::query()->where('nik', $mutasi->penerima_nik)->value('nama')
                ?? $mutasi->penerima_nik;
        }

        return Inertia::render('aset-mutasi-lokasi/show', [
            'mutasi' => [
                'id' => $mutasi->id,
                'nomor' => $mutasi->nomor,
                'tanggal_mutasi' => $mutasi->tanggal_mutasi?->toDateTimeString(),
                'catatan' => $mutasi->catatan,
                'penerima_label' => $penerimaLabel,
                'penerima_nik' => $mutasi->penerima_nik,
                'dicatat_oleh' => $mutasi->dicatatOleh?->name,
                'ruang_asal' => $mutasi->ruangAsal?->nama_ruang,
                'ruang_tujuan' => $mutasi->ruangTujuan?->nama_ruang,
                'aset' => [
                    'id' => $mutasi->aset?->id,
                    'kode_aset' => $mutasi->aset?->kode_aset,
                    'nama_barang' => $mutasi->aset?->barang?->nama_barang,
                ],
            ],
        ]);
    }

    public function searchAset(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $search = "%{$q}%";
        $items = Aset::query()
            ->with(['barang', 'ruang'])
            ->where('siklus_hidup', 'aktif')
            ->where('status_ketersediaan', 'tersedia')
            ->where(function ($query) use ($search) {
                $query->where('kode_aset', 'like', $search)
                    ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', $search));
            })
            ->orderBy('kode_aset')
            ->limit(20)
            ->get()
            ->map(fn (Aset $aset) => [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'nama_barang' => $aset->barang?->nama_barang,
                'aset_ruang_id' => $aset->aset_ruang_id,
                'nama_ruang' => $aset->ruang?->nama_ruang,
                'label' => trim($aset->kode_aset.' — '.($aset->barang?->nama_barang ?? '')),
            ]);

        return response()->json($items);
    }

    public function searchPegawai(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Pegawai::query()->where('stts_aktif', 'AKTIF');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        $pegawai = $query->orderBy('nama')->limit(20)->get(['nik', 'nama', 'jbtn']);

        return response()->json(
            $pegawai->map(fn (Pegawai $p) => [
                'nik' => $p->nik,
                'nama' => $p->nama,
                'label' => trim($p->nama.($p->jbtn ? " — {$p->jbtn}" : '')),
            ]),
        );
    }

    public function searchUser(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(
            $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'label' => $u->name.($u->email ? " — {$u->email}" : ''),
            ]),
        );
    }
}
