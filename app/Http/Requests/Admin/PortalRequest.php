<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $merges['slug'] = Str::slug((string) $this->input('name'));
        }

        if ($this->has('category') && is_string($this->input('category'))) {
            $merges['category'] = trim($this->input('category'));
        }

        if (! empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        /** @var Portal|string|int|null $portal */
        $portal = $this->route('portal');
        $portalId = $portal instanceof Portal ? $portal->id : $portal;

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:150',
                Rule::unique('portals', 'slug')->ignore($portalId),
            ],
            'category' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url'],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'icon_file' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'max:2048',
            ],
            'remove_logo' => [
                'nullable',
                'boolean',
            ],
            'description' => ['nullable', 'string'],
            'auth_type' => ['required', 'in:shared,personal,both'],
            'shared_username' => ['nullable', 'string', 'max:255'],
            'shared_password' => ['nullable', 'string'],
            'shared_extra_fields' => ['nullable', 'array'],
            'form_config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
