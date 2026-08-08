<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickStoreAsetMasterRequest;
use App\Models\AsetAspakAlat;
use App\Models\AsetDistributor;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetNonAlkes;
use App\Models\AsetProdusen;
use App\Services\Inventaris\GeneratorKodeMasterAset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsetMasterController extends Controller
{
    public function searchNonAlkes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetNonAlkes::query()
            ->leaf()
            ->search($q)
            ->with(['parent.parent.parent'])
            ->orderBy('nama_alat')
            ->limit(40)
            ->get(['id', 'id_alat', 'nama_alat', 'kode', 'alat_code', 'level', 'sinonim', 'parent_id', 'aset_kategori_id']);

        return response()->json(
            $items->map(fn (AsetNonAlkes $item) => [
                'id' => $item->id,
                'id_alat' => $item->id_alat,
                'nama_alat' => $item->nama_alat,
                'kode' => $item->kode ?: $item->alat_code,
                'level' => $item->level,
                'aset_kategori_id' => $item->resolvedKategoriId(),
                'label' => trim(($item->nama_alat).' ('.($item->kode ?: $item->alat_code ?: $item->id_alat).')'),
            ])->values(),
        );
    }

    public function searchAspak(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $items = AsetAspakAlat::query()
            ->leaf()
            ->search($q)
            ->orderBy('nama_alat')
            ->limit(40)
            ->get([
                'id',
                'id_alat_aspak',
                'nama_alat',
                'kode',
                'alat_code',
                'sinonim',
                'wajib_kalibrasi',
                'durasi_kalibrasi_hari',
            ]);

        return response()->json(
            $items->map(fn (AsetAspakAlat $item) => [
                'id' => $item->id,
                'id_alat_aspak' => $item->id_alat_aspak,
                'nama_alat' => $item->nama_alat,
                'kode' => $item->kode ?: $item->alat_code,
                'wajib_kalibrasi' => (bool) $item->wajib_kalibrasi,
                'durasi_kalibrasi_hari' => $item->durasi_kalibrasi_hari,
                'label' => trim(($item->nama_alat).' ('.($item->kode ?: $item->alat_code ?: $item->id_alat_aspak).')'),
            ])->values(),
        );
    }

    public function store(QuickStoreAsetMasterRequest $request, string $tipe, GeneratorKodeMasterAset $generator): JsonResponse
    {
        $nama = trim($request->validated('nama'));
        $merkId = $request->validated('aset_merk_id') ?? null;

        [$model, $namaColumn, $kodeColumn] = $this->resolveMaster($tipe);

        /** @var Model|null $existing */
        $existingQuery = $model::query()->whereRaw('LOWER('.$namaColumn.') = ?', [mb_strtolower($nama)]);
        if ($tipe === 'jenis' && filled($merkId)) {
            $existingQuery->where(function ($q) use ($merkId) {
                $q->where('aset_merk_id', $merkId)->orWhereNull('aset_merk_id');
            });
        }
        $existing = $existingQuery->first();

        if ($existing !== null) {
            if ($tipe === 'jenis' && filled($merkId) && $existing->getAttribute('aset_merk_id') === null) {
                $existing->update(['aset_merk_id' => $merkId]);
            }

            return response()->json([
                'item' => $this->formatItem($existing->fresh(), $namaColumn, $kodeColumn, $tipe),
                'created' => false,
            ]);
        }

        $kode = $generator->generate($nama, new $model, $kodeColumn);

        $payload = [
            $kodeColumn => $kode,
            $namaColumn => $nama,
        ];
        if ($tipe === 'jenis' && filled($merkId)) {
            $payload['aset_merk_id'] = $merkId;
        }

        /** @var Model $record */
        $record = $model::query()->create($payload);

        return response()->json([
            'item' => $this->formatItem($record, $namaColumn, $kodeColumn, $tipe),
            'created' => true,
        ], 201);
    }

    /**
     * @return array{0: class-string<Model>, 1: string, 2: string}
     */
    private function resolveMaster(string $tipe): array
    {
        return match ($tipe) {
            'kategori' => [AsetKategori::class, 'nama_kategori', 'kode_kategori'],
            'jenis' => [AsetJenis::class, 'nama_jenis', 'kode_jenis'],
            'merk' => [AsetMerk::class, 'nama_merk', 'kode_merk'],
            'produsen' => [AsetProdusen::class, 'nama_produsen', 'kode_produsen'],
            'distributor' => [AsetDistributor::class, 'nama_distributor', 'kode_distributor'],
            default => abort(404),
        };
    }

    /**
     * @return array{id: int, kode: string, nama: string, aset_merk_id?: int|null}
     */
    private function formatItem(Model $record, string $namaColumn, string $kodeColumn, string $tipe = ''): array
    {
        $item = [
            'id' => (int) $record->getKey(),
            'kode' => (string) $record->getAttribute($kodeColumn),
            'nama' => (string) $record->getAttribute($namaColumn),
        ];

        if ($tipe === 'jenis') {
            $item['aset_merk_id'] = $record->getAttribute('aset_merk_id');
        }

        return $item;
    }
}
