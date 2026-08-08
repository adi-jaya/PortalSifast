<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\WebOfficial\ExternalRssFeedService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialExternalRssWebController extends Controller
{
    public function index(ExternalRssFeedService $externalRssFeedService): Response
    {
        return Inertia::render('web-official/berita-eksternal/index', [
            'status' => $externalRssFeedService->status(),
            'items' => $externalRssFeedService->listForPublic(12),
            'apiEndpoint' => url('/api/berita-eksternal'),
        ]);
    }

    public function sync(ExternalRssFeedService $externalRssFeedService): RedirectResponse
    {
        $result = $externalRssFeedService->syncFromFeeds();

        if ($result['success']) {
            return redirect()
                ->route('web-official.berita-eksternal.index')
                ->with('success', sprintf('Sinkronisasi RSS berhasil. %d berita diperbarui.', $result['count']));
        }

        return redirect()
            ->route('web-official.berita-eksternal.index')
            ->with('error', $result['message'] ?? 'Sinkronisasi RSS gagal.');
    }
}
