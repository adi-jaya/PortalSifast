<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBerkasScanInboxRequest extends FormRequest
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
            'nik' => ['required', 'string', 'max:20'],
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
            'nik.required' => 'Pegawai wajib dipilih.',
            'kode_berkas.required' => 'Jenis berkas wajib dipilih.',
            'tgl_uploud.required' => 'Tanggal upload wajib diisi.',
            'tgl_uploud.date' => 'Tanggal upload tidak valid.',
        ];
    }
}
