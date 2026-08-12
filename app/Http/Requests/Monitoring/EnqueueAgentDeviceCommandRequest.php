<?php

namespace App\Http\Requests\Monitoring;

use App\Models\AgentDeviceCommand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnqueueAgentDeviceCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AgentDeviceCommand::TYPES)],
            'pid' => ['required_if:type,'.AgentDeviceCommand::TYPE_KILL_PID, 'nullable', 'integer', 'min:1'],
            'exe' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Jenis perintah wajib diisi.',
            'type.in' => 'Jenis perintah tidak valid.',
            'pid.required_if' => 'PID wajib diisi untuk menutup aplikasi.',
            'pid.integer' => 'PID harus berupa angka.',
            'pid.min' => 'PID tidak valid.',
        ];
    }
}
