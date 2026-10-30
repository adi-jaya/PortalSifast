<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsetRuangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_ruang' => strtoupper(trim((string) $this->input('kode_ruang', ''))),
            'nama_ruang' => trim((string) $this->input('nama_ruang', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kode_ruang' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('aset_ruang', 'kode_ruang'),
            ],
            'nama_ruang' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_ruang.required' => 'Kode ruang wajib diisi.',
            'kode_ruang.unique' => 'Kode ruang sudah digunakan.',
            'kode_ruang.regex' => 'Kode ruang hanya boleh huruf, angka, strip, atau underscore.',
            'nama_ruang.required' => 'Nama ruang wajib diisi.',
            'nama_ruang.min' => 'Nama ruang minimal 2 karakter.',
        ];
    }
}
