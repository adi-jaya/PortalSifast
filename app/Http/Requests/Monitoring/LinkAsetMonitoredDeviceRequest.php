<?php

namespace App\Http\Requests\Monitoring;

use App\Models\Aset;
use App\Models\MonitoredDevice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LinkAsetMonitoredDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monitored_device_id' => ['nullable', 'integer', 'exists:monitored_devices,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monitored_device_id.exists' => 'Perangkat monitoring tidak ditemukan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Aset $aset */
            $aset = $this->route('aset');

            if (! $aset->isMonitorableForAgent()) {
                $validator->errors()->add(
                    'monitored_device_id',
                    'Aset ini bukan kategori yang bisa dimonitor (PC/laptop/dll).'
                );

                return;
            }

            $deviceId = $this->input('monitored_device_id');

            if ($deviceId === null || $deviceId === '') {
                return;
            }

            $device = MonitoredDevice::query()->find((int) $deviceId);

            if ($device === null) {
                return;
            }

            if ($device->aset_id !== null && (int) $device->aset_id !== (int) $aset->id) {
                $validator->errors()->add(
                    'monitored_device_id',
                    'Perangkat ini sudah terhubung ke aset inventaris lain.'
                );
            }
        });
    }
}
