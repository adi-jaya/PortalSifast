<?php

namespace App\Http\Controllers;

use App\Models\AsetMutasiLokasi;
use App\Models\Pegawai;
use Illuminate\View\View;

class AsetMutasiLokasiPrintController extends Controller
{
    public function __invoke(AsetMutasiLokasi $mutasi): View
    {
        $mutasi->load(['aset.barang', 'ruangAsal', 'ruangTujuan', 'penerimaUser', 'dicatatOleh']);

        $penerimaLabel = $mutasi->penerimaUser?->name;
        if (! $penerimaLabel && $mutasi->penerima_nik) {
            $penerimaLabel = Pegawai::query()->where('nik', $mutasi->penerima_nik)->value('nama')
                ?? $mutasi->penerima_nik;
        }

        return view('aset.mutasi-lokasi-print', [
            'mutasi' => $mutasi,
            'penerimaLabel' => $penerimaLabel,
        ]);
    }
}
