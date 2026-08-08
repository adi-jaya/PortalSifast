<?php

namespace App\Http\Controllers\Api\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patroli\PatroliLaporanRequest;
use App\Services\Patroli\PatroliLaporanAggregator;
use App\Support\PatroliPetugasResolver;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatroliLaporanController extends Controller
{
    public function __construct(private PatroliPetugasResolver $petugasResolver) {}

    public function index(PatroliLaporanRequest $request, PatroliLaporanAggregator $aggregator): JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }

        $periode = (string) ($request->validated('periode') ?? 'bulanan');
        [$from, $to] = $aggregator->resolvePeriod(
            $periode,
            $request->validated('from'),
            $request->validated('to'),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => $periode,
                'report' => $aggregator->aggregate($from, $to),
            ],
        ]);
    }

    public function export(PatroliLaporanRequest $request, PatroliLaporanAggregator $aggregator): StreamedResponse|JsonResponse
    {
        [, $error] = $this->petugasResolver->resolve($request);
        if ($error !== null) {
            return $error;
        }
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

            foreach ($report['temuan'] as $row) {
                $extra = trim(
                    ($row['nama_area'] ?? '').' / '.($row['kode'] ?? '').' '.($row['nama_ruang'] ?? '').' @ '.($row['checked_at'] ?? '')
                );
                fputcsv($out, ['temuan', $row['nama_item'], '', 1, '', '', $extra]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
