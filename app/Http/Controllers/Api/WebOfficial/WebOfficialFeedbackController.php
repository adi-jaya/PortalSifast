<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\StoreWebOfficialFeedbackRequest;
use App\Models\WebOfficialFeedback;
use App\Support\WebOfficialFeedbackServiceUnits;
use Illuminate\Http\JsonResponse;
use Throwable;

class WebOfficialFeedbackController extends Controller
{
    public function store(StoreWebOfficialFeedbackRequest $request): JsonResponse
    {
        try {
            $feedback = WebOfficialFeedback::query()->create($request->submissionAttributes());

            return response()->json([
                'success' => true,
                'data' => $feedback->toPublicResponseArray(),
                'message' => 'Kritik & saran berhasil diterima.',
            ], 201);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
            ], 500);
        }
    }

    public function serviceUnits(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WebOfficialFeedbackServiceUnits::all(),
        ]);
    }
}
