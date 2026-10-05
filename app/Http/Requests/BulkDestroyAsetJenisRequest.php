<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyAsetJenisRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:aset_jenis,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal satu jenis untuk dihapus.',
            'ids.min' => 'Pilih minimal satu jenis untuk dihapus.',
            'ids.max' => 'Maksimal 100 jenis per penghapusan.',
            'ids.*.exists' => 'Salah satu jenis yang dipilih tidak ditemukan.',
        ];
    }
}
