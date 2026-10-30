<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRiwayatSuratPeringatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_peringatan' => trim((string) $this->input('nama_peringatan', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_peringatan' => ['required', 'string', 'max:60'],
            'tanggal' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_peringatan.required' => 'Nama peringatan wajib diisi.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ];
    }
}
