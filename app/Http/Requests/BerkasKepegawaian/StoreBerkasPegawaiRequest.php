<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class StoreBerkasPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg', 'max:10240'],
            'kode_berkas' => ['required', 'string', 'max:20'],
            'tgl_uploud' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dokumen.required' => 'Dokumen wajib diunggah.',
            'dokumen.file' => 'Dokumen harus berupa file.',
            'dokumen.mimes' => 'Dokumen harus berformat PDF, JPG, atau JPEG.',
            'dokumen.max' => 'Dokumen maksimal 10 MB.',
            'kode_berkas.required' => 'Jenis berkas wajib dipilih.',
            'kode_berkas.max' => 'Kode berkas maksimal 20 karakter.',
            'tgl_uploud.required' => 'Tanggal upload wajib diisi.',
            'tgl_uploud.date' => 'Tanggal upload tidak valid.',
        ];
    }
}
