<?php

namespace App\Http\Requests\Patroli;

use App\Models\PatroliCheckinItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatroliCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->canAccessPatroli() || $user->isPayrollServiceIntegrationAccount();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patroli_ruang_id' => ['required', 'integer', 'exists:patroli_ruang,id'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.patroli_template_item_id' => ['required', 'integer', 'exists:patroli_template_item,id'],
            'items.*.status' => ['required', 'string', Rule::in(PatroliCheckinItem::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patroli_ruang_id.required' => 'Ruang patroli wajib dipilih.',
            'items.required' => 'Checklist wajib diisi.',
            'items.*.status.in' => 'Status item tidak valid.',
        ];
    }
}
