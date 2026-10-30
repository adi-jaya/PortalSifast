<?php

namespace App\Http\Requests\Driver;

use App\Models\DriverPemeriksaanDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverPemeriksaanApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->canCreateDriverPemeriksaan() || $user->isPayrollServiceIntegrationAccount();
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
            'items.*.hasil' => ['required', Rule::in([
                DriverPemeriksaanDetail::HASIL_LAYAK,
                DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK,
            ])],
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
            'items.*.hasil.in' => 'Hasil item tidak valid. Kirim layak atau tidak_layak (label UI: Baik / Tidak Baik).',
        ];
    }
}
