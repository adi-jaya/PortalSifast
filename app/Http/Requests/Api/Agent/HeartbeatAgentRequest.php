<?php

namespace App\Http\Requests\Api\Agent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class HeartbeatAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cpu_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'ram_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'disk_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpu_percent.required' => 'cpu_percent wajib diisi.',
            'ram_percent.required' => 'ram_percent wajib diisi.',
            'disk_percent.required' => 'disk_percent wajib diisi.',
        ];
    }
}
