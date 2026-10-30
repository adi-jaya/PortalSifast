<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class SyncUserPortalsRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'assignments' => ['required', 'array'],
            'assignments.*.portal_id' => ['required', 'integer', 'exists:portals,id'],
            'assignments.*.has_access' => ['required', 'boolean'],
            'assignments.*.credential_type' => ['nullable', 'in:use_shared,personal'],
            'assignments.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
