<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisRequest;
use App\Http\Requests\UpdateInventarisRequest;
use App\Http\Requests\UpdateInventarisStatusRequest;
use App\Models\Inventaris;
use App\Models\InventarisBarang;
use App\Models\InventarisRuang;
use App\Models\Ticket;
use App\Services\InventarisPhotoResolver;
use App\Services\InventarisQrCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarisController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $idRuang = (string) $request->query('id_ruang', '');
        $statusOptions = ['Ada', 'Rusak', 'Hilang', 'Perbaikan', 'Dipinjam'];

        try {
            $query = Inventaris::query()
                ->with(['barang', 'ruang', 'gambar'])
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('no_inventaris', 'like', $search)
                            ->orWhere('kode_barang', 'like', $search)
                            ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', $search));
                    });
                })
                ->when($status !== '', fn ($query) => $query->where('status_barang', $status))
                ->when($idRuang !== '', fn ($query) => $query->where('id_ruang', $idRuang))
                ->orderBy('no_inventaris');

            $inventaris = $query->paginate(20)->withQueryString();

            $nos = $inventaris->getCollection()->pluck('no_inventaris');
            $openTicketCounts = $nos->isEmpty()
                ? collect()
                : Ticket::query()
                    ->whereIn('asset_no_inventaris', $nos)
                    ->whereHas('status', fn ($q) => $q->where('is_closed', false))
                    ->select('asset_no_inventaris', DB::raw('count(*) as aggregate'))
                    ->groupBy('asset_no_inventaris')
                    ->pluck('aggregate', 'asset_no_inventaris');

            $inventaris->setCollection(
                $inventaris->getCollection()->map(fn (Inventaris $inv) => [
                    'no_inventaris' => $inv->no_inventaris,
                    'kode_barang' => $inv->kode_barang,
                    'nama_barang' => $inv->barang?->nama_barang ?? $inv->kode_barang,
                    'nama_ruang' => $inv->ruang?->nama_ruang ?? null,
                    'status_barang' => $inv->status_barang ?? null,
                    'asal_barang' => $inv->asal_barang,
                    'harga' => $inv->harga,
                    'has_photo' => filled($inv->gambar?->photo),
                    'photo_src' => filled($inv->gambar?->photo)
                        ? route('inventaris.photo', $inv->no_inventaris)
                        : null,
                    'open_tickets' => (int) ($openTicketCounts[$inv->no_inventaris] ?? 0),
                ])
            );

            $ruang = InventarisRuang::query()
                ->orderBy('nama_ruang')
                ->get(['id_ruang', 'nama_ruang']);

            $statusCounts = Inventaris::query()
                ->select('status_barang', DB::raw('count(*) as aggregate'))
                ->groupBy('status_barang')
                ->pluck('aggregate', 'status_barang');

            $stats = [
                'total' => (int) $statusCounts->sum(),
                'by_status' => collect($statusOptions)
                    ->mapWithKeys(fn (string $opt) => [$opt => (int) ($statusCounts[$opt] ?? 0)])
                    ->all(),
            ];
        } catch (\Throwable $e) {
            $inventaris = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
            $ruang = collect();
            $stats = [
                'total' => 0,
                'by_status' => collect($statusOptions)->mapWithKeys(fn (string $opt) => [$opt => 0])->all(),
            ];
        }

        return Inertia::render('inventaris/index', [
            'inventaris' => $inventaris,
            'ruang' => $ruang,
            'stats' => $stats,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'id_ruang' => $idRuang,
            ],
            'statusOptions' => $statusOptions,
        ]);
    }

    public function export(Request $request): StreamedResponse|HttpResponse
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $idRuang = (string) $request->query('id_ruang', '');

        try {
            $rows = Inventaris::query()
                ->with(['barang', 'ruang'])
                ->when($q !== '', function ($query) use ($q) {
                    $search = "%{$q}%";
                    $query->where(function ($q2) use ($search) {
                        $q2->where('no_inventaris', 'like', $search)
                            ->orWhere('kode_barang', 'like', $search)
                            ->orWhereHas('barang', fn ($b) => $b->where('nama_barang', 'like', $search));
                    });
                })
                ->when($status !== '', fn ($query) => $query->where('status_barang', $status))
                ->when($idRuang !== '', fn ($query) => $query->where('id_ruang', $idRuang))
                ->orderBy('no_inventaris')
                ->limit(5000)
                ->get();
        } catch (\Throwable $e) {
            return response('Tidak dapat mengekspor inventaris.', 503);
        }

        $filename = 'inventaris-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'no_inventaris',
                'kode_barang',
                'nama_barang',
                'asal_barang',
                'tgl_pengadaan',
                'harga',
                'status_barang',
                'nama_ruang',
                'no_rak',
                'no_box',
            ]);

            foreach ($rows as $inv) {
                fputcsv($handle, [
                    $inv->no_inventaris,
                    $inv->kode_barang,
                    $inv->barang?->nama_barang ?? '',
                    $inv->asal_barang,
                    $inv->tgl_pengadaan,
                    $inv->harga,
                    $inv->status_barang,
                    $inv->ruang?->nama_ruang ?? '',
                    $inv->no_rak,
                    $inv->no_box,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function labelPrint(Request $request, Inventaris $inventaris, InventarisQrCodeGenerator $qrCodeGenerator): View
    {
        $inventaris->load(['barang', 'ruang']);

        return view('inventaris.label-print', [
            'title' => 'Label QR '.$inventaris->no_inventaris,
            'subtitle' => 'Label tunggal inventaris',
            'labels' => [$this->buildLabelPayload($inventaris, $qrCodeGenerator)],
            'backUrl' => route('inventaris.show', $inventaris->no_inventaris),
            'autoPrint' => $request->boolean('autoprint'),
        ]);
    }

    public function labelPrintBatch(Request $request, InventarisQrCodeGenerator $qrCodeGenerator): View
    {
        $idRuang = (string) $request->query('id_ruang', '');

        abort_if($idRuang === '', 422, 'Pilih ruang terlebih dahulu.');

        $ruang = InventarisRuang::query()->find($idRuang);
        abort_if($ruang === null, 404, 'Ruang tidak ditemukan.');

        $items = Inventaris::query()
            ->with(['barang', 'ruang'])
            ->where('id_ruang', $idRuang)
            ->orderBy('no_inventaris')
            ->limit(300)
            ->get();

        $labels = $items
            ->map(fn (Inventaris $inventaris) => $this->buildLabelPayload($inventaris, $qrCodeGenerator))
            ->all();

        return view('inventaris.label-print', [
            'title' => 'Label QR Ruang '.$ruang->nama_ruang,
            'subtitle' => 'Cetak massal ruang '.$ruang->nama_ruang.' ('.count($labels).' label)',
            'labels' => $labels,
            'backUrl' => route('inventaris.index', ['id_ruang' => $idRuang]),
            'autoPrint' => $request->boolean('autoprint'),
        ]);
    }

    public function audit(Request $request): Response
    {
        $idRuang = (string) $request->query('id_ruang', '');
        $statusOptions = ['Ada', 'Rusak', 'Hilang', 'Perbaikan', 'Dipinjam'];

        abort_if($idRuang === '', 422, 'Pilih ruang untuk memulai audit.');

        $ruang = InventarisRuang::query()->find($idRuang);
        abort_if($ruang === null, 404, 'Ruang tidak ditemukan.');

        $items = Inventaris::query()
            ->with(['barang', 'gambar'])
            ->where('id_ruang', $idRuang)
            ->orderBy('no_inventaris')
            ->limit(300)
            ->get();

        $openTicketCounts = Ticket::query()
            ->whereIn('asset_no_inventaris', $items->pluck('no_inventaris'))
            ->whereHas('status', fn ($q) => $q->where('is_closed', false))
            ->selectRaw('asset_no_inventaris, COUNT(*) as aggregate')
            ->groupBy('asset_no_inventaris')
            ->pluck('aggregate', 'asset_no_inventaris');

        return Inertia::render('inventaris/audit', [
            'ruang' => [
                'id_ruang' => $ruang->id_ruang,
                'nama_ruang' => $ruang->nama_ruang,
            ],
            'items' => $items->map(fn (Inventaris $inv) => [
                'no_inventaris' => $inv->no_inventaris,
                'kode_barang' => $inv->kode_barang,
                'nama_barang' => $inv->barang?->nama_barang ?? $inv->kode_barang,
                'status_barang' => $inv->status_barang,
                'has_photo' => filled($inv->gambar?->photo),
                'photo_src' => filled($inv->gambar?->photo)
                    ? route('inventaris.photo', $inv->no_inventaris)
                    : null,
                'open_tickets' => (int) ($openTicketCounts[$inv->no_inventaris] ?? 0),
            ]),
            'statusOptions' => $statusOptions,
            'allRuang' => InventarisRuang::query()
                ->orderBy('nama_ruang')
                ->get(['id_ruang', 'nama_ruang']),
        ]);
    }

    public function updateStatus(UpdateInventarisStatusRequest $request, Inventaris $inventaris): RedirectResponse
    {
        try {
            $inventaris->update([
                'status_barang' => $request->validated('status_barang'),
            ]);
        } catch (QueryException $e) {
            return back()->withErrors([
                'status_barang' => 'Gagal menyimpan status ke SIMRS. Periksa hak tulis database.',
            ]);
        }

        return back()->with('success', 'Status '.$inventaris->no_inventaris.' diperbarui.');
    }

    /**
     * @return array{no_inventaris: string, kode_barang: string, nama_barang: string, nama_ruang: string, qrSvg: string}
     */
    private function buildLabelPayload(Inventaris $inventaris, InventarisQrCodeGenerator $qrCodeGenerator): array
    {
        $namaBarang = $inventaris->barang?->nama_barang ?? $inventaris->kode_barang;
        if (mb_strlen($namaBarang) > 48) {
            $namaBarang = mb_substr($namaBarang, 0, 45).'...';
        }

        return [
            'no_inventaris' => $inventaris->no_inventaris,
            'kode_barang' => $inventaris->kode_barang,
            'nama_barang' => $namaBarang,
            'nama_ruang' => $inventaris->ruang?->nama_ruang ?: 'Ruang belum diisi',
            'qrSvg' => $qrCodeGenerator->svg(
                route('inventaris.show', $inventaris->no_inventaris, absolute: true),
                180
            ),
        ];
    }

    public function create(Request $request): Response
    {
        try {
            $barang = InventarisBarang::query()
                ->orderBy('nama_barang')
                ->limit(500)
                ->get(['kode_barang', 'nama_barang'])
                ->map(fn ($b) => ['kode_barang' => $b->kode_barang, 'nama_barang' => $b->nama_barang]);

            $ruang = InventarisRuang::query()
                ->orderBy('nama_ruang')
                ->get(['id_ruang', 'nama_ruang']);
        } catch (\Throwable $e) {
            $barang = collect();
            $ruang = collect();
        }

        return Inertia::render('inventaris/create', [
            'barang' => $barang,
            'ruang' => $ruang,
            'prefillKodeBarang' => $request->query('kode_barang'),
        ]);
    }

    public function store(StoreInventarisRequest $request): RedirectResponse
    {
        $v = $request->validated();

        Inventaris::query()->create([
            'no_inventaris' => $v['no_inventaris'],
            'kode_barang' => $v['kode_barang'],
            'asal_barang' => $v['asal_barang'] ?? null,
            'tgl_pengadaan' => isset($v['tgl_pengadaan']) ? $v['tgl_pengadaan'] : null,
            'harga' => $v['harga'] ?? null,
            'status_barang' => $v['status_barang'] ?? null,
            'id_ruang' => $v['id_ruang'] ?? null,
            'no_rak' => $v['no_rak'] ?? null,
            'no_box' => $v['no_box'] ?? null,
        ]);

        return redirect()
            ->route('inventaris.index')
            ->with('success', 'Inventaris berhasil ditambahkan.');
    }

    public function show(Inventaris $inventaris, InventarisPhotoResolver $photoResolver): Response
    {
        $inventaris->load(['barang', 'ruang', 'gambar']);

        $tickets = Ticket::query()
            ->with('status:id,name,color')
            ->where('asset_no_inventaris', $inventaris->no_inventaris)
            ->latest()
            ->limit(20)
            ->get(['id', 'ticket_number', 'title', 'ticket_status_id', 'created_at'])
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'status' => $ticket->status?->name,
                'status_color' => $ticket->status?->color,
                'created_at' => $ticket->created_at?->toDateString(),
            ]);

        return Inertia::render('inventaris/show', [
            'inventaris' => [
                'no_inventaris' => $inventaris->no_inventaris,
                'kode_barang' => $inventaris->kode_barang,
                'nama_barang' => $inventaris->barang?->nama_barang ?? $inventaris->kode_barang,
                'asal_barang' => $inventaris->asal_barang,
                'tgl_pengadaan' => $inventaris->tgl_pengadaan,
                'harga' => $inventaris->harga,
                'status_barang' => $inventaris->status_barang,
                'nama_ruang' => $inventaris->ruang?->nama_ruang ?? null,
                'no_rak' => $inventaris->no_rak,
                'no_box' => $inventaris->no_box,
                'photo' => $inventaris->gambar?->photo,
                'photo_url' => $photoResolver->toDataUri($inventaris->gambar?->photo),
            ],
            'tickets' => $tickets,
            'statusOptions' => ['Ada', 'Rusak', 'Hilang', 'Perbaikan', 'Dipinjam'],
        ]);
    }

    public function edit(Inventaris $inventaris, InventarisPhotoResolver $photoResolver): Response
    {
        $inventaris->load(['barang', 'ruang', 'gambar']);

        try {
            $barang = InventarisBarang::query()
                ->orderBy('nama_barang')
                ->limit(500)
                ->get(['kode_barang', 'nama_barang'])
                ->map(fn ($b) => ['kode_barang' => $b->kode_barang, 'nama_barang' => $b->nama_barang]);

            $ruang = InventarisRuang::query()
                ->orderBy('nama_ruang')
                ->get(['id_ruang', 'nama_ruang']);
        } catch (\Throwable $e) {
            $barang = collect();
            $ruang = collect();
        }

        return Inertia::render('inventaris/edit', [
            'inventaris' => [
                'no_inventaris' => $inventaris->no_inventaris,
                'kode_barang' => $inventaris->kode_barang,
                'nama_barang' => $inventaris->barang?->nama_barang ?? $inventaris->kode_barang,
                'asal_barang' => $inventaris->asal_barang,
                'tgl_pengadaan' => $inventaris->tgl_pengadaan,
                'harga' => $inventaris->harga,
                'status_barang' => $inventaris->status_barang,
                'id_ruang' => $inventaris->id_ruang,
                'nama_ruang' => $inventaris->ruang?->nama_ruang ?? null,
                'no_rak' => $inventaris->no_rak,
                'no_box' => $inventaris->no_box,
                'photo' => $inventaris->gambar?->photo,
                'photo_url' => $photoResolver->toDataUri($inventaris->gambar?->photo),
            ],
            'barang' => $barang,
            'ruang' => $ruang,
        ]);
    }

    public function update(UpdateInventarisRequest $request, Inventaris $inventaris): RedirectResponse
    {
        $inventaris->update($request->validated());

        return redirect()
            ->route('inventaris.show', $inventaris)
            ->with('success', 'Inventaris berhasil diperbarui.');
    }

    public function destroy(Inventaris $inventaris): RedirectResponse
    {
        $inventaris->delete();

        return redirect()
            ->route('inventaris.index')
            ->with('success', 'Inventaris berhasil dihapus.');
    }
}
