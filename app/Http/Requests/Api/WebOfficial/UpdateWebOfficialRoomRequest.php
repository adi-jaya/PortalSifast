<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialRoomRequest extends FormRequest
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
        $roomId = $this->route('room')?->id ?? $this->route('room');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:200'],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_rooms', 'slug')->ignore($roomId),
            ],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:100'],
            'badge' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'string', 'min:10'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'photo' => ['sometimes', 'string', 'url', 'max:500'],
            'facilities' => ['sometimes', 'array', 'min:1'],
            'facilities.*' => ['required', 'string', 'max:100'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug sudah digunakan.',
        ];
    }
}
