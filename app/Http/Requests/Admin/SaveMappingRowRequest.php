<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class SaveMappingRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'portal_id' => ['required', 'integer', 'exists:portals,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'has_access' => ['required', 'boolean'],
            'credential_type' => ['nullable', 'in:use_shared,personal'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
