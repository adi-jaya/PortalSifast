<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAsetPenyusutanSettingsRequest;
use App\Services\Inventaris\PengaturanPenyusutanAset;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AsetPenyusutanSettingsController extends Controller
{
    public function edit(PengaturanPenyusutanAset $pengaturan): Response
    {
        return Inertia::render('aset/pengaturan-penyusutan', [
            'pengaturan' => $pengaturan->all(),
        ]);
    }

    public function update(
        UpdateAsetPenyusutanSettingsRequest $request,
        PengaturanPenyusutanAset $pengaturan,
    ): RedirectResponse {
        $pengaturan->update($request->validated());

        return redirect()
            ->route('aset.pengaturan-penyusutan.edit')
            ->with('success', 'Pengaturan penyusutan disimpan.');
    }
}
