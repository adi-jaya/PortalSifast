<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAsetUnitCsvRequest;
use App\Services\Inventaris\ImportAsetUnitCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsetImportController extends Controller
{
    public function create(Request $request, ImportAsetUnitCsv $importer): InertiaResponse
    {
        $preview = $request->session()->get(ImportAsetUnitCsv::SESSION_KEY);

        return Inertia::render('aset/import', [
            'templateUrl' => route('aset.import.template'),
            'hints' => $importer->importHints(),
            'preview' => $preview === null ? null : [
                'token' => $preview['token'],
                'error_count' => $preview['error_count'],
                'ok_count' => $preview['ok_count'],
                'rows' => $preview['rows'],
            ],
        ]);
    }

    public function template(ImportAsetUnitCsv $importer): StreamedResponse
    {
        $headers = $importer->headers();
        $examples = $importer->exampleRows();

        return Response::streamDownload(function () use ($headers, $examples) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($examples as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'template-import-aset-unit.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function preview(ImportAsetUnitCsvRequest $request, ImportAsetUnitCsv $importer): RedirectResponse
    {
        $result = $importer->preview($request->file('file'));

        $request->session()->put(ImportAsetUnitCsv::SESSION_KEY, $result);

        return redirect()
            ->route('aset.import')
            ->with(
                $result['error_count'] > 0 ? 'error' : 'success',
                $result['error_count'] > 0
                    ? "Preview selesai: {$result['error_count']} baris error, {$result['ok_count']} OK."
                    : "Preview OK: {$result['ok_count']} baris siap diimpor.",
            );
    }

    public function store(Request $request, ImportAsetUnitCsv $importer): RedirectResponse
    {
        $preview = $request->session()->get(ImportAsetUnitCsv::SESSION_KEY);
        $token = (string) $request->input('token', '');

        if (! is_array($preview) || $token === '' || ($preview['token'] ?? null) !== $token) {
            return redirect()
                ->route('aset.import')
                ->with('error', 'Preview tidak ditemukan atau sudah kedaluwarsa. Upload ulang CSV.');
        }

        $created = $importer->commit($preview, $request->user());
        $request->session()->forget(ImportAsetUnitCsv::SESSION_KEY);

        $count = count($created);
        if ($count === 0) {
            return redirect()
                ->route('aset.import')
                ->with('error', 'Tidak ada aset yang dibuat.');
        }

        if ($count === 1) {
            return redirect()
                ->route('aset.show', $created[0])
                ->with('success', 'Import berhasil: '.$created[0]->kode_aset);
        }

        if ($count <= 20) {
            return redirect()
                ->route('aset.created', [
                    'ids' => collect($created)->pluck('id')->implode(','),
                ])
                ->with('success', "{$count} aset berhasil diimpor dari CSV.");
        }

        return redirect()
            ->route('aset.index')
            ->with('success', "{$count} aset berhasil diimpor dari CSV.");
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(ImportAsetUnitCsv::SESSION_KEY);

        return redirect()->route('aset.import');
    }
}
