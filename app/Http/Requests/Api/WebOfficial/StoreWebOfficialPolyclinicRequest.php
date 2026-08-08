<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebOfficialPolyclinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebOfficial() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $polyclinicId = $this->route('poliklinik')?->id;

        return [
            'kdPoli' => [
                'required',
                'string',
                'max:20',
                Rule::unique('web_official_polyclinics', 'kd_poli')->ignore($polyclinicId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_polyclinics', 'slug')->ignore($polyclinicId),
            ],
            'label' => ['nullable', 'string', 'max:80'],
            'nameOverride' => ['nullable', 'string', 'max:200'],
            'shortDescription' => ['required', 'string', 'min:10', 'max:500'],
            'longDescription' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'url', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => filled($this->input('label')) ? $this->input('label') : 'KLINIK SPESIALIS',
            'isActive' => $this->has('isActive') ? $this->boolean('isActive') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kdPoli.required' => 'Kode poliklinik wajib dipilih.',
            'shortDescription.required' => 'Deskripsi singkat wajib diisi.',
            'shortDescription.min' => 'Deskripsi singkat minimal 10 karakter.',
            'photo.url' => 'URL foto poliklinik tidak valid.',
            'slug.unique' => 'Slug poliklinik sudah digunakan.',
        ];
    }
}
