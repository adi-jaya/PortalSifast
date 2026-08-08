<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferTicketDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket !== null && $this->user()?->can('transferDepartment', $ticket) === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $depId = $this->string('dep_id')->toString();

        return [
            'dep_id' => ['required', 'string', Rule::in(['IT', 'IPS'])],
            'ticket_category_id' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')
                    ->where('is_active', true)
                    ->where('dep_id', $depId),
            ],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dep_id.required' => 'Departemen tujuan harus dipilih.',
            'dep_id.in' => 'Departemen tujuan harus IT atau IPS.',
            'ticket_category_id.required' => 'Kategori tujuan harus dipilih.',
            'ticket_category_id.exists' => 'Kategori tidak valid untuk departemen tujuan.',
            'reason.required' => 'Alasan pemindahan harus diisi.',
            'reason.min' => 'Alasan pemindahan minimal 5 karakter.',
        ];
    }
}
