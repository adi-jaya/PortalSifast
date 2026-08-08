<?php

namespace App\Http\Controllers\Tatanaskah;

use App\Http\Controllers\Controller;
use App\Models\Dokumen;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PegawaiSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Dokumen::class);

        $q = $request->string('q')->trim()->toString();

        $query = Pegawai::query()->where('stts_aktif', 'AKTIF');

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%")
                    ->orWhere('jbtn', 'like', "%{$q}%");
            });
        } else {
            $query->orderByRaw("CASE WHEN jbtn LIKE '%irektur%' THEN 0 WHEN jbtn LIKE '%Kabid%' OR jbtn LIKE '%Kabag%' THEN 1 ELSE 2 END");
        }

        $pegawai = $query
            ->orderBy('nama')
            ->limit(25)
            ->get(['nik', 'nama', 'jbtn', 'departemen']);

        return response()->json(
            $pegawai->map(fn (Pegawai $p) => [
                'nik' => $p->nik,
                'nama' => $p->nama,
                'jbtn' => $p->jbtn,
                'departemen' => $p->departemen,
                'label' => trim($p->nama.($p->jbtn ? " — {$p->jbtn}" : '')),
            ]),
        );
    }
}
