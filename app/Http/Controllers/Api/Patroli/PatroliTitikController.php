<?php

namespace App\Http\Controllers\Api\Patroli;

use App\Http\Controllers\Controller;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Support\PatroliPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatroliTitikController extends Controller
{
    public function __construct(private PatroliPetugasResolver $petugasResolver) {}

    public function index(Request $request): JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }
        $q = trim((string) $request->query('q', ''));
        $onlyAssigned = filter_var($request->query('assigned', true), FILTER_VALIDATE_BOOLEAN);

        $rows = PatroliRuang::query()
            ->with([
                'area',
                'patroliTemplate' => fn ($query) => $query->withCount('activeItems'),
            ])
            ->where('is_active', true)
            ->when($onlyAssigned, fn ($query) => $query->whereNotNull('patroli_template_id'))
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->where('kode', 'like', $search)
                        ->orWhere('nama', 'like', $search)
                        ->orWhereHas('area', fn ($area) => $area->where('nama', 'like', $search));
                });
            })
            ->orderBy('nama')
            ->paginate((int) $request->query('per_page', 50))
            ->withQueryString();

        $rows->getCollection()->transform(fn (PatroliRuang $ruang) => $this->serializeTitik($ruang));

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

    public function scanForm(Request $request, PatroliRuang $ruang): JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        return $this->buildScanResponse($ruang);
    }

    public function scanByKode(Request $request, string $kodeRuang): JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $ruang = PatroliRuang::query()->where('kode', $kodeRuang)->first();

        if ($ruang === null) {
            return response()->json([
                'success' => false,
                'message' => 'Kode ruang patroli tidak ditemukan.',
            ], 404);
        }

        return $this->buildScanResponse($ruang);
    }

    public function resolveQr(Request $request): JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $payload = trim((string) $request->input('qr_payload', ''));

        if ($payload === '') {
            return response()->json([
                'success' => false,
                'message' => 'qr_payload wajib diisi.',
            ], 422);
        }

        if (preg_match('#/patroli/scan/(\d+)#', $payload, $matches) === 1) {
            $ruang = PatroliRuang::query()->find((int) $matches[1]);
            if ($ruang === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titik patroli dari QR tidak ditemukan.',
                ], 404);
            }

            return $this->buildScanResponse($ruang);
        }

        $ruang = PatroliRuang::query()->where('kode', $payload)->first();
        if ($ruang === null) {
            return response()->json([
                'success' => false,
                'message' => 'QR tidak dikenali. Kirim URL scan atau kode ruang patroli.',
            ], 422);
        }

        return $this->buildScanResponse($ruang);
    }

    private function buildScanResponse(PatroliRuang $ruang): JsonResponse
    {
        $ruang->load(['patroliTemplate.activeItems', 'area']);

        if (! $ruang->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang patroli ini tidak aktif.',
            ], 422);
        }

        if ($ruang->patroliTemplate === null || ! $ruang->patroliTemplate->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang ini belum memiliki template patroli aktif.',
            ], 422);
        }

        $items = $ruang->patroliTemplate->activeItems;
        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Template patroli tidak memiliki item aktif.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ruang' => [
                    'id' => $ruang->id,
                    'kode' => $ruang->kode,
                    'nama' => $ruang->nama,
                    'area' => [
                        'id' => $ruang->area?->id,
                        'nama' => $ruang->area?->nama,
                    ],
                ],
                'template' => [
                    'id' => $ruang->patroliTemplate->id,
                    'nama' => $ruang->patroliTemplate->nama,
                ],
                'items' => $items->map(fn ($item) => [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'urutan' => $item->urutan,
                    'default_status' => PatroliCheckinItem::STATUS_BERFUNGSI,
                ])->values(),
                'statuses' => PatroliCheckinItem::STATUSES,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTitik(PatroliRuang $ruang): array
    {
        return [
            'id' => $ruang->id,
            'kode' => $ruang->kode,
            'nama' => $ruang->nama,
            'patroli_template_id' => $ruang->patroli_template_id,
            'template_nama' => $ruang->patroliTemplate?->nama,
            'jumlah_item_aktif' => $ruang->patroliTemplate?->active_items_count ?? 0,
            'area' => [
                'id' => $ruang->area?->id,
                'nama' => $ruang->area?->nama,
            ],
            'scan_path' => '/patroli/scan/'.$ruang->id,
            'scan_url' => route('patroli.scan', $ruang, absolute: true),
        ];
    }
}
