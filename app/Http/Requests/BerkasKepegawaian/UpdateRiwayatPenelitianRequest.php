<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiwayatPenelitianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jenis_penelitian' => trim((string) $this->input('jenis_penelitian', '')),
            'peranan' => trim((string) $this->input('peranan', '')),
            'judul_penelitian' => trim((string) $this->input('judul_penelitian', '')),
            'judul_jurnal' => trim((string) $this->input('judul_jurnal', '')),
            'asal_dana' => trim((string) $this->input('asal_dana', '')),
            'original_judul_penelitian' => trim((string) $this->input('original_judul_penelitian', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'original_judul_penelitian' => ['required', 'string', 'max:60'],
            'original_tahun' => ['required', 'digits:4'],
            'jenis_penelitian' => ['required', 'string', 'max:30'],
            'peranan' => ['required', 'string', 'max:30'],
            'judul_penelitian' => ['required', 'string', 'max:60'],
            'judul_jurnal' => ['required', 'string', 'max:60'],
            'tahun' => ['required', 'digits:4'],
            'biaya_penelitian' => ['nullable', 'numeric', 'min:0'],
            'asal_dana' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_judul_penelitian.required' => 'Identitas data asli wajib dikirim.',
            'original_tahun.required' => 'Identitas data asli wajib dikirim.',
            'jenis_penelitian.required' => 'Jenis penelitian wajib diisi.',
            'peranan.required' => 'Peranan wajib diisi.',
            'judul_penelitian.required' => 'Judul penelitian wajib diisi.',
            'judul_jurnal.required' => 'Judul jurnal wajib diisi.',
            'tahun.required' => 'Tahun wajib diisi.',
            'asal_dana.required' => 'Asal dana wajib diisi.',
        ];
    }
}
