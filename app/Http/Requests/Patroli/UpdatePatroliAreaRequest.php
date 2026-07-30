<?php

namespace App\Http\Requests\Patroli;

use App\Models\PatroliArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatroliAreaRequest extends FormRequest
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
        /** @var PatroliArea $area */
        $area = $this->route('area');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('patroli_area', 'nama')->ignore($area->id),
            ],
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
