<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasKepegawaian\StoreBerkasScanInboxRequest;
use App\Models\MasterBerkasPegawai;
use App\Services\BerkasKepegawaian\BerkasScanInboxService;
use Illuminate\Http\JsonResponse;
use Throwable;

class BerkasScanInboxController extends Controller
{
    public function __construct(private BerkasScanInboxService $service) {}

    public function store(StoreBerkasScanInboxRequest $request): JsonResponse
    {
        try {
            $item = $this->service->storeFromAgent(
                $request->file('dokumen'),
                $request->validated(),
            );
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'id' => $item->id,
            'message' => 'Scan diterima di inbox.',
        ], 201);
    }

    public function jenis(): JsonResponse
    {
        $data = MasterBerkasPegawai::query()
            ->orderBy('no_urut')
            ->orderBy('nama_berkas')
            ->get(['kode', 'nama_berkas', 'kategori', 'no_urut'])
            ->map(fn (MasterBerkasPegawai $row) => [
                'kode' => $row->kode,
                'nama_berkas' => $row->nama_berkas,
                'kategori' => $row->kategori,
                'no_urut' => (int) $row->no_urut,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
