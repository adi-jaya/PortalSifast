<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsetKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $kode = trim((string) $this->input('kode_kategori', ''));
        $nama = trim((string) $this->input('nama_kategori', ''));

        $this->merge([
            'kode_kategori' => $kode === '' ? null : strtoupper($kode),
            'nama_kategori' => $nama,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kategoriId = $this->route('kategori')?->id;

        return [
            'nama_kategori' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('aset_kategori', 'nama_kategori')->ignore($kategoriId),
            ],
            'kode_kategori' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('aset_kategori', 'kode_kategori')->ignore($kategoriId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.min' => 'Nama kategori minimal 2 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
            'kode_kategori.required' => 'Kode kategori wajib diisi.',
            'kode_kategori.unique' => 'Kode kategori sudah digunakan.',
            'kode_kategori.regex' => 'Kode kategori hanya boleh huruf, angka, strip, atau underscore.',
        ];
    }
}
