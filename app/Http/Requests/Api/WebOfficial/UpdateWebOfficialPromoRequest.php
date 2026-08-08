<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialPromoRequest extends FormRequest
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
        $promoId = $this->route('promo')?->id ?? $this->route('promo');

        return [
            'title' => ['sometimes', 'string', 'min:3', 'max:200'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_promos', 'slug')->ignore($promoId),
            ],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
            'excerpt' => ['sometimes', 'string', 'min:10', 'max:500'],
            'body' => ['sometimes', 'nullable', 'string'],
            'cover' => ['sometimes', 'string', 'url', 'max:500'],
            'startDate' => ['sometimes', 'date'],
            'endDate' => ['sometimes', 'date', 'after_or_equal:startDate'],
            'isFeatured' => ['sometimes', 'boolean'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('isFeatured')) {
            $merged['isFeatured'] = $this->boolean('isFeatured');
        }

        if ($this->has('isActive')) {
            $merged['isActive'] = $this->boolean('isActive');
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
