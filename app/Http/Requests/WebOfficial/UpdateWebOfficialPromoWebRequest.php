<?php

namespace App\Http\Requests\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialPromoWebRequest extends FormRequest
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
        $promoId = $this->route('promosi')?->id;

        return [
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_promos', 'slug')->ignore($promoId),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['required', 'string', 'min:10', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover' => ['nullable', 'string', 'url', 'max:500'],
            'cover_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : null,
            'label' => filled($this->input('label')) ? $this->input('label') : 'PROMO SPESIAL',
        ]);
    }
}
