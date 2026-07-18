<?php

namespace App\Http\Requests\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialRoomWebRequest extends FormRequest
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
        $roomId = $this->route('room')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_rooms', 'slug')->ignore($roomId),
            ],
            'tagline' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'min:10'],
            'price' => ['required', 'integer', 'min:0'],
            'photo' => ['nullable', 'string', 'url', 'max:500'],
            'photo_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
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
