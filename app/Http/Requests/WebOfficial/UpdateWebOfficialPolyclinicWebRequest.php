<?php

namespace App\Http\Requests\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialPolyclinicWebRequest extends FormRequest
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
            'kd_poli' => ['required', 'string', 'max:20', Rule::unique('web_official_polyclinics', 'kd_poli')->ignore($polyclinicId)],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_polyclinics', 'slug')->ignore($polyclinicId),
            ],
            'label' => ['nullable', 'string', 'max:80'],
            'name_override' => ['nullable', 'string', 'max:200'],
            'short_description' => ['required', 'string', 'min:10', 'max:500'],
            'long_description' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'url', 'max:500'],
            'photo_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'label' => filled($this->input('label')) ? $this->input('label') : 'KLINIK SPESIALIS',
        ]);
    }
}
