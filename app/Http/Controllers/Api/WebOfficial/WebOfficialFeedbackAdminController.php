<?php

namespace App\Http\Controllers\Api\WebOfficial;

use App\Enums\WebOfficialFeedbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WebOfficial\UpdateWebOfficialFeedbackRequest;
use App\Models\WebOfficialFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebOfficialFeedbackAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WebOfficialFeedback::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('service_unit')) {
            $query->where('service_unit', $request->string('service_unit')->toString());
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('full_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('message', 'like', $search);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map->toAdminListArray()->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(WebOfficialFeedback $feedback): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $feedback->toAdminDetailArray(),
        ]);
    }

    public function partialUpdate(
        UpdateWebOfficialFeedbackRequest $request,
        WebOfficialFeedback $feedback,
    ): JsonResponse {
        $validated = $request->validated();

        if (array_key_exists('status', $validated)) {
            $status = WebOfficialFeedbackStatus::from($validated['status']);
            $feedback->status = $status;

            if ($status === WebOfficialFeedbackStatus::Resolved) {
                $feedback->resolved_at = now();
            } else {
                $feedback->resolved_at = null;
            }
        }

        if (array_key_exists('adminNotes', $validated)) {
            $feedback->admin_notes = $validated['adminNotes'];
        }

        $feedback->save();

        return response()->json([
            'success' => true,
            'data' => $feedback->fresh()->toAdminDetailArray(),
        ]);
    }

    public function destroy(WebOfficialFeedback $feedback): JsonResponse
    {
        $feedback->status = WebOfficialFeedbackStatus::Archived;
        $feedback->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $feedback->id,
                'deleted' => true,
            ],
        ]);
    }
}
