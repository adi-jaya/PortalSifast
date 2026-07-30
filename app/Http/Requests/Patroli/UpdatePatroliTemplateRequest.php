<?php

namespace App\Http\Requests\Patroli;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatroliTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessPatroli() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $templateId = $this->route('template')?->id ?? $this->route('template');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('patroli_template', 'nama')->ignore($templateId),
            ],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:patroli_template_item,id'],
            'items.*.nama' => ['required', 'string', 'max:255'],
            'items.*.urutan' => ['nullable', 'integer', 'min:0'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
