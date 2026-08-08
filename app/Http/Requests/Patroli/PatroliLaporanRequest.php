<?php

namespace App\Http\Requests\Patroli;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatroliLaporanRequest extends FormRequest
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
            'periode' => ['nullable', 'string', Rule::in(['mingguan', 'bulanan', '3bulanan', 'tahunan', 'custom'])],
            'from' => ['nullable', 'date', 'required_if:periode,custom'],
            'to' => ['nullable', 'date', 'required_if:periode,custom', 'after_or_equal:from'],
        ];
    }
}
