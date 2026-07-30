<?php

namespace App\Http\Requests\Patroli;

use App\Models\PatroliArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatroliRuangRequest extends FormRequest
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
                Rule::unique('patroli_ruang', 'nama')->where('patroli_area_id', $area->id),
            ],
            'kode' => ['nullable', 'string', 'max:64', Rule::unique('patroli_ruang', 'kode')],
            'patroli_template_id' => ['nullable', 'integer', Rule::exists('patroli_template', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama ruang wajib diisi.',
            'nama.unique' => 'Nama ruang sudah ada di area ini.',
            'kode.unique' => 'Kode ruang patroli sudah dipakai.',
        ];
    }
}
