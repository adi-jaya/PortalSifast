<?php

namespace App\Http\Requests\Patroli;

use Illuminate\Foundation\Http\FormRequest;

class StorePatroliAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessPatroli() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255', 'unique:patroli_area,nama'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama area wajib diisi.',
            'nama.unique' => 'Nama area sudah dipakai.',
        ];
    }
}
