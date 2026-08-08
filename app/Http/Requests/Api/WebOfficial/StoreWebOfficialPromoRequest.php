<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebOfficialPromoRequest extends FormRequest
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
        $promoId = $this->route('promo')?->id;

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
            'cover' => ['required', 'string', 'url', 'max:500'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'isFeatured' => ['nullable', 'boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'isFeatured' => $this->boolean('isFeatured'),
            'isActive' => $this->has('isActive') ? $this->boolean('isActive') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul promo wajib diisi.',
            'excerpt.required' => 'Ringkasan promo wajib diisi.',
            'cover.required' => 'Cover promo wajib diisi.',
            'startDate.required' => 'Tanggal mulai promo wajib diisi.',
            'endDate.required' => 'Tanggal akhir promo wajib diisi.',
            'endDate.after_or_equal' => 'Tanggal akhir promo harus sama atau setelah tanggal mulai.',
            'slug.unique' => 'Slug promo sudah digunakan.',
        ];
    }
}
