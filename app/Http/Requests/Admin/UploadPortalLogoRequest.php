<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class UploadPortalLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'icon_file' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'max:2048',
            ],
        ];
    }
}
