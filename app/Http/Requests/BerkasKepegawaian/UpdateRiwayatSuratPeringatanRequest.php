<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiwayatSuratPeringatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jenis' => trim((string) $this->input('jenis', '')),
            'nama_peringatan' => trim((string) $this->input('nama_peringatan', '')),
            'original_nama_peringatan' => trim((string) $this->input('original_nama_peringatan', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'original_nama_peringatan' => ['required', 'string', 'max:60'],
            'original_tanggal' => ['required', 'date'],
            'jenis' => ['required', 'string', 'max:30'],
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
            'original_nama_peringatan.required' => 'Identitas data asli wajib dikirim.',
            'original_tanggal.required' => 'Identitas data asli wajib dikirim.',
            'jenis.required' => 'Jenis wajib diisi.',
            'nama_peringatan.required' => 'Nama peringatan wajib diisi.',
            'tanggal.required' => 'Tanggal wajib diisi.',
        ];
    }
}
