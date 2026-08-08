<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisRuangRequest extends FormRequest
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
            'id_ruang' => ['required', 'string', 'max:5', 'unique:dbsimrs.inventaris_ruang,id_ruang'],
            'nama_ruang' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_ruang.required' => 'ID ruang wajib diisi.',
            'id_ruang.unique' => 'ID ruang sudah digunakan.',
            'nama_ruang.required' => 'Nama ruang wajib diisi.',
        ];
    }
}
