<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRiwayatPenelitianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'judul_penelitian' => trim((string) $this->input('judul_penelitian', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul_penelitian' => ['required', 'string', 'max:60'],
            'tahun' => ['required', 'digits:4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul_penelitian.required' => 'Judul penelitian wajib diisi.',
            'tahun.required' => 'Tahun wajib diisi.',
        ];
    }
}
