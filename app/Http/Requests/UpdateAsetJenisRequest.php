<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAsetJenisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $kode = trim((string) $this->input('kode_jenis', ''));
        $nama = trim((string) $this->input('nama_jenis', ''));
        $merkId = $this->input('aset_merk_id');

        $this->merge([
            'kode_jenis' => $kode === '' ? null : strtoupper($kode),
            'nama_jenis' => $nama,
            'aset_merk_id' => $merkId === '' || $merkId === null ? null : (int) $merkId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jenisId = $this->route('jenis')?->id;

        return [
            'nama_jenis' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('aset_jenis', 'nama_jenis')->ignore($jenisId),
            ],
            'kode_jenis' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('aset_jenis', 'kode_jenis')->ignore($jenisId),
            ],
            'aset_merk_id' => ['nullable', 'integer', 'exists:aset_merk,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_jenis.required' => 'Nama jenis wajib diisi.',
            'nama_jenis.min' => 'Nama jenis minimal 2 karakter.',
            'nama_jenis.unique' => 'Nama jenis sudah digunakan.',
            'kode_jenis.required' => 'Kode jenis wajib diisi.',
            'kode_jenis.unique' => 'Kode jenis sudah digunakan.',
            'kode_jenis.regex' => 'Kode jenis hanya boleh huruf, angka, strip, atau underscore.',
            'aset_merk_id.exists' => 'Merk tidak ditemukan.',
        ];
    }
}
