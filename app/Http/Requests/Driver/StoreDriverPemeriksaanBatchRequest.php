<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverPemeriksaanBatchRequest extends FormRequest
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
            'pemeriksaan' => ['required', 'array', 'min:1'],
            'pemeriksaan.*.driver_kendaraan_id' => ['required', 'integer', 'exists:driver_kendaraan,id', 'distinct'],
            'pemeriksaan.*.catatan' => ['nullable', 'string', 'max:2000'],
            'pemeriksaan.*.items' => ['required', 'array', 'min:1'],
            'pemeriksaan.*.items.*.driver_checklist_item_id' => ['required', 'integer', 'exists:driver_checklist_item,id'],
            'pemeriksaan.*.items.*.hasil' => ['required', 'in:layak,tidak_layak'],
            'pemeriksaan.*.items.*.temuan' => ['nullable', 'string', 'max:2000'],
            'pemeriksaan.*.items.*.rekomendasi' => ['nullable', 'string', 'max:2000'],
            'pemeriksaan.*.items.*.keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pemeriksaan.required' => 'Minimal satu kendaraan harus diisi lengkap.',
            'pemeriksaan.min' => 'Minimal satu kendaraan harus diisi lengkap.',
            'pemeriksaan.*.driver_kendaraan_id.distinct' => 'Kendaraan tidak boleh diduplikasi.',
            'pemeriksaan.*.items.*.hasil.in' => 'Hasil item tidak valid. Pilih Layak atau Tidak Layak.',
        ];
    }
}
