<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAsetJenisMerkRequest;
use App\Models\AsetJenis;
use App\Models\AsetMerk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetJenisController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $merkId = $request->query('merk_id');
        $onlyUnassigned = $request->boolean('only_unassigned');

        $items = AsetJenis::query()
            ->with('merk:id,nama_merk')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nama_jenis', 'like', "%{$q}%")
                        ->orWhere('kode_jenis', 'like', "%{$q}%");
                });
            })
            ->when(
                filled($merkId) && is_numeric($merkId),
                fn ($query) => $query->where('aset_merk_id', (int) $merkId),
            )
            ->when($onlyUnassigned, fn ($query) => $query->whereNull('aset_merk_id'))
            ->orderBy('nama_jenis')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AsetJenis $item) => [
                'id' => $item->id,
                'kode_jenis' => $item->kode_jenis,
                'nama_jenis' => $item->nama_jenis,
                'aset_merk_id' => $item->aset_merk_id,
                'merk_nama' => $item->merk?->nama_merk,
            ]);

        $total = AsetJenis::query()->count();
        $unassigned = AsetJenis::query()->whereNull('aset_merk_id')->count();

        return Inertia::render('aset/master-jenis/index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'merk_id' => filled($merkId) ? (string) $merkId : '',
                'only_unassigned' => $onlyUnassigned,
            ],
            'stats' => [
                'total' => $total,
                'unassigned' => $unassigned,
                'assigned' => $total - $unassigned,
            ],
            'merkOptions' => AsetMerk::query()
                ->orderBy('nama_merk')
                ->get(['id', 'nama_merk'])
                ->map(fn (AsetMerk $m) => [
                    'id' => $m->id,
                    'nama' => $m->nama_merk,
                ]),
        ]);
    }

    public function updateMerk(UpdateAsetJenisMerkRequest $request, AsetJenis $jenis): RedirectResponse
    {
        $jenis->update([
            'aset_merk_id' => $request->validated('aset_merk_id'),
        ]);

        return back()->with('success', 'Merk jenis diperbarui.');
    }
}
