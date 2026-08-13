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
            'critical_software' => ['nullable', 'array'],
            'critical_software.*.id' => ['required_with:critical_software', 'string', 'max:64'],
            'critical_software.*.name' => ['required_with:critical_software', 'string', 'max:255'],
            'critical_software.*.status' => ['required_with:critical_software', 'string', 'in:running,installed,missing'],
            'critical_software.*.detail' => ['nullable', 'string', 'max:255'],
            'usb' => ['nullable', 'array'],
            'usb.ports_total' => ['nullable', 'integer', 'min:0', 'max:512'],
            'usb.ports_used' => ['nullable', 'integer', 'min:0', 'max:512'],
            'usb.ports_empty' => ['nullable', 'integer', 'min:0', 'max:512'],
            'usb.removable_storage_count' => ['nullable', 'integer', 'min:0', 'max:512'],
            'usb.has_removable_storage' => ['nullable', 'boolean'],
            'usb.printer_count' => ['nullable', 'integer', 'min:0', 'max:512'],
            'usb.estimated' => ['nullable', 'boolean'],
            'usb.note' => ['nullable', 'string', 'max:500'],
            'usb.devices' => ['nullable', 'array', 'max:100'],
            'usb.devices.*.name' => ['nullable', 'string', 'max:255'],
            'usb.devices.*.kind' => ['nullable', 'string', 'in:storage,printer,hub,hid,other'],
            'usb.devices.*.device_id' => ['nullable', 'string', 'max:255'],
            'sensors' => ['nullable', 'array'],
            'sensors.supported' => ['nullable', 'boolean'],
            'sensors.note' => ['nullable', 'string', 'max:500'],
            'sensors.readings' => ['nullable', 'array', 'max:32'],
            'sensors.readings.*.name' => ['nullable', 'string', 'max:255'],
            'sensors.readings.*.temperature_c' => ['nullable', 'numeric', 'min:-50', 'max:150'],
            'suspicious_processes' => ['nullable', 'array', 'max:100'],
            'suspicious_processes.*.pid' => ['required_with:suspicious_processes', 'integer', 'min:1'],
            'suspicious_processes.*.exe' => ['required_with:suspicious_processes', 'string', 'max:255'],
            'suspicious_processes.*.path' => ['nullable', 'string', 'max:1024'],
            'suspicious_processes.*.cpu_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'suspicious_processes.*.reasons' => ['nullable', 'array', 'max:10'],
            'suspicious_processes.*.reasons.*' => ['string', 'max:64'],
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
