<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisProdusenRequest extends FormRequest
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
            'kode_produsen' => ['required', 'string', 'max:10', 'unique:dbsimrs.inventaris_produsen,kode_produsen'],
            'nama_produsen' => ['required', 'string', 'max:40'],
            'alamat_produsen' => ['nullable', 'string', 'max:70'],
            'no_telp' => ['nullable', 'string', 'max:13'],
            'email' => ['nullable', 'email', 'max:25'],
            'website_produsen' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_produsen.required' => 'Kode produsen wajib diisi.',
            'kode_produsen.unique' => 'Kode produsen sudah digunakan.',
            'nama_produsen.required' => 'Nama produsen wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
