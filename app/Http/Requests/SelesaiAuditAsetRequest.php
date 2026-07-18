<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelesaiAuditAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['nullable', 'string', 'max:2000'],
            'paksa' => ['sometimes', 'boolean'],
        ];
    }
}
