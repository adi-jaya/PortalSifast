<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Data\Simrs\DoctorScheduleFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\DoctorScheduleIndexRequest;
use App\Services\Simrs\DoctorScheduleService;
use App\Support\SimrsDayName;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class DoctorScheduleController extends Controller
{
    public function __invoke(
        DoctorScheduleIndexRequest $request,
        DoctorScheduleService $scheduleService,
    ): JsonResponse {
        try {
            $filters = $this->buildFilters($request);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_DAY',
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }

        $schedules = $scheduleService->list($filters);

        $effectiveDate = null;
        if ($filters->hasDayFilter() && $filters->simrsDay !== null) {
            if ($request->filled('tanggal')) {
                $effectiveDate = $request->string('tanggal')->toString();
            } else {
                $effectiveDate = $this->resolveReferenceDate($request)->toDateString();
            }
        }

        return response()->json([
            'success' => true,
            'meta' => [
                'tanggal' => $effectiveDate,
                'hari' => $filters->simrsDay !== null ? SimrsDayName::label($filters->simrsDay) : null,
                'hariKey' => $filters->simrsDay,
                'semuaHari' => $filters->allDays,
                'kdPoli' => $filters->kdPoli,
                'poli' => $filters->poli,
                'q' => $filters->q,
                'total' => count($schedules),
            ],
            'data' => $schedules,
        ]);
    }

    private function resolveReferenceDate(DoctorScheduleIndexRequest $request): Carbon
    {
        if ($request->filled('tanggal')) {
            return Carbon::createFromFormat('Y-m-d', $request->string('tanggal')->toString())->startOfDay();
        }

        return Carbon::now()->startOfDay();
    }

    public function poliklinik(DoctorScheduleService $scheduleService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $scheduleService->listPoliklinik(),
        ]);
    }

    private function buildFilters(DoctorScheduleIndexRequest $request): DoctorScheduleFilters
    {
        $allDays = $request->boolean('semua_hari');
        $hasSearchOnly = $request->filled('q') || $request->filled('poli') || $request->filled('kd_poli');
        $simrsDay = null;

        if (! $allDays) {
            if ($request->filled('hari')) {
                $simrsDay = SimrsDayName::normalize($request->string('hari')->toString());
            } elseif ($request->filled('tanggal')) {
                $date = Carbon::createFromFormat('Y-m-d', $request->string('tanggal')->toString())->startOfDay();
                $simrsDay = SimrsDayName::fromDate($date);
            } elseif (! $hasSearchOnly) {
                $simrsDay = SimrsDayName::fromDate(Carbon::now()->startOfDay());
            }
        }

        return new DoctorScheduleFilters(
            simrsDay: $simrsDay,
            kdPoli: $request->filled('kd_poli') ? $request->string('kd_poli')->toString() : null,
            poli: $request->filled('poli') ? $request->string('poli')->toString() : null,
            q: $request->filled('q') ? $request->string('q')->toString() : null,
            withFoto: $request->boolean('with_foto', false),
            allDays: $allDays || ($simrsDay === null && $hasSearchOnly),
        );
    }
}
