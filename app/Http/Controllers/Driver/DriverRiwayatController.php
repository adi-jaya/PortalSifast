<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverKendaraan;
use App\Models\DriverPemeriksaan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverRiwayatController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tanggal = $request->query('tanggal');
        $kendaraanId = $request->query('kendaraan_id');
        $status = $request->query('status');

        $query = DriverPemeriksaan::query()
            ->with(['kendaraan:id,nama,no_polisi', 'petugas:id,name'])
            ->withCount([
                'details as temuan_count' => fn ($q) => $q->where('hasil', 'tidak_layak'),
            ])
            ->latest('waktu_pemeriksaan');

        if (filled($tanggal)) {
            $query->whereDate('tanggal', $tanggal);
        }

        if (filled($kendaraanId) && is_numeric($kendaraanId)) {
            $query->where('driver_kendaraan_id', (int) $kendaraanId);
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        $items = $query->paginate(20)->withQueryString()->through(fn (DriverPemeriksaan $p) => [
            'id' => $p->id,
            'tanggal' => $p->tanggal->toDateString(),
            'waktu' => $p->waktu_pemeriksaan->format('H:i'),
            'pemeriksaan_ke' => $p->pemeriksaan_ke,
            'status' => $p->status,
            'temuan_count' => (int) $p->temuan_count,
            'kendaraan' => $p->kendaraan?->nama,
            'petugas' => $p->petugas?->name,
        ]);

        return Inertia::render('driver/riwayat', [
            'items' => $items,
            'filters' => [
                'tanggal' => $tanggal ?? '',
                'kendaraan_id' => filled($kendaraanId) ? (string) $kendaraanId : '',
                'status' => $status ?? '',
            ],
            'kendaraanOptions' => DriverKendaraan::query()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn (DriverKendaraan $k) => [
                    'id' => $k->id,
                    'nama' => $k->nama,
                ]),
        ]);
    }
}
