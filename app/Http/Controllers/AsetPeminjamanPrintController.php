<?php

namespace App\Http\Controllers;

use App\Models\AsetPeminjaman;
use App\Models\Pegawai;
use Illuminate\View\View;

class AsetPeminjamanPrintController extends Controller
{
    public function __invoke(AsetPeminjaman $peminjaman): View
    {
        $peminjaman->load(['aset.barang', 'peminjamUser', 'diserahkanOleh', 'diterimaKembaliOleh']);

        $peminjamLabel = $peminjaman->peminjamUser?->name;
        if (! $peminjamLabel && $peminjaman->peminjam_nik) {
            $peminjamLabel = Pegawai::query()->where('nik', $peminjaman->peminjam_nik)->value('nama')
                ?? $peminjaman->peminjam_nik;
        }

        return view('aset.peminjaman-print', [
            'peminjaman' => $peminjaman,
            'peminjamLabel' => $peminjamLabel,
        ]);
    }
}
