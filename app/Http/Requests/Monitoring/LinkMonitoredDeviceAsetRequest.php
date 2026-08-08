<?php

namespace App\Http\Requests\Monitoring;

use App\Models\Aset;
use App\Models\MonitoredDevice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LinkMonitoredDeviceAsetRequest extends FormRequest
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
        /** @var MonitoredDevice $device */
        $device = $this->route('device');

        return [
            'aset_id' => [
                'nullable',
                'integer',
                'exists:aset,id',
                Rule::unique('monitored_devices', 'aset_id')->ignore($device->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aset_id.exists' => 'Aset tidak ditemukan.',
            'aset_id.unique' => 'Aset ini sudah terhubung ke perangkat monitoring lain.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $asetId = $this->input('aset_id');

            if ($asetId === null || $asetId === '') {
                return;
            }

            $isMonitorable = Aset::query()
                ->monitorableForAgent()
                ->whereKey((int) $asetId)
                ->exists();

            if (! $isMonitorable) {
                $validator->errors()->add(
                    'aset_id',
                    'Aset ini bukan kategori yang bisa dimonitor (PC/laptop/dll).'
                );
            }
        });
    }
}
