<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickStoreAsetMasterRequest;
use App\Models\AsetDistributor;
use App\Models\AsetJenis;
use App\Models\AsetKategori;
use App\Models\AsetMerk;
use App\Models\AsetProdusen;
use App\Services\Inventaris\GeneratorKodeMasterAset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class AsetMasterController extends Controller
{
    public function store(QuickStoreAsetMasterRequest $request, string $tipe, GeneratorKodeMasterAset $generator): JsonResponse
    {
        $nama = trim($request->validated('nama'));

        [$model, $namaColumn, $kodeColumn] = $this->resolveMaster($tipe);

        /** @var Model|null $existing */
        $existing = $model::query()
            ->whereRaw('LOWER('.$namaColumn.') = ?', [mb_strtolower($nama)])
            ->first();

        if ($existing !== null) {
            return response()->json([
                'item' => $this->formatItem($existing, $namaColumn, $kodeColumn),
                'created' => false,
            ]);
        }

        $kode = $generator->generate($nama, new $model, $kodeColumn);

        /** @var Model $record */
        $record = $model::query()->create([
            $kodeColumn => $kode,
            $namaColumn => $nama,
        ]);

        return response()->json([
            'item' => $this->formatItem($record, $namaColumn, $kodeColumn),
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
     * @return array{id: int, kode: string, nama: string}
     */
    private function formatItem(Model $record, string $namaColumn, string $kodeColumn): array
    {
        return [
            'id' => (int) $record->getKey(),
            'kode' => (string) $record->getAttribute($kodeColumn),
            'nama' => (string) $record->getAttribute($namaColumn),
        ];
    }
}
