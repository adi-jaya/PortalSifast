<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifikasiAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'aset_ruang_id' => ['required', 'integer', 'exists:aset_ruang,id'],
            'tahun_registrasi' => ['required', 'integer', 'min:1900', 'max:2100'],
            'kondisi' => ['nullable', 'string', 'max:30'],
            'kelas_aset' => ['nullable', Rule::in(['medis', 'non_medis'])],
            'wajib_kalibrasi' => ['sometimes', 'boolean'],
            'umur_ekonomis_bulan' => ['nullable', 'integer', 'min:1', 'max:600'],
            'regenerate_kode' => ['sometimes', 'boolean'],
        ];
    }
}
