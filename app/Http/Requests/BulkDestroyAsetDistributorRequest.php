<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyAsetDistributorRequest extends FormRequest
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
            'ids.*' => ['required', 'integer', 'distinct', 'exists:aset_distributor,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal satu distributor untuk dihapus.',
            'ids.min' => 'Pilih minimal satu distributor untuk dihapus.',
            'ids.max' => 'Maksimal 100 distributor per penghapusan.',
            'ids.*.exists' => 'Salah satu distributor yang dipilih tidak ditemukan.',
        ];
    }
}
