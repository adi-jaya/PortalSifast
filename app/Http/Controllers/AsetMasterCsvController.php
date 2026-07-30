<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAsetMasterCsvRequest;
use App\Services\Inventaris\AsetMasterCsv;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsetMasterCsvController extends Controller
{
    public function template(string $tipe, AsetMasterCsv $csv): StreamedResponse
    {
        return $csv->templateDownload($tipe);
    }

    public function export(string $tipe, AsetMasterCsv $csv): StreamedResponse
    {
        return $csv->exportDownload($tipe);
    }

    public function import(string $tipe, ImportAsetMasterCsvRequest $request, AsetMasterCsv $csv): RedirectResponse
    {
        $count = $csv->import($tipe, $request->file('file'));

        $redirect = match ($tipe) {
            'ruang' => route('aset.master.ruang.index'),
            'aspak' => route('aset.master.aspak.index'),
            'non_alkes' => route('aset.master.non-alkes.index'),
            default => route('aset.index'),
        };

        return redirect()
            ->to($redirect)
            ->with('success', "Import {$tipe} selesai: {$count} baris.");
    }
}
