<?php

namespace App\Http\Controllers\WebOfficial;

use App\Http\Controllers\Controller;
use App\Services\Instagram\InstagramFeedService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialInstagramWebController extends Controller
{
    public function index(InstagramFeedService $instagramFeedService): Response
    {
        return Inertia::render('web-official/instagram/index', [
            'status' => $instagramFeedService->status(),
            'posts' => $instagramFeedService->listForPublic(12),
            'apiEndpoint' => url('/api/instagram-feed'),
        ]);
    }

    public function sync(InstagramFeedService $instagramFeedService): RedirectResponse
    {
        $result = $instagramFeedService->syncFromApi();

        if ($result['success']) {
            return redirect()
                ->route('web-official.instagram.index')
                ->with('success', sprintf('Sinkronisasi Instagram berhasil. %d postingan diperbarui.', $result['count']));
        }

        return redirect()
            ->route('web-official.instagram.index')
            ->with('error', $result['message'] ?? 'Sinkronisasi Instagram gagal.');
    }
}
