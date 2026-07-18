<?php

namespace App\Http\Requests\Api\WebOfficial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebOfficialRoomRequest extends FormRequest
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
            'photo' => ['required', 'string', 'url', 'max:500'],
            'facilities' => ['required', 'array', 'min:1'],
            'facilities.*' => ['required', 'string', 'max:100'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kamar wajib diisi.',
            'description.required' => 'Deskripsi wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
            'photo.required' => 'Foto wajib diisi.',
            'facilities.required' => 'Minimal satu fasilitas wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
        ];
    }
}
