<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageDriverMaster() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'urutan' => ['required', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama item wajib diisi.',
            'urutan.required' => 'Urutan wajib diisi.',
        ];
    }
}
