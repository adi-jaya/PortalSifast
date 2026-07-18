<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsetDokumenRequest;
use App\Models\Aset;
use App\Models\AsetDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsetDokumenController extends Controller
{
    public function store(StoreAsetDokumenRequest $request, Aset $aset): RedirectResponse
    {
        $file = $request->file('file');
        $lingkup = $request->validated('lingkup');
        $path = $file->store('aset/dokumen', 'public');

        $judul = trim((string) ($request->validated('judul') ?? ''));
        if ($judul === '') {
            $judul = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        AsetDokumen::query()->create([
            'judul' => $judul,
            'tipe' => $request->validated('tipe'),
            'lingkup' => $lingkup,
            'aset_id' => $lingkup === AsetDokumen::LINGKUP_UNIT ? $aset->id : null,
            'aset_barang_id' => $lingkup === AsetDokumen::LINGKUP_BARANG ? $aset->aset_barang_id : null,
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'ukuran' => $file->getSize() ?: 0,
            'diunggah_oleh' => $request->user()?->id,
        ]);

        $aset->riwayat()->create([
            'pengguna_id' => $request->user()?->id,
            'jenis_peristiwa' => 'dokumen_diunggah',
            'nilai_baru' => [
                'tipe' => $request->validated('tipe'),
                'lingkup' => $lingkup,
                'path' => $path,
            ],
            'created_at' => now(),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function unduh(Aset $aset, AsetDokumen $dokumen): StreamedResponse
    {
        $this->assertDokumenVisibleOnAset($aset, $dokumen);

        abort_unless(Storage::disk('public')->exists($dokumen->path), 404);

        return Storage::disk('public')->download(
            $dokumen->path,
            $dokumen->nama_asli,
        );
    }

    public function destroy(Aset $aset, AsetDokumen $dokumen): RedirectResponse
    {
        $this->assertDokumenVisibleOnAset($aset, $dokumen);

        if (Storage::disk('public')->exists($dokumen->path)) {
            Storage::disk('public')->delete($dokumen->path);
        }

        $dokumen->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }

    private function assertDokumenVisibleOnAset(Aset $aset, AsetDokumen $dokumen): void
    {
        $ok = match ($dokumen->lingkup) {
            AsetDokumen::LINGKUP_UNIT => $dokumen->aset_id === $aset->id,
            AsetDokumen::LINGKUP_BARANG => $aset->aset_barang_id !== null
                && $dokumen->aset_barang_id === $aset->aset_barang_id,
            default => false,
        };

        abort_unless($ok, 404);
    }
}
