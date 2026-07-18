<?php

namespace App\Http\Requests\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebOfficialRoomWebRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:web_official_rooms,slug'],
            'tagline' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'min:10'],
            'price' => ['required', 'integer', 'min:0'],
            'photo' => ['nullable', 'string', 'url', 'max:500', 'required_without:photo_file'],
            'photo_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048', 'required_without:photo'],
            'facilities_text' => ['required', 'string', 'min:1'],
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
