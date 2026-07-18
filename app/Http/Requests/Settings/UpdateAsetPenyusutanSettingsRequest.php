<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsetPenyusutanSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'residu_persen_default' => ['required', 'numeric', 'min:0', 'max:50'],
            'umur_bulan_medis' => ['required', 'integer', 'min:1', 'max:600'],
            'umur_bulan_non_medis' => ['required', 'integer', 'min:1', 'max:600'],
            'umur_bulan_default' => ['required', 'integer', 'min:1', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'residu_persen_default.required' => 'Persen nilai residu default wajib diisi.',
            'residu_persen_default.max' => 'Persen residu maksimal 50%.',
            'umur_bulan_medis.required' => 'Umur manfaat default aset medis wajib diisi.',
            'umur_bulan_non_medis.required' => 'Umur manfaat default aset non-medis wajib diisi.',
            'umur_bulan_default.required' => 'Umur manfaat default umum wajib diisi.',
        ];
    }
}
