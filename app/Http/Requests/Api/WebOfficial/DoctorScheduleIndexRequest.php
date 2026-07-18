<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;

class DoctorScheduleIndexRequest extends FormRequest
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
            'tanggal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'hari' => ['nullable', 'string', 'max:20'],
            'kd_poli' => ['nullable', 'string', 'max:20'],
            'poli' => ['nullable', 'string', 'min:2', 'max:100'],
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'semua_hari' => ['nullable', 'boolean'],
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

        if ($this->has('semua_hari')) {
            $this->merge([
                'semua_hari' => $this->boolean('semua_hari'),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
        ];
    }
}
