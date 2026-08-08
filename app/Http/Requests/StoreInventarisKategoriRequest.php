<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisKategoriRequest extends FormRequest
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
            'id_kategori' => ['required', 'string', 'max:10', 'unique:dbsimrs.inventaris_kategori,id_kategori'],
            'nama_kategori' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_kategori.required' => 'ID kategori wajib diisi.',
            'id_kategori.unique' => 'ID kategori sudah digunakan.',
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
        ];
    }
}
