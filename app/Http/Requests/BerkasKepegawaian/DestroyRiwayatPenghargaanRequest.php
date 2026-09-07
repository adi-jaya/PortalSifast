<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class DestroyRiwayatPenghargaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_penghargaan' => trim((string) $this->input('nama_penghargaan', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_penghargaan' => ['required', 'string', 'max:60'],
            'tanggal' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_penghargaan.required' => 'Nama penghargaan wajib diisi.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ];
    }
}
