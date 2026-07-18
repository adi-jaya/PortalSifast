<?php

namespace App\Services\Tatanaskah;

use App\Enums\DokumenStatus;
use App\Models\AuditDokumen;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DokumenWorkflowService
{
    public function __construct(
        private DocumentNumberService $numberService,
        private HijriConverterService $hijriService,
        private StirlingPdfService $stirlingService,
        private DocumentStorageService $storageService,
        private DocumentSigningService $signingService,
    ) {}

    public function transition(
        Dokumen $dokumen,
        DokumenStatus $target,
        User $actor,
        ?string $catatan = null,
        ?Request $request = null,
    ): Dokumen {
        $current = $dokumen->status;

        if (! $current->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Transisi dari {$current->value} ke {$target->value} tidak diizinkan.",
            );
        }

        return DB::transaction(function () use ($dokumen, $current, $target, $actor, $catatan, $request): Dokumen {
            if ($target === DokumenStatus::MenungguTte && $dokumen->nomor_dokumen === null) {
                $this->assignNomor($dokumen);
            }

            if ($target === DokumenStatus::Aktif && $dokumen->status === DokumenStatus::MenungguTte) {
                $this->signingService->signOnApproval($dokumen, $actor);
                $dokumen->tanggal_berlaku ??= now()->toDateString();
                $dokumen->disetujui_oleh = $actor->id;
            }

            $dokumen->update(['status' => $target]);

            AuditDokumen::query()->create([
                'dokumen_id' => $dokumen->id,
                'user_id' => $actor->id,
                'aksi' => 'status_changed',
                'status_lama' => $current->value,
                'status_baru' => $target->value,
                'catatan' => $catatan,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str($request->userAgent())->limit(300)->toString() : null,
                'created_at' => now(),
            ]);

            return $dokumen->fresh([
                'jenis', 'unitKlasifikasi', 'sifat', 'pembuat', 'versiSaatIni', 'metaRegulasi', 'audit.user',
            ]);
        });
    }

    private function assignNomor(Dokumen $dokumen): void
    {
        $dokumen->loadMissing(['jenis', 'unitKlasifikasi', 'sifat', 'versiSaatIni']);

        $tanggal = now();
        $nomor = $this->numberService->generate(
            $dokumen->jenis,
            $dokumen->unitKlasifikasi,
            $dokumen->sifat,
            $tanggal,
        );

        $dokumen->tanggal_ditetapkan = $tanggal->toDateString();
        $dokumen->tanggal_hijriyah = $this->hijriService->formatFromGregorian($tanggal);
        $dokumen->nomor_dokumen = $nomor;

        $versi = $dokumen->versiSaatIni;
        if ($versi !== null && $versi->file_asli !== null) {
            $absolute = \Illuminate\Support\Facades\Storage::disk('local')->path($versi->file_asli);
            if (is_file($absolute)) {
                $stampText = implode("\n", array_filter([
                    $nomor,
                    $dokumen->tanggal_hijriyah,
                    $tanggal->translatedFormat('d F Y').' M',
                ]));
                $bernomor = $this->stirlingService->addTextStamp($absolute, $stampText, [
                    'pageNumbers' => '1',
                    'xPercent' => $dokumen->jenis->posisi_x ?? 60,
                    'yPercent' => $dokumen->jenis->posisi_y ?? 15,
                    'fontSize' => $dokumen->jenis->ukuran_font ?? 11,
                ]);
                if ($bernomor !== $absolute && ! str_starts_with($bernomor, '/')) {
                    $versi->update(['file_bernomor' => $bernomor]);
                }
            }
        }

        $dokumen->save();
    }
}
