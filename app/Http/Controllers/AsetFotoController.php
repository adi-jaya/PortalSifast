<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsetFotoRequest;
use App\Models\Aset;
use App\Models\AsetFoto;
use App\Services\InventarisPhotoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AsetFotoController extends Controller
{
    private const MISSING_PHOTO_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function __construct(private InventarisPhotoResolver $photoResolver) {}

    public function store(StoreAsetFotoRequest $request, Aset $aset): RedirectResponse
    {
        $path = $request->file('foto')->store('aset', 'public');

        AsetFoto::query()
            ->where('aset_id', $aset->id)
            ->where('utama', true)
            ->update(['utama' => false]);

        AsetFoto::query()->create([
            'aset_id' => $aset->id,
            'path' => $path,
            'utama' => true,
            'diunggah_oleh' => $request->user()?->id,
        ]);

        $aset->riwayat()->create([
            'pengguna_id' => $request->user()?->id,
            'jenis_peristiwa' => 'foto_diunggah',
            'nilai_baru' => ['path' => $path],
            'created_at' => now(),
        ]);

        return back()->with('success', 'Foto aset berhasil diunggah.');
    }

    public function destroy(Aset $aset, AsetFoto $foto): RedirectResponse
    {
        abort_unless($foto->aset_id === $aset->id, 404);

        if (Storage::disk('public')->exists($foto->path)) {
            Storage::disk('public')->delete($foto->path);
        }

        $foto->delete();

        return back()->with('success', 'Foto aset dihapus.');
    }

    public function showSumber(Aset $aset): Response|SymfonyResponse
    {
        $resolved = $this->photoResolver->resolve($aset->path_foto_sumber);

        if ($resolved === null) {
            return response(base64_decode(self::MISSING_PHOTO_PNG), 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, max-age=120',
                'X-Aset-Foto' => 'missing',
            ]);
        }

        return response($resolved['binary'], 200, [
            'Content-Type' => $resolved['mime'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Aset-Foto' => 'ok',
        ]);
    }
}
