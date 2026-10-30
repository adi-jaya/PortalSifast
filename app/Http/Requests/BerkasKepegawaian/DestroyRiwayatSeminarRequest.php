<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRiwayatSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_seminar' => trim((string) $this->input('nama_seminar', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_seminar' => ['required', 'string', 'max:50'],
            'mulai' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_seminar.required' => 'Nama seminar wajib diisi.',
            'mulai.required' => 'Tanggal mulai wajib diisi.',
        ];
    }
}
