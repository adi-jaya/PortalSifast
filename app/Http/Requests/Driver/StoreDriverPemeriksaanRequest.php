<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverPemeriksaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateDriverPemeriksaan() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'driver_kendaraan_id' => ['required', 'integer', 'exists:driver_kendaraan,id'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.driver_checklist_item_id' => ['required', 'integer', 'exists:driver_checklist_item,id'],
            'items.*.hasil' => ['required', 'in:layak,tidak_layak'],
            'items.*.temuan' => ['nullable', 'string', 'max:2000'],
            'items.*.rekomendasi' => ['nullable', 'string', 'max:2000'],
            'items.*.keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'driver_kendaraan_id.required' => 'Kendaraan wajib dipilih.',
            'items.required' => 'Item checklist wajib diisi.',
            'items.*.hasil.in' => 'Hasil item tidak valid. Pilih Baik atau Tidak Baik.',
        ];
    }
}
