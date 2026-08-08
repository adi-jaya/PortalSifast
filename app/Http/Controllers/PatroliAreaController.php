<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patroli\StorePatroliAreaRequest;
use App\Http\Requests\Patroli\StorePatroliRuangRequest;
use App\Http\Requests\Patroli\UpdatePatroliAreaRequest;
use App\Http\Requests\Patroli\UpdatePatroliRuangRequest;
use App\Models\AsetRuang;
use App\Models\PatroliArea;
use App\Models\PatroliRuang;
use App\Models\PatroliTemplate;
use App\Services\InventarisQrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class PatroliAreaController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $rows = PatroliArea::query()
            ->withCount('ruang')
            ->when($q !== '', function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where(function ($inner) use ($search) {
                    $inner->where('nama', 'like', $search)
                        ->orWhere('deskripsi', 'like', $search);
                });
            })
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('patroli/area/index', [
            'rows' => $rows,
            'filters' => ['q' => $q],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('patroli/area/create');
    }

    public function store(StorePatroliAreaRequest $request): RedirectResponse
    {
        $area = PatroliArea::query()->create([
            'nama' => $request->validated('nama'),
            'deskripsi' => $request->validated('deskripsi'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('patroli.area.show', $area)
            ->with('success', 'Area patroli dibuat.');
    }

    public function show(PatroliArea $area): Response
    {
        $area->load(['ruang.patroliTemplate']);

        $templates = PatroliTemplate::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $asetRuangOptions = AsetRuang::query()
            ->orderBy('kode_ruang')
            ->get(['id', 'kode_ruang', 'nama_ruang'])
            ->map(fn (AsetRuang $row) => [
                'value' => (string) $row->id,
                'label' => $row->nama_ruang,
                'description' => $row->kode_ruang,
                'kode' => $row->kode_ruang,
                'nama' => $row->nama_ruang,
            ])
            ->values();

        return Inertia::render('patroli/area/show', [
            'area' => [
                'id' => $area->id,
                'nama' => $area->nama,
                'deskripsi' => $area->deskripsi,
                'is_active' => $area->is_active,
            ],
            'ruang' => $area->ruang->map(fn (PatroliRuang $ruang) => [
                'id' => $ruang->id,
                'kode' => $ruang->kode,
                'nama' => $ruang->nama,
                'is_active' => $ruang->is_active,
                'patroli_template_id' => $ruang->patroli_template_id,
                'template_nama' => $ruang->patroliTemplate?->nama,
                'scan_url' => route('patroli.scan', $ruang),
                'label_url' => route('patroli.area.ruang.label', [$area, $ruang]),
            ])->values(),
            'templates' => $templates,
            'asetRuangOptions' => $asetRuangOptions,
        ]);
    }

    public function update(UpdatePatroliAreaRequest $request, PatroliArea $area): RedirectResponse
    {
        $area->update([
            'nama' => $request->validated('nama'),
            'deskripsi' => $request->validated('deskripsi'),
            'is_active' => $request->boolean('is_active', $area->is_active),
        ]);

        return redirect()
            ->route('patroli.area.show', $area)
            ->with('success', 'Area patroli diperbarui.');
    }

    public function storeRuang(StorePatroliRuangRequest $request, PatroliArea $area): RedirectResponse
    {
        $area->ruang()->create([
            'nama' => $request->validated('nama'),
            'kode' => $request->validated('kode'),
            'patroli_template_id' => $request->validated('patroli_template_id'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('patroli.area.show', $area)
            ->with('success', 'Ruang patroli ditambahkan.');
    }

    public function updateRuang(UpdatePatroliRuangRequest $request, PatroliArea $area, PatroliRuang $ruang): RedirectResponse
    {
        abort_unless($ruang->patroli_area_id === $area->id, 404);

        $ruang->update([
            'nama' => $request->validated('nama'),
            'kode' => $request->validated('kode'),
            'patroli_template_id' => $request->validated('patroli_template_id'),
            'is_active' => $request->boolean('is_active', $ruang->is_active),
        ]);

        return redirect()
            ->route('patroli.area.show', $area)
            ->with('success', 'Ruang patroli diperbarui.');
    }

    public function destroyRuang(PatroliArea $area, PatroliRuang $ruang): RedirectResponse
    {
        abort_unless($ruang->patroli_area_id === $area->id, 404);

        $ruang->delete();

        return redirect()
            ->route('patroli.area.show', $area)
            ->with('success', 'Ruang patroli dihapus.');
    }

    public function labelPrint(PatroliArea $area, PatroliRuang $ruang, InventarisQrCodeGenerator $qrCodeGenerator): View
    {
        abort_unless($ruang->patroli_area_id === $area->id, 404);

        $scanUrl = route('patroli.scan', $ruang, absolute: true);

        return view('patroli.label-print', [
            'title' => 'Label QR Patroli',
            'area' => $area,
            'ruang' => $ruang,
            'scanUrl' => $scanUrl,
            'qrSvg' => $qrCodeGenerator->svg($scanUrl, 180),
        ]);
    }
}
