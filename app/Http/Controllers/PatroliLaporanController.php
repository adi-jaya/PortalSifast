<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patroli\PatroliLaporanRequest;
use App\Services\Patroli\PatroliLaporanAggregator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatroliLaporanController extends Controller
{
    public function index(PatroliLaporanRequest $request, PatroliLaporanAggregator $aggregator): Response
    {
        $periode = (string) ($request->validated('periode') ?? 'bulanan');
        [$from, $to] = $aggregator->resolvePeriod(
            $periode,
            $request->validated('from'),
            $request->validated('to'),
        );

        $report = $aggregator->aggregate($from, $to);

        return Inertia::render('patroli/laporan', [
            'filters' => [
                'periode' => $periode,
                'from' => $request->validated('from'),
                'to' => $request->validated('to'),
            ],
            'report' => $report,
        ]);
    }

    public function export(PatroliLaporanRequest $request, PatroliLaporanAggregator $aggregator): StreamedResponse
    {
        $periode = (string) ($request->validated('periode') ?? 'bulanan');
        [$from, $to] = $aggregator->resolvePeriod(
            $periode,
            $request->validated('from'),
            $request->validated('to'),
        );

        $report = $aggregator->aggregate($from, $to);
        $filename = sprintf('patroli-laporan-%s-%s.csv', $report['from'], $report['to']);

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Jenis', 'Kunci', 'Berfungsi', 'Tidak Berfungsi', 'Tidak Dicek', 'Persen Berfungsi', 'Extra']);

            fputcsv($out, ['ringkas', 'total_checkin', $report['total_checkin'], '', '', '', "{$report['from']} s/d {$report['to']}"]);

            foreach ($report['per_item'] as $row) {
                fputcsv($out, [
                    'item',
                    $row['nama_item'],
                    $row['berfungsi'],
                    $row['tidak_berfungsi'],
                    $row['tidak_dicek'],
                    $row['persen_berfungsi'],
                    '',
                ]);
            }

            foreach ($report['per_area'] as $row) {
                fputcsv($out, [
                    'area',
                    $row['nama_area'] ?? '',
                    $row['total_checkin'],
                    $row['tidak_berfungsi'],
                    '',
                    '',
                    '',
                ]);
            }

            foreach ($report['per_ruang'] as $row) {
                fputcsv($out, [
                    'ruang',
                    ($row['nama_area'] ?? '').' / '.($row['nama_ruang'] ?? ''),
                    $row['total_checkin'],
                    $row['tidak_berfungsi'],
                    '',
                    '',
                    $row['kode'] ?? '',
                ]);
            }

            foreach ($report['per_template'] as $row) {
                fputcsv($out, [
                    'template',
                    $row['nama'] ?? '',
                    $row['total_checkin'],
                    '',
                    '',
                    '',
                    '',
                ]);
            }

            foreach ($report['temuan'] as $row) {
                $extra = trim(
                    ($row['nama_area'] ?? '').' / '.($row['kode'] ?? '').' '.($row['nama_ruang'] ?? '').' @ '.($row['checked_at'] ?? '')
                );
                fputcsv($out, [
                    'temuan',
                    $row['nama_item'],
                    '',
                    1,
                    '',
                    '',
                    $extra,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
