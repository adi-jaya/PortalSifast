<?php

namespace App\Http\Requests\Api\WebOfficial;

use App\Services\WebOfficialMediaStorageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadWebOfficialMediaRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:'.WebOfficialMediaStorageService::maxSizeKb(),
            ],
            'folder' => ['nullable', 'string', Rule::in(['informasi', 'kamar-inap', 'promosi', 'poliklinik', 'rekanan'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File gambar wajib diupload.',
            'file.mimes' => 'Format gambar harus JPEG, PNG, atau WebP.',
            'file.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}
