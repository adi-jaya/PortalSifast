<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisJenisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_jenis' => ['required', 'string', 'max:10', 'unique:dbsimrs.inventaris_jenis,id_jenis'],
            'nama_jenis' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_jenis.required' => 'ID jenis wajib diisi.',
            'id_jenis.unique' => 'ID jenis sudah digunakan.',
            'nama_jenis.required' => 'Nama jenis wajib diisi.',
        ];
    }
}
