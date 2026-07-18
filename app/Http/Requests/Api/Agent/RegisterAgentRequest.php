<?php

namespace App\Http\Requests\Api\Agent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterAgentRequest extends FormRequest
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
            'enrollment_key' => ['required', 'string'],
            'uuid' => ['required', 'uuid'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'computer_name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:64'],
            'agent_version' => ['nullable', 'string', 'max:32'],
            'hardware' => ['nullable', 'array'],
            'hardware.os' => ['nullable', 'string', 'max:255'],
            'hardware.os_version' => ['nullable', 'string', 'max:255'],
            'hardware.architecture' => ['nullable', 'string', 'max:32'],
            'hardware.cpu_model' => ['nullable', 'string', 'max:255'],
            'hardware.cpu_cores' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'hardware.ram_total_mb' => ['nullable', 'integer', 'min:0'],
            'hardware.disk_total_gb' => ['nullable', 'integer', 'min:0'],
            'hardware.manufacturer' => ['nullable', 'string', 'max:255'],
            'hardware.model' => ['nullable', 'string', 'max:255'],
            'hardware.serial_number' => ['nullable', 'string', 'max:255'],
            'hardware.motherboard' => ['nullable', 'string', 'max:255'],
            'hardware.bios' => ['nullable', 'string', 'max:255'],
            'hardware.boot_time' => ['nullable', 'date'],
            'hardware.timezone' => ['nullable', 'string', 'max:64'],
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
            'enrollment_key.required' => 'Enrollment key wajib diisi.',
            'uuid.required' => 'UUID perangkat wajib diisi.',
            'uuid.uuid' => 'UUID perangkat tidak valid.',
        ];
    }
}
