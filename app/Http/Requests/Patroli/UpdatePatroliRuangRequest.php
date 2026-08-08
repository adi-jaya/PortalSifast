<?php

namespace App\Http\Requests\Patroli;

use App\Models\PatroliArea;
use App\Models\PatroliRuang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatroliRuangRequest extends FormRequest
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
        /** @var PatroliRuang $ruang */
        $ruang = $this->route('ruang');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('patroli_ruang', 'nama')
                    ->where('patroli_area_id', $area->id)
                    ->ignore($ruang->id),
            ],
            'kode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('patroli_ruang', 'kode')->ignore($ruang->id),
            ],
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
