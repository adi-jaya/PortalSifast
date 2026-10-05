<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverKendaraanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageDriverMaster() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('tahun') === '' || $this->input('tahun') === null) {
            $this->merge(['tahun' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'no_polisi' => ['nullable', 'string', 'max:30'],
            'merk' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'tahun' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kendaraan wajib diisi.',
            'status.in' => 'Status kendaraan tidak valid.',
            'foto.image' => 'File foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
