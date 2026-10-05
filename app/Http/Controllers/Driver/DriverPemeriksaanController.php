<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverPemeriksaanBatchRequest;
use App\Http\Requests\Driver\StoreDriverPemeriksaanRequest;
use App\Models\DriverChecklistItem;
use App\Models\DriverKendaraan;
use App\Models\DriverKendaraanItem;
use App\Models\DriverPemeriksaan;
use App\Services\Driver\BatalkanPemeriksaanDriver;
use App\Services\Driver\BuatPemeriksaanDriver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DriverPemeriksaanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->canCreateDriverPemeriksaan(), 403);

        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);

        $kendaraan = DriverKendaraan::query()
            ->where('status', DriverKendaraan::STATUS_AKTIF)
            ->orderBy('nama')
            ->get(['id', 'nama', 'no_polisi', 'merk', 'model']);

        $todayCounts = DriverPemeriksaan::query()
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->selectRaw('driver_kendaraan_id, count(*) as total')
            ->groupBy('driver_kendaraan_id')
            ->pluck('total', 'driver_kendaraan_id');

        $items = DriverChecklistItem::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get(['id', 'nama', 'kategori', 'urutan']);

        $pivots = DriverKendaraanItem::query()
            ->whereIn('driver_kendaraan_id', $kendaraan->pluck('id'))
            ->get()
            ->groupBy('driver_kendaraan_id');

        $matrixKendaraan = $kendaraan
            ->filter(fn (DriverKendaraan $k) => (int) ($todayCounts[$k->id] ?? 0) < $maxPerDay)
            ->values()
            ->map(function (DriverKendaraan $k) use ($todayCounts, $items, $pivots) {
                $kendaraanPivots = $pivots->get($k->id, collect());
                $pivotByItem = $kendaraanPivots->keyBy('driver_checklist_item_id');
                $hasPivotConfig = $kendaraanPivots->isNotEmpty();

                return [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'no_polisi' => $k->no_polisi,
                    'merk' => $k->merk,
                    'model' => $k->model,
                    'foto_url' => $k->fotoUrl(),
                    'jumlah_hari_ini' => (int) ($todayCounts[$k->id] ?? 0),
                    'pemeriksaan_ke' => (int) ($todayCounts[$k->id] ?? 0) + 1,
                    'cells' => $items->map(function (DriverChecklistItem $item) use ($pivotByItem, $hasPivotConfig) {
                        $berlaku = $hasPivotConfig
                            ? (bool) ($pivotByItem->get($item->id)?->berlaku ?? false)
                            : true;

                        return [
                            'driver_checklist_item_id' => $item->id,
                            'berlaku' => $berlaku,
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('driver/pemeriksaan/index', [
            'items' => $items->map(fn (DriverChecklistItem $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori,
                'urutan' => $item->urutan,
            ]),
            'kendaraan' => $matrixKendaraan,
            'tanggal' => now()->translatedFormat('d F Y'),
            'waktu' => now()->format('H:i'),
            'petugas' => $request->user()?->name,
            'maxPerDay' => $maxPerDay,
        ]);
    }

    public function create(Request $request, DriverKendaraan $kendaraan): Response
    {
        abort_unless($request->user()?->canCreateDriverPemeriksaan(), 403);
        abort_unless($kendaraan->isAktif(), 404);

        $existingCount = DriverPemeriksaan::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', DriverPemeriksaan::STATUS_SELESAI)
            ->count();

        $maxPerDay = (int) config('driver.max_inspection_per_day', 2);
        abort_if($existingCount >= $maxPerDay, 403, 'Pemeriksaan hari ini sudah mencapai batas maksimal.');

        $pivots = DriverKendaraanItem::query()
            ->where('driver_kendaraan_id', $kendaraan->id)
            ->get()
            ->keyBy('driver_checklist_item_id');

        $items = DriverChecklistItem::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get()
            ->map(function (DriverChecklistItem $item) use ($pivots) {
                $pivot = $pivots->get($item->id);
                $berlaku = $pivots->isEmpty() ? true : (bool) ($pivot?->berlaku ?? false);

                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kategori' => $item->kategori,
                    'urutan' => $item->urutan,
                    'berlaku' => $berlaku,
                ];
            })
            ->values();

        return Inertia::render('driver/pemeriksaan/create', [
            'kendaraan' => [
                'id' => $kendaraan->id,
                'nama' => $kendaraan->nama,
                'no_polisi' => $kendaraan->no_polisi,
            ],
            'pemeriksaan_ke' => $existingCount + 1,
            'tanggal' => now()->translatedFormat('d F Y'),
            'waktu' => now()->format('H:i'),
            'petugas' => $request->user()?->name,
            'items' => $items,
        ]);
    }

    public function store(StoreDriverPemeriksaanRequest $request, BuatPemeriksaanDriver $service): RedirectResponse
    {
        $kendaraan = DriverKendaraan::query()->findOrFail($request->validated('driver_kendaraan_id'));

        $pemeriksaan = $service->handle(
            $kendaraan,
            $request->user(),
            $request->validated('items'),
            $request->validated('catatan'),
        );

        return redirect()
            ->route('driver.pemeriksaan.show', $pemeriksaan)
            ->with('success', 'Pemeriksaan berhasil disimpan.');
    }

    public function storeBatch(StoreDriverPemeriksaanBatchRequest $request, BuatPemeriksaanDriver $service): RedirectResponse
    {
        $rows = $request->validated('pemeriksaan');

        $created = DB::transaction(function () use ($rows, $request, $service) {
            $hasil = [];

            foreach ($rows as $row) {
                $kendaraan = DriverKendaraan::query()->findOrFail($row['driver_kendaraan_id']);
                $hasil[] = $service->handle(
                    $kendaraan,
                    $request->user(),
                    $row['items'],
                    $row['catatan'] ?? null,
                );
            }

            return $hasil;
        });

        $count = count($created);

        return redirect()
            ->route('driver.dashboard')
            ->with('success', "{$count} pemeriksaan berhasil disimpan.");
    }

    public function show(Request $request, DriverPemeriksaan $pemeriksaan): Response
    {
        abort_unless($request->user()?->canAccessDriverModule(), 403);

        $pemeriksaan->load(['kendaraan', 'petugas', 'details.checklistItem']);

        return Inertia::render('driver/pemeriksaan/show', [
            'pemeriksaan' => [
                'id' => $pemeriksaan->id,
                'tanggal' => $pemeriksaan->tanggal->toDateString(),
                'waktu' => $pemeriksaan->waktu_pemeriksaan->format('H:i'),
                'pemeriksaan_ke' => $pemeriksaan->pemeriksaan_ke,
                'status' => $pemeriksaan->status,
                'catatan' => $pemeriksaan->catatan,
                'kendaraan' => [
                    'id' => $pemeriksaan->kendaraan->id,
                    'nama' => $pemeriksaan->kendaraan->nama,
                    'no_polisi' => $pemeriksaan->kendaraan->no_polisi,
                ],
                'petugas' => $pemeriksaan->petugas?->name,
                'details' => $pemeriksaan->details
                    ->sortBy(fn ($d) => $d->checklistItem?->urutan ?? 0)
                    ->values()
                    ->map(fn ($d) => [
                        'id' => $d->id,
                        'nama_item' => $d->checklistItem?->nama,
                        'hasil' => $d->hasil,
                        'temuan' => $d->temuan,
                        'rekomendasi' => $d->rekomendasi,
                        'keterangan' => $d->keterangan,
                    ]),
            ],
            'canCancel' => $pemeriksaan->isSelesai() && (
                $request->user()?->canManageDriverMaster()
                || $request->user()?->canCoordinateChecklistKendaraan()
                || $pemeriksaan->petugas_id === $request->user()?->id
            ),
        ]);
    }

    public function destroy(Request $request, DriverPemeriksaan $pemeriksaan, BatalkanPemeriksaanDriver $service): RedirectResponse
    {
        abort_unless($request->user()?->canAccessDriverModule(), 403);

        $service->handle($pemeriksaan, $request->user());

        return redirect()
            ->route('driver.pemeriksaan.show', $pemeriksaan)
            ->with('success', 'Pemeriksaan dibatalkan.');
    }
}
