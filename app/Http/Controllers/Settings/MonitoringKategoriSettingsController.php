<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateMonitoringKategoriSettingsRequest;
use App\Models\AsetKategori;
use App\Services\Agent\PengaturanMonitorableKategori;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringKategoriSettingsController extends Controller
{
    public function edit(PengaturanMonitorableKategori $pengaturan): Response
    {
        $selected = $pengaturan->codes();

        $kategori = AsetKategori::query()
            ->whereNotNull('kode_kategori')
            ->where('kode_kategori', '!=', '')
            ->where('kode_kategori', '!=', '-')
            ->orderBy('kode_kategori')
            ->get(['id', 'kode_kategori', 'nama_kategori'])
            ->map(fn (AsetKategori $row): array => [
                'kode_kategori' => $row->kode_kategori,
                'nama_kategori' => $row->nama_kategori,
                'selected' => in_array($row->kode_kategori, $selected, true),
            ])
            ->values()
            ->all();

        return Inertia::render('monitoring/pengaturan-kategori', [
            'kategori' => $kategori,
            'selectedCodes' => $selected,
            'defaults' => $pengaturan->defaultsFromConfig(),
        ]);
    }

    public function update(
        UpdateMonitoringKategoriSettingsRequest $request,
        PengaturanMonitorableKategori $pengaturan,
    ): RedirectResponse {
        $pengaturan->update($request->validated('kategori_codes'));

        return redirect()
            ->route('monitoring.pengaturan-kategori.edit')
            ->with('success', 'Kategori perangkat yang boleh dimonitor disimpan.');
    }
}
