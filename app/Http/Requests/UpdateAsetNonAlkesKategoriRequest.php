<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsetNonAlkesKategoriRequest extends FormRequest
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
            'aset_kategori_id' => ['nullable', 'integer', 'exists:aset_kategori,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aset_kategori_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }
}
