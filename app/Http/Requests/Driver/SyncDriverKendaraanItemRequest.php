<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class SyncDriverKendaraanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageDriverMaster() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.driver_checklist_item_id' => ['required', 'integer', 'exists:driver_checklist_item,id'],
            'items.*.berlaku' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Daftar item wajib diisi.',
        ];
    }
}
