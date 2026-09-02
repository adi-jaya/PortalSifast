<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsetMerkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $kode = trim((string) $this->input('kode_merk', ''));
        $nama = trim((string) $this->input('nama_merk', ''));

        $this->merge([
            'kode_merk' => $kode === '' ? null : strtoupper($kode),
            'nama_merk' => $nama,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_merk' => ['required', 'string', 'min:2', 'max:100', Rule::unique('aset_merk', 'nama_merk')],
            'kode_merk' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('aset_merk', 'kode_merk')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_merk.required' => 'Nama merk wajib diisi.',
            'nama_merk.min' => 'Nama merk minimal 2 karakter.',
            'nama_merk.unique' => 'Nama merk sudah digunakan.',
            'kode_merk.unique' => 'Kode merk sudah digunakan.',
            'kode_merk.regex' => 'Kode merk hanya boleh huruf, angka, strip, atau underscore.',
        ];
    }
}
