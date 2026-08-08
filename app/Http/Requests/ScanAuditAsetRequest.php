<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanAuditAsetRequest extends FormRequest
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
            'kode_aset' => ['required', 'string', 'max:60'],
            'hasil' => ['nullable', 'in:ditemukan,tidak_ditemukan,salah_ruang'],
            'kondisi_aktual' => ['nullable', 'string', 'max:30'],
            'aset_ruang_ditemukan_id' => ['nullable', 'integer', 'exists:aset_ruang,id'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
