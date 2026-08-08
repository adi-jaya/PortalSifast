<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\AsetMutasiLokasi;
use App\Models\AsetPeminjaman;
use App\Models\Pegawai;
use App\Models\Ticket;
use App\Services\InventarisPhotoResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AsetPublicController extends Controller
{
    private const MISSING_PHOTO_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function __construct(private InventarisPhotoResolver $photoResolver) {}

    /**
     * Halaman hasil scan QR — tanpa login.
     * Riwayat: guest lihat ringkas; nama peminjam & judul tiket hanya jika login.
     */
    public function show(Request $request, Aset $aset): InertiaResponse
    {
        $aset->load(['barang.merk', 'barang.jenis', 'barang.kategori', 'ruang', 'fotoUtama']);

        $authenticated = $request->user() !== null;

        $fotoPortal = $aset->fotoUtama?->path;
        $photoSrc = $fotoPortal
            ? asset('storage/'.$fotoPortal)
            : ($aset->path_foto_sumber ? route('aset.public.foto', $aset) : null);

        return Inertia::render('aset/public-scan', [
            'aset' => [
                'id' => $aset->id,
                'kode_aset' => $aset->kode_aset,
                'no_simrs' => $aset->no_simrs,
                'no_seri' => $aset->no_seri,
                'nama_barang' => $aset->barang?->nama_barang ?? $aset->kode_aset,
                'kode_barang' => $aset->barang?->kode_barang,
                'nama_merk' => $aset->barang?->merk?->nama_merk,
                'nama_jenis' => $aset->barang?->jenis?->nama_jenis,
                'nama_kategori' => $aset->barang?->kategori?->nama_kategori,
                'nama_ruang' => $aset->ruang?->nama_ruang,
                'kode_ruang' => $aset->ruang?->kode_ruang,
                'kelas_aset' => $aset->barang?->kelas_aset,
                'wajib_kalibrasi' => (bool) $aset->barang?->wajib_kalibrasi,
                'status_fungsi' => $aset->status_fungsi,
                'tingkat_kerusakan' => $aset->tingkat_kerusakan,
                'siklus_hidup' => $aset->siklus_hidup,
                'status_ketersediaan' => $aset->status_ketersediaan ?? 'tersedia',
                'tahun_registrasi' => $aset->tahun_registrasi,
                'photo_src' => $photoSrc,
            ],
            'authenticated' => $authenticated,
            'canManage' => $authenticated,
            'manageUrl' => $authenticated
                ? route('aset.show', $aset)
                : url('/'),
            'ticketCreateUrl' => '/tickets/create?asset_id='.$aset->id,
            'riwayat' => [
                'peminjaman' => $this->riwayatPeminjaman($aset, $authenticated),
                'mutasi' => $this->riwayatMutasi($aset, $authenticated),
                'tiket' => $this->riwayatTiket($aset, $authenticated),
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function riwayatPeminjaman(Aset $aset, bool $authenticated): array
    {
        $rows = AsetPeminjaman::query()
            ->with('peminjamUser:id,name')
            ->where('aset_id', $aset->id)
            ->latest('id')
            ->limit(8)
            ->get();

        $niks = $rows->pluck('peminjam_nik')->filter()->unique()->values();
        $pegawaiNama = $authenticated && $niks->isNotEmpty()
            ? Pegawai::query()->whereIn('nik', $niks)->pluck('nama', 'nik')
            : collect();

        return $rows->map(function (AsetPeminjaman $row) use ($authenticated, $pegawaiNama) {
            $item = [
                'id' => $row->id,
                'nomor' => $row->nomor,
                'status' => $row->status,
                'is_terlambat' => $row->isTerlambat(),
                'tanggal' => $row->tanggal_pinjam?->toDateString(),
                'tanggal_kembali_rencana' => $row->tanggal_kembali_rencana?->toDateString(),
            ];

            if ($authenticated) {
                $item['peminjam_label'] = $row->peminjamUser?->name
                    ?? ($row->peminjam_nik ? ($pegawaiNama[$row->peminjam_nik] ?? $row->peminjam_nik) : null);
                $item['url'] = route('aset-peminjaman.show', $row);
            }

            return $item;
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function riwayatMutasi(Aset $aset, bool $authenticated): array
    {
        $rows = AsetMutasiLokasi::query()
            ->with(['ruangAsal:id,nama_ruang', 'ruangTujuan:id,nama_ruang', 'penerimaUser:id,name'])
            ->where('aset_id', $aset->id)
            ->latest('id')
            ->limit(8)
            ->get();

        $niks = $rows->pluck('penerima_nik')->filter()->unique()->values();
        $pegawaiNama = $authenticated && $niks->isNotEmpty()
            ? Pegawai::query()->whereIn('nik', $niks)->pluck('nama', 'nik')
            : collect();

        return $rows->map(function (AsetMutasiLokasi $row) use ($authenticated, $pegawaiNama) {
            $item = [
                'id' => $row->id,
                'nomor' => $row->nomor,
                'tanggal' => $row->tanggal_mutasi?->toDateString(),
                'ruang_asal' => $row->ruangAsal?->nama_ruang,
                'ruang_tujuan' => $row->ruangTujuan?->nama_ruang,
            ];

            if ($authenticated) {
                $item['penerima_label'] = $row->penerimaUser?->name
                    ?? ($row->penerima_nik ? ($pegawaiNama[$row->penerima_nik] ?? $row->penerima_nik) : null);
                $item['url'] = route('aset-mutasi-lokasi.show', $row);
            }

            return $item;
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function riwayatTiket(Aset $aset, bool $authenticated): array
    {
        /** @var Collection<int, Ticket> $tickets */
        $tickets = Ticket::query()
            ->with('status:id,name,color')
            ->where(function ($q) use ($aset) {
                $q->where('asset_id', $aset->id);
                if ($aset->no_simrs) {
                    $q->orWhere('asset_no_inventaris', $aset->no_simrs);
                }
            })
            ->latest('id')
            ->limit(8)
            ->get(['id', 'ticket_number', 'title', 'ticket_status_id', 'created_at', 'asset_id', 'asset_no_inventaris']);

        return $tickets->map(function (Ticket $ticket) use ($authenticated) {
            $item = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status?->name,
                'tanggal' => $ticket->created_at?->toDateString(),
            ];

            if ($authenticated) {
                $item['title'] = $ticket->title;
                $item['url'] = route('tickets.show', $ticket);
            }

            return $item;
        })->all();
    }

    public function foto(Aset $aset): Response|SymfonyResponse
    {
        $aset->loadMissing('fotoUtama');

        if ($aset->fotoUtama?->path) {
            $path = storage_path('app/public/'.$aset->fotoUtama->path);
            if (is_file($path)) {
                return response()->file($path, [
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            }
        }

        $resolved = $this->photoResolver->resolve($aset->path_foto_sumber);

        if ($resolved === null) {
            return response(base64_decode(self::MISSING_PHOTO_PNG), 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=120',
            ]);
        }

        return response($resolved['binary'], 200, [
            'Content-Type' => $resolved['mime'],
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
