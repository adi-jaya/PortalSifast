<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiwayatPenghargaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jenis' => trim((string) $this->input('jenis', '')),
            'nama_penghargaan' => trim((string) $this->input('nama_penghargaan', '')),
            'instansi' => trim((string) $this->input('instansi', '')),
            'pejabat_pemberi' => trim((string) $this->input('pejabat_pemberi', '')),
            'original_nama_penghargaan' => trim((string) $this->input('original_nama_penghargaan', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'original_nama_penghargaan' => ['required', 'string', 'max:60'],
            'original_tanggal' => ['required', 'date'],
            'jenis' => ['required', 'string', 'max:30'],
            'nama_penghargaan' => ['required', 'string', 'max:60'],
            'tanggal' => ['required', 'date'],
            'instansi' => ['required', 'string', 'max:40'],
            'pejabat_pemberi' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_nama_penghargaan.required' => 'Identitas data asli wajib dikirim.',
            'original_tanggal.required' => 'Identitas data asli wajib dikirim.',
            'jenis.required' => 'Jenis wajib diisi.',
            'nama_penghargaan.required' => 'Nama penghargaan wajib diisi.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'instansi.required' => 'Instansi wajib diisi.',
            'pejabat_pemberi.required' => 'Pejabat pemberi wajib diisi.',
        ];
    }
}
