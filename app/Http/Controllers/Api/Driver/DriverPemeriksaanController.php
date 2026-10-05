<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverPemeriksaanApiRequest;
use App\Models\DriverKendaraan;
use App\Models\DriverPemeriksaan;
use App\Models\DriverPemeriksaanDetail;
use App\Services\Driver\BatalkanPemeriksaanDriver;
use App\Services\Driver\BuatPemeriksaanDriver;
use App\Support\DriverPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverPemeriksaanController extends Controller
{
    public function __construct(private DriverPetugasResolver $petugasResolver) {}

    public function index(Request $request): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $tanggal = $request->query('tanggal');
        $kendaraanId = $request->query('kendaraan_id');
        $mine = filter_var($request->query('mine', true), FILTER_VALIDATE_BOOLEAN);

        $authUser = $request->user();
        $canSeeAll = $authUser?->canCreateDriverPemeriksaan()
            && ! $authUser->isPayrollServiceIntegrationAccount();

        $rows = DriverPemeriksaan::query()
            ->with(['kendaraan:id,nama,no_polisi', 'petugas:id,name,simrs_nik'])
            ->withCount([
                'details as temuan_count' => fn ($q) => $q->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK),
            ])
            ->when($mine || ! $canSeeAll, fn ($q) => $q->where('petugas_id', $petugas->id))
            ->when(filled($tanggal), fn ($q) => $q->whereDate('tanggal', $tanggal))
            ->when(filled($kendaraanId) && is_numeric($kendaraanId), fn ($q) => $q->where('driver_kendaraan_id', (int) $kendaraanId))
            ->latest('waktu_pemeriksaan')
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        $rows->getCollection()->transform(fn (DriverPemeriksaan $row) => [
            'id' => $row->id,
            'tanggal' => $row->tanggal->toDateString(),
            'waktu_pemeriksaan' => $row->waktu_pemeriksaan->toIso8601String(),
            'pemeriksaan_ke' => $row->pemeriksaan_ke,
            'status' => $row->status,
            'temuan_count' => (int) $row->temuan_count,
            'kendaraan' => [
                'id' => $row->driver_kendaraan_id,
                'nama' => $row->kendaraan?->nama,
                'no_polisi' => $row->kendaraan?->no_polisi,
            ],
            'petugas' => [
                'id' => $row->petugas_id,
                'name' => $row->petugas?->name,
                'simrs_nik' => $row->petugas?->simrs_nik,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function store(StoreDriverPemeriksaanApiRequest $request, BuatPemeriksaanDriver $service): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $validated = $request->validated();
        $kendaraan = DriverKendaraan::query()->findOrFail($validated['driver_kendaraan_id']);

        try {
            $pemeriksaan = $service->handle(
                $kendaraan,
                $petugas,
                $validated['items'],
                $validated['catatan'] ?? null,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Validasi gagal.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pemeriksaan berhasil disimpan.',
            'data' => $this->serializeDetail($pemeriksaan),
        ], 201);
    }

    public function show(Request $request, DriverPemeriksaan $pemeriksaan): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $pemeriksaan->load(['kendaraan', 'petugas', 'details.checklistItem']);

        $authUser = $request->user();
        $canSeeAll = $authUser?->canCreateDriverPemeriksaan()
            && ! $authUser->isPayrollServiceIntegrationAccount();

        if (! $canSeeAll && $pemeriksaan->petugas_id !== $petugas->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pemeriksaan ini bukan milik NIK yang diminta.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeDetail($pemeriksaan),
        ]);
    }

    public function destroy(Request $request, DriverPemeriksaan $pemeriksaan, BatalkanPemeriksaanDriver $service): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        try {
            $updated = $service->handle($pemeriksaan, $petugas);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first() ?? 'Tidak dapat membatalkan.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pemeriksaan dibatalkan.',
            'data' => $this->serializeDetail($updated),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(DriverPemeriksaan $pemeriksaan): array
    {
        $pemeriksaan->loadMissing(['kendaraan', 'petugas', 'details.checklistItem']);

        return [
            'id' => $pemeriksaan->id,
            'tanggal' => $pemeriksaan->tanggal->toDateString(),
            'waktu_pemeriksaan' => $pemeriksaan->waktu_pemeriksaan->toIso8601String(),
            'pemeriksaan_ke' => $pemeriksaan->pemeriksaan_ke,
            'status' => $pemeriksaan->status,
            'catatan' => $pemeriksaan->catatan,
            'kendaraan' => [
                'id' => $pemeriksaan->driver_kendaraan_id,
                'nama' => $pemeriksaan->kendaraan?->nama,
                'no_polisi' => $pemeriksaan->kendaraan?->no_polisi,
            ],
            'petugas' => [
                'id' => $pemeriksaan->petugas_id,
                'name' => $pemeriksaan->petugas?->name,
                'simrs_nik' => $pemeriksaan->petugas?->simrs_nik,
            ],
            'details' => $pemeriksaan->details
                ->sortBy(fn (DriverPemeriksaanDetail $d) => $d->checklistItem?->urutan ?? 0)
                ->values()
                ->map(fn (DriverPemeriksaanDetail $d) => [
                    'id' => $d->id,
                    'driver_checklist_item_id' => $d->driver_checklist_item_id,
                    'nama_item' => $d->checklistItem?->nama,
                    'hasil' => $d->hasil,
                    'hasil_label' => match ($d->hasil) {
                        DriverPemeriksaanDetail::HASIL_LAYAK => 'Baik',
                        DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK => 'Tidak Baik',
                        DriverPemeriksaanDetail::HASIL_NA => 'Tidak berlaku',
                        default => $d->hasil,
                    },
                    'temuan' => $d->temuan,
                    'rekomendasi' => $d->rekomendasi,
                    'keterangan' => $d->keterangan,
                ]),
        ];
    }
}
