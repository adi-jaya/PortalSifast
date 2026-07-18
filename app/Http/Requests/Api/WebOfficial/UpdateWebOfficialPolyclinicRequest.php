<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialPolyclinicRequest extends FormRequest
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
        $polyclinicId = $this->route('poliklinik')?->id ?? $this->route('poliklinik');

        return [
            'kdPoli' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('web_official_polyclinics', 'kd_poli')->ignore($polyclinicId),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_polyclinics', 'slug')->ignore($polyclinicId),
            ],
            'label' => ['sometimes', 'nullable', 'string', 'max:80'],
            'nameOverride' => ['sometimes', 'nullable', 'string', 'max:200'],
            'shortDescription' => ['sometimes', 'string', 'min:10', 'max:500'],
            'longDescription' => ['sometimes', 'nullable', 'string'],
            'photo' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('isActive')) {
            $merged['isActive'] = $this->boolean('isActive');
        }

        if ($this->has('label') && blank($this->input('label'))) {
            $merged['label'] = 'KLINIK SPESIALIS';
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
