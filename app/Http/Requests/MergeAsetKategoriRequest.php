<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeAsetKategoriRequest extends FormRequest
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
            'source_id' => [
                'required',
                'integer',
                'exists:aset_kategori,id',
                Rule::notIn([(int) $this->route('kategori')?->id]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_id.required' => 'Pilih kategori yang akan digabung.',
            'source_id.exists' => 'Kategori sumber tidak ditemukan.',
            'source_id.not_in' => 'Kategori sumber dan tujuan tidak boleh sama.',
        ];
    }
}
