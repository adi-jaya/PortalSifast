<?php

namespace App\Http\Controllers;

use App\Http\Requests\KembalikanAsetPeminjamanRequest;
use App\Http\Requests\StoreAsetPeminjamanRequest;
use App\Models\Aset;
use App\Models\AsetPeminjaman;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\Inventaris\BuatPeminjamanAset;
use App\Services\Inventaris\KembalikanPeminjamanAset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetPeminjamanController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $rows = AsetPeminjaman::query()
            ->with(['aset.barang', 'peminjamUser', 'diserahkanOleh'])
            ->when($status === 'dipinjam', fn ($query) => $query->where('status', 'dipinjam'))
            ->when($status === 'dikembalikan', fn ($query) => $query->where('status', 'dikembalikan'))
            ->when($status === 'terlambat', fn ($query) => $query->terlambat())
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->where('nomor', 'like', $search)
                        ->orWhere('peminjam_nik', 'like', $search)
                        ->orWhereHas('aset', fn ($a) => $a->where('kode_aset', 'like', $search));
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $niks = $rows->getCollection()->pluck('peminjam_nik')->filter()->unique()->values();
        $pegawaiNama = $niks->isEmpty()
            ? collect()
            : Pegawai::query()->whereIn('nik', $niks)->pluck('nama', 'nik');

        $rows->setCollection(
            $rows->getCollection()->map(function (AsetPeminjaman $row) use ($pegawaiNama) {
                return [
                    'id' => $row->id,
                    'nomor' => $row->nomor,
                    'status' => $row->status,
                    'is_terlambat' => $row->isTerlambat(),
                    'tanggal_pinjam' => $row->tanggal_pinjam?->toDateTimeString(),
                    'tanggal_kembali_rencana' => $row->tanggal_kembali_rencana?->toDateString(),
                    'kode_aset' => $row->aset?->kode_aset,
                    'nama_barang' => $row->aset?->barang?->nama_barang,
                    'peminjam_label' => $row->peminjamUser?->name
                        ?? ($row->peminjam_nik ? ($pegawaiNama[$row->peminjam_nik] ?? $row->peminjam_nik) : '–'),
                    'diserahkan_oleh' => $row->diserahkanOleh?->name,
                ];
            })
        );

        return Inertia::render('aset-peminjaman/index', [
            'rows' => $rows,
            'filters' => [
                'status' => $status,
                'q' => $q,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $asetId = $request->integer('aset_id') ?: null;
        $aset = $asetId
            ? Aset::query()->with('barang')->find($asetId)
            : null;

        return Inertia::render('aset-peminjaman/create', [
            'initialAset' => $aset ? [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'nama_barang' => $aset->barang?->nama_barang,
                'status_ketersediaan' => $aset->status_ketersediaan,
            ] : null,
        ]);
    }

    public function store(StoreAsetPeminjamanRequest $request, BuatPeminjamanAset $service): RedirectResponse
    {
        $validated = $request->validated();
        $aset = Aset::query()->findOrFail($validated['aset_id']);

        $peminjaman = $service->handle(
            aset: $aset,
            actor: $request->user(),
            peminjamUserId: isset($validated['peminjam_user_id']) ? (int) $validated['peminjam_user_id'] : null,
            peminjamNik: $validated['peminjam_nik'] ?? null,
            tanggalPinjam: $validated['tanggal_pinjam'],
            tanggalKembaliRencana: $validated['tanggal_kembali_rencana'] ?? null,
            catatan: $validated['catatan'] ?? null,
        );

        return redirect()
            ->route('aset-peminjaman.show', $peminjaman)
            ->with('success', 'Peminjaman dicatat.');
    }

    public function show(AsetPeminjaman $peminjaman): Response
    {
        $peminjaman->load(['aset.barang', 'aset.ruang', 'peminjamUser', 'diserahkanOleh', 'diterimaKembaliOleh']);

        $peminjamLabel = $peminjaman->peminjamUser?->name;
        if (! $peminjamLabel && $peminjaman->peminjam_nik) {
            $peminjamLabel = Pegawai::query()->where('nik', $peminjaman->peminjam_nik)->value('nama')
                ?? $peminjaman->peminjam_nik;
        }

        return Inertia::render('aset-peminjaman/show', [
            'peminjaman' => [
                'id' => $peminjaman->id,
                'nomor' => $peminjaman->nomor,
                'status' => $peminjaman->status,
                'is_terlambat' => $peminjaman->isTerlambat(),
                'tanggal_pinjam' => $peminjaman->tanggal_pinjam?->toDateTimeString(),
                'tanggal_kembali_rencana' => $peminjaman->tanggal_kembali_rencana?->toDateString(),
                'tanggal_kembali_aktual' => $peminjaman->tanggal_kembali_aktual?->toDateTimeString(),
                'catatan' => $peminjaman->catatan,
                'kondisi_kembali' => $peminjaman->kondisi_kembali,
                'peminjam_label' => $peminjamLabel,
                'peminjam_nik' => $peminjaman->peminjam_nik,
                'diserahkan_oleh' => $peminjaman->diserahkanOleh?->name,
                'diterima_kembali_oleh' => $peminjaman->diterimaKembaliOleh?->name,
                'aset' => [
                    'id' => $peminjaman->aset?->id,
                    'kode_aset' => $peminjaman->aset?->kode_aset,
                    'nama_barang' => $peminjaman->aset?->barang?->nama_barang,
                    'nama_ruang' => $peminjaman->aset?->ruang?->nama_ruang,
                ],
            ],
        ]);
    }

    public function kembalikan(
        KembalikanAsetPeminjamanRequest $request,
        AsetPeminjaman $peminjaman,
        KembalikanPeminjamanAset $service,
    ): RedirectResponse {
        $service->handle(
            $peminjaman,
            $request->user(),
            $request->validated('kondisi_kembali'),
        );

        return redirect()
            ->route('aset-peminjaman.show', $peminjaman)
            ->with('success', 'Aset dikembalikan.');
    }

    public function searchAset(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $search = "%{$q}%";
        $items = Aset::query()
            ->with('barang')
            ->where('siklus_hidup', 'aktif')
            ->where('status_ketersediaan', 'tersedia')
            ->where(function ($query) use ($search) {
                $query->where('kode_aset', 'like', $search)
                    ->orWhere('no_seri', 'like', $search)
                    ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', $search));
            })
            ->orderBy('kode_aset')
            ->limit(20)
            ->get()
            ->map(fn (Aset $aset) => [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'nama_barang' => $aset->barang?->nama_barang,
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
