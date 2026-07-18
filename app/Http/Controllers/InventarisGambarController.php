<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventarisGambarRequest;
use App\Models\Inventaris;
use App\Models\InventarisGambar;
use App\Services\InventarisPhotoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InventarisGambarController extends Controller
{
    /** 1×1 transparent PNG — triggers frontend empty-state via naturalWidth check. */
    private const MISSING_PHOTO_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function __construct(private InventarisPhotoResolver $photoResolver) {}

    public function show(Inventaris $inventaris): Response|SymfonyResponse
    {
        $inventaris->loadMissing('gambar');
        $resolved = $this->photoResolver->resolve($inventaris->gambar?->photo);

        if ($resolved === null) {
            return response(base64_decode(self::MISSING_PHOTO_PNG), 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, max-age=120',
                'X-Inventaris-Photo' => 'missing',
            ]);
        }

        return response($resolved['binary'], 200, [
            'Content-Type' => $resolved['mime'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Inventaris-Photo' => 'ok',
        ]);
    }

    public function store(StoreInventarisGambarRequest $request, Inventaris $inventaris): RedirectResponse
    {
        $path = $request->file('photo')->store('inventaris', 'public');

        $existing = InventarisGambar::query()->find($inventaris->no_inventaris);

        if ($existing?->photo && $this->photoResolver->isLocalPortalUpload($existing->photo)) {
            Storage::disk('public')->delete($existing->photo);
        }

        InventarisGambar::query()->updateOrCreate(
            ['no_inventaris' => $inventaris->no_inventaris],
            ['photo' => $path]
        );

        return redirect()
            ->route('inventaris.show', $inventaris)
            ->with('success', 'Foto inventaris berhasil disimpan.');
    }

    public function destroy(Inventaris $inventaris): RedirectResponse
    {
        $gambar = InventarisGambar::query()->find($inventaris->no_inventaris);

        if ($gambar) {
            if ($gambar->photo && $this->photoResolver->isLocalPortalUpload($gambar->photo)) {
                Storage::disk('public')->delete($gambar->photo);
            }

            $gambar->delete();
        }

        return redirect()
            ->route('inventaris.show', $inventaris)
            ->with('success', 'Foto inventaris berhasil dihapus.');
    }
}
