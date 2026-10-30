<?php

namespace App\Http\Controllers\BerkasKepegawaian;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasKepegawaian\ConfirmBerkasScanInboxRequest;
use App\Models\BerkasScanInbox;
use App\Services\BerkasKepegawaian\BerkasScanInboxService;
use App\Services\BerkasKepegawaian\KepegawaianReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BerkasScanInboxController extends Controller
{
    public function __construct(
        private BerkasScanInboxService $service,
        private KepegawaianReadService $readService,
    ) {}

    public function index(): Response
    {
        $items = BerkasScanInbox::query()
            ->whereIn('status', [
                BerkasScanInbox::STATUS_PENDING,
                BerkasScanInbox::STATUS_FAILED,
            ])
            ->latest('id')
            ->get()
            ->map(fn (BerkasScanInbox $item) => [
                'id' => $item->id,
                'original_filename' => $item->original_filename,
                'suggested_kode' => $item->suggested_kode,
                'suggested_label' => $item->suggested_label,
                'confidence' => $item->confidence,
                'ocr_excerpt' => $item->ocr_excerpt,
                'ocr_failed' => $item->ocr_failed,
                'status' => $item->status,
                'agent_label' => $item->agent_label,
                'error_message' => $item->error_message,
                'preview_url' => route('berkas-kepegawaian.inbox.preview', $item),
                'created_at' => $item->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('berkas-kepegawaian/inbox', [
            'items' => $items,
            'masterOptions' => $this->readService->masterBerkasOptions(),
            'pegawaiOptions' => $this->readService->aktifPegawaiOptions(),
        ]);
    }

    public function preview(BerkasScanInbox $inbox): StreamedResponse
    {
        abort_unless($inbox->isActionable(), 404);
        abort_unless(
            $inbox->stored_path !== '' && Storage::disk('local')->exists($inbox->stored_path),
            404,
        );

        return Storage::disk('local')->response(
            $inbox->stored_path,
            $inbox->original_filename,
            [
                'Content-Disposition' => 'inline; filename="'.$inbox->original_filename.'"',
            ],
        );
    }

    public function confirm(ConfirmBerkasScanInboxRequest $request, BerkasScanInbox $inbox): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->service->confirm(
                $inbox,
                $data['nik'],
                $data['kode_berkas'],
                $data['tgl_uploud'],
                $request->user(),
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Scan dikonfirmasi dan diunggah ke Khanza.');
    }

    public function reject(BerkasScanInbox $inbox): RedirectResponse
    {
        abort_unless(auth()->user()?->canAccessBerkasKepegawaian() ?? false, 403);

        try {
            $this->service->reject($inbox, auth()->user());
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Scan ditolak dan dihapus dari inbox.');
    }
}
