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
            'computer_name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:64'],
            'agent_version' => ['nullable', 'string', 'max:32'],
            'hardware' => ['nullable', 'array'],
            'hardware.manufacturer' => ['nullable', 'string', 'max:255'],
            'hardware.model' => ['nullable', 'string', 'max:255'],
            'hardware.serial_number' => ['nullable', 'string', 'max:255'],
            'hardware.motherboard' => ['nullable', 'string', 'max:255'],
            'hardware.bios' => ['nullable', 'string', 'max:255'],
            'hardware.domain' => ['nullable', 'string', 'max:255'],
            'hardware.username' => ['nullable', 'string', 'max:255'],
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
