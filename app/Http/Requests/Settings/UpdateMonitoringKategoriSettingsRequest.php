<?php

namespace App\Http\Requests\Settings;

use App\Models\AsetKategori;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitoringKategoriSettingsRequest extends FormRequest
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
        $existingCodes = AsetKategori::query()
            ->whereNotNull('kode_kategori')
            ->where('kode_kategori', '!=', '')
            ->pluck('kode_kategori')
            ->all();

        return [
            'kategori_codes' => ['present', 'array'],
            'kategori_codes.*' => ['string', 'max:20', Rule::in($existingCodes)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori_codes.present' => 'Daftar kategori wajib dikirim.',
            'kategori_codes.*.in' => 'Ada kode kategori yang tidak valid.',
        ];
    }
}
