<?php

namespace App\Http\Controllers\Api\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patroli\StorePatroliCheckinRequest;
use App\Models\PatroliCheckin;
use App\Models\PatroliCheckinItem;
use App\Models\PatroliRuang;
use App\Services\Patroli\BuatPatroliCheckin;
use App\Support\PatroliPetugasResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatroliCheckinController extends Controller
{
    public function __construct(private PatroliPetugasResolver $petugasResolver) {}

    public function index(Request $request): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $q = trim((string) $request->query('q', ''));
        $temuan = (string) $request->query('temuan', 'all');
        $mine = filter_var($request->query('mine', true), FILTER_VALIDATE_BOOLEAN);

        $rows = PatroliCheckin::query()
            ->with(['ruang.area', 'user', 'template', 'items'])
            ->when($mine, fn ($query) => $query->where('user_id', $petugas->id))
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('ruang', function ($ruang) use ($search) {
                        $ruang->where('kode', 'like', $search)
                            ->orWhere('nama', 'like', $search)
                            ->orWhereHas('area', fn ($area) => $area->where('nama', 'like', $search));
                    })->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search));
                });
            })
            ->when($temuan === 'ya', function ($query) {
                $query->whereHas('items', fn ($item) => $item->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI));
            })
            ->orderByDesc('checked_at')
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        $rows->getCollection()->transform(fn (PatroliCheckin $row) => [
            'id' => $row->id,
            'checked_at' => $row->checked_at?->toIso8601String(),
            'catatan' => $row->catatan,
            'ruang' => [
                'id' => $row->patroli_ruang_id,
                'kode' => $row->ruang?->kode,
                'nama' => $row->ruang?->nama,
                'area' => [
                    'id' => $row->ruang?->area?->id,
                    'nama' => $row->ruang?->area?->nama,
                ],
            ],
            'petugas' => [
                'id' => $row->user_id,
                'name' => $row->user?->name,
                'simrs_nik' => $row->user?->simrs_nik,
            ],
            'template' => $row->template?->nama,
            'jumlah_item' => $row->items->count(),
            'jumlah_tidak_berfungsi' => $row->items->where('status', PatroliCheckinItem::STATUS_TIDAK_BERFUNGSI)->count(),
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

    public function store(StorePatroliCheckinRequest $request, BuatPatroliCheckin $service): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $validated = $request->validated();
        $ruang = PatroliRuang::query()->findOrFail($validated['patroli_ruang_id']);

        try {
            $checkin = $service->handle(
                ruang: $ruang,
                actor: $petugas,
                items: $validated['items'],
                catatan: $validated['catatan'] ?? null,
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
            'message' => 'Check-in patroli tersimpan.',
            'data' => $this->serializeDetail($checkin),
        ], 201);
    }

    public function show(Request $request, PatroliCheckin $checkin): JsonResponse
    {
        [$petugas, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $checkin->load(['ruang.area', 'user', 'template', 'items']);

        $authUser = $request->user();
        $canSeeAll = $authUser?->canAccessPatroli() && ! $authUser->isPayrollServiceIntegrationAccount();
        if (! $canSeeAll && $checkin->user_id !== $petugas->id) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in ini bukan milik NIK yang diminta.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeDetail($checkin),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(PatroliCheckin $checkin): array
    {
        return [
            'id' => $checkin->id,
            'checked_at' => $checkin->checked_at?->toIso8601String(),
            'catatan' => $checkin->catatan,
            'ruang' => [
                'id' => $checkin->patroli_ruang_id,
                'kode' => $checkin->ruang?->kode,
                'nama' => $checkin->ruang?->nama,
                'area' => [
                    'id' => $checkin->ruang?->area?->id,
                    'nama' => $checkin->ruang?->area?->nama,
                ],
            ],
            'petugas' => [
                'id' => $checkin->user_id,
                'name' => $checkin->user?->name,
                'simrs_nik' => $checkin->user?->simrs_nik,
            ],
            'template' => [
                'id' => $checkin->patroli_template_id,
                'nama' => $checkin->template?->nama,
            ],
            'items' => $checkin->items->map(fn (PatroliCheckinItem $item) => [
                'id' => $item->id,
                'patroli_template_item_id' => $item->patroli_template_item_id,
                'nama_item' => $item->nama_item,
                'status' => $item->status,
            ])->values(),
        ];
    }
}
