<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventarisBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['kode_produsen', 'id_merk', 'id_kategori', 'id_jenis', 'isbn', 'thn_produksi', 'jml_barang'];

        $merged = [];
        foreach ($nullable as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $value = $this->input($field);
            $merged[$field] = ($value === '' || $value === '__none__') ? null : $value;
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $kodeBarang = $this->route('barang')?->kode_barang ?? $this->route('barang');

        return [
            'kode_barang' => [
                'required',
                'string',
                'max:20',
                Rule::unique('dbsimrs.inventaris_barang', 'kode_barang')->ignore($kodeBarang, 'kode_barang'),
            ],
            'nama_barang' => ['required', 'string', 'max:60'],
            'jml_barang' => ['nullable', 'integer', 'min:0'],
            'kode_produsen' => ['nullable', 'string', 'max:10', 'exists:dbsimrs.inventaris_produsen,kode_produsen'],
            'id_merk' => ['nullable', 'string', 'max:10', 'exists:dbsimrs.inventaris_merk,id_merk'],
            'thn_produksi' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 10)],
            'isbn' => ['nullable', 'string', 'max:50'],
            'id_kategori' => ['nullable', 'string', 'max:10', 'exists:dbsimrs.inventaris_kategori,id_kategori'],
            'id_jenis' => ['nullable', 'string', 'max:10', 'exists:dbsimrs.inventaris_jenis,id_jenis'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.unique' => 'Kode barang sudah digunakan.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'jml_barang.integer' => 'Jumlah barang harus berupa angka.',
            'jml_barang.min' => 'Jumlah barang tidak boleh negatif.',
            'thn_produksi.integer' => 'Tahun produksi harus berupa angka.',
            'thn_produksi.min' => 'Tahun produksi tidak valid.',
            'thn_produksi.max' => 'Tahun produksi tidak valid.',
            'kode_produsen.exists' => 'Produsen tidak ditemukan.',
            'id_merk.exists' => 'Merk tidak ditemukan.',
            'id_kategori.exists' => 'Kategori tidak ditemukan.',
            'id_jenis.exists' => 'Jenis tidak ditemukan.',
        ];
    }
}
