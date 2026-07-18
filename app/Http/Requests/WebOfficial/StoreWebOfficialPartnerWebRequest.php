<?php

namespace App\Http\Requests\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebOfficialPartnerWebRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:web_official_partners,slug'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'string', 'url', 'max:500', 'required_without:logo_file'],
            'logo_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:2048', 'required_without:logo'],
            'website_url' => ['nullable', 'string', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
