<?php

namespace App\Http\Requests\Tatanaskah;

use App\Enums\DokumenStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DokumenStatus::class)],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
