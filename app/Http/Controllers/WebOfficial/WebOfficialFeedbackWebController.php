<?php

namespace App\Http\Controllers\WebOfficial;

use App\Enums\WebOfficialFeedbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\UpdateWebOfficialFeedbackWebRequest;
use App\Models\WebOfficialFeedback;
use App\Support\WebOfficialFeedbackServiceUnits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialFeedbackWebController extends Controller
{
    public function index(Request $request): Response
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

        $feedbacks = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/kritik-saran/index', [
            'feedbacks' => $feedbacks->through(fn (WebOfficialFeedback $feedback): array => [
                'id' => $feedback->id,
                'full_name' => $feedback->full_name,
                'phone' => $feedback->phone,
                'service_unit' => $feedback->service_unit,
                'rating' => $feedback->rating,
                'message' => $feedback->message,
                'status' => $feedback->status->value,
                'created_at' => $feedback->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'service_unit' => $request->string('service_unit')->toString(),
                'rating' => $request->input('rating'),
            ],
            'statusOptions' => collect(WebOfficialFeedbackStatus::cases())->map(fn (WebOfficialFeedbackStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
            'serviceUnitOptions' => WebOfficialFeedbackServiceUnits::all(),
        ]);
    }

    public function show(WebOfficialFeedback $kritik_saran): Response
    {
        return Inertia::render('web-official/kritik-saran/show', [
            'feedback' => [
                'id' => $kritik_saran->id,
                'full_name' => $kritik_saran->full_name,
                'phone' => $kritik_saran->phone,
                'service_unit' => $kritik_saran->service_unit,
                'rating' => $kritik_saran->rating,
                'message' => $kritik_saran->message,
                'status' => $kritik_saran->status->value,
                'source' => $kritik_saran->source,
                'ip_address' => $kritik_saran->ip_address,
                'user_agent' => $kritik_saran->user_agent,
                'admin_notes' => $kritik_saran->admin_notes,
                'created_at' => $kritik_saran->created_at?->toIso8601String(),
                'updated_at' => $kritik_saran->updated_at?->toIso8601String(),
                'resolved_at' => $kritik_saran->resolved_at?->toIso8601String(),
            ],
            'statusOptions' => collect(WebOfficialFeedbackStatus::cases())->map(fn (WebOfficialFeedbackStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
        ]);
    }

    public function update(
        UpdateWebOfficialFeedbackWebRequest $request,
        WebOfficialFeedback $kritik_saran,
    ): RedirectResponse {
        $validated = $request->validated();

        if (array_key_exists('status', $validated)) {
            $status = WebOfficialFeedbackStatus::from($validated['status']);
            $kritik_saran->status = $status;

            if ($status === WebOfficialFeedbackStatus::Resolved) {
                $kritik_saran->resolved_at = now();
            } else {
                $kritik_saran->resolved_at = null;
            }
        }

        if (array_key_exists('admin_notes', $validated)) {
            $kritik_saran->admin_notes = $validated['admin_notes'];
        }

        $kritik_saran->save();

        return redirect()
            ->route('web-official.kritik-saran.show', $kritik_saran)
            ->with('success', 'Pengaduan berhasil diperbarui.');
    }

    public function destroy(WebOfficialFeedback $kritik_saran): RedirectResponse
    {
        $kritik_saran->status = WebOfficialFeedbackStatus::Archived;
        $kritik_saran->save();

        return redirect()
            ->route('web-official.kritik-saran.index')
            ->with('success', 'Pengaduan berhasil diarsipkan.');
    }
}
