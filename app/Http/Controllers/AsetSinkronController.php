<?php

namespace App\Http\Controllers;

use App\Jobs\JalankanSinkronAsetJob;
use App\Models\AsetSinkron;
use App\Services\Inventaris\SinkronAsetDariSimrs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsetSinkronController extends Controller
{
    public function index(): Response
    {
        $runs = AsetSinkron::query()
            ->with('pemicu:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('aset/sinkron', [
            'runs' => $runs,
            'terakhir' => $runs->first(),
        ]);
    }

    public function preview(SinkronAsetDariSimrs $sinkron): RedirectResponse
    {
        $result = $sinkron->preview();

        return back()->with('sinkron_preview', $result);
    }

    public function apply(Request $request, SinkronAsetDariSimrs $sinkron): RedirectResponse
    {
        if ($request->boolean('queue')) {
            JalankanSinkronAsetJob::dispatch($request->user()?->id);

            return back()->with('success', 'Sinkron dimasukkan ke antrian.');
        }

        $result = $sinkron->apply($request->user()?->id);

        return back()->with('success', sprintf(
            'Sinkron selesai: %d baru, %d berubah, %d sama, %d hilang.',
            $result['jumlah_baru'],
            $result['jumlah_berubah'],
            $result['jumlah_sama'],
            $result['jumlah_hilang']
        ));
    }
}
