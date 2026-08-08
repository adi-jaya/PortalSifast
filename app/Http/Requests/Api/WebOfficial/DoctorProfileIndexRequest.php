<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;

class DoctorProfileIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'kd_poli' => ['nullable', 'string', 'max:20'],
            'poli' => ['nullable', 'string', 'min:2', 'max:100'],
            'with_foto' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('with_foto')) {
            $this->merge([
                'with_foto' => $this->boolean('with_foto'),
            ]);
        }
    }
}
