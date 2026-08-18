<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsetAspakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_alat' => trim((string) $this->input('nama_alat', '')),
            'sinonim' => $this->nullableString('sinonim'),
            'parent_id' => $this->nullableInt('parent_id'),
            'wajib_kalibrasi' => filter_var($this->input('wajib_kalibrasi', false), FILTER_VALIDATE_BOOLEAN),
            'durasi_kalibrasi_hari' => $this->nullableInt('durasi_kalibrasi_hari'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_alat' => ['required', 'string', 'min:2', 'max:500'],
            'sinonim' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('aset_aspak_alat', 'id'),
            ],
            'wajib_kalibrasi' => ['boolean'],
            'durasi_kalibrasi_hari' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_alat.required' => 'Nama katalog wajib diisi.',
            'nama_alat.min' => 'Nama katalog minimal 2 karakter.',
            'parent_id.exists' => 'Induk tidak ditemukan.',
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->input($key);

        if ($value === null || $value === '' || $value === '__none__') {
            return null;
        }

        return (int) $value;
    }
}
