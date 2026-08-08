<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisMerkRequest extends FormRequest
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
            'id_merk' => ['required', 'string', 'max:10', 'unique:dbsimrs.inventaris_merk,id_merk'],
            'nama_merk' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_merk.required' => 'ID merk wajib diisi.',
            'id_merk.unique' => 'ID merk sudah digunakan.',
            'nama_merk.required' => 'Nama merk wajib diisi.',
        ];
    }
}
