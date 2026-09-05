<?php

namespace App\Http\Requests\BerkasKepegawaian;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterBerkasPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessBerkasKepegawaian() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => trim((string) $this->input('kode', '')),
            'nama_berkas' => trim((string) $this->input('nama_berkas', '')),
            'kategori' => trim((string) $this->input('kategori', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:20', 'unique:dbsimrs.master_berkas_pegawai,kode'],
            'nama_berkas' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', 'max:100'],
            'no_urut' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode wajib diisi.',
            'kode.max' => 'Kode maksimal 20 karakter.',
            'kode.unique' => 'Kode sudah digunakan.',
            'nama_berkas.required' => 'Nama berkas wajib diisi.',
            'nama_berkas.max' => 'Nama berkas maksimal 150 karakter.',
            'kategori.required' => 'Kategori wajib diisi.',
            'kategori.max' => 'Kategori maksimal 100 karakter.',
            'no_urut.required' => 'No urut wajib diisi.',
            'no_urut.integer' => 'No urut harus berupa angka.',
            'no_urut.min' => 'No urut minimal 0.',
        ];
    }
}
