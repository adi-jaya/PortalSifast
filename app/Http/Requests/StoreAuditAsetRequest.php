<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditAsetRequest extends FormRequest
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
            'judul' => ['nullable', 'string', 'max:150'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aset_ruang_id.required' => 'Ruang audit wajib dipilih.',
            'aset_ruang_id.exists' => 'Ruang tidak ditemukan.',
        ];
    }
}
