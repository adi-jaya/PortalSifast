<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditAsetItemRequest extends FormRequest
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
            'hasil' => ['required', Rule::in(['ditemukan', 'tidak_ditemukan', 'salah_ruang'])],
            'kondisi_aktual' => ['nullable', 'string', 'max:30'],
            'aset_ruang_ditemukan_id' => [
                'nullable',
                'integer',
                'exists:aset_ruang,id',
                Rule::requiredIf(fn () => $this->input('hasil') === 'salah_ruang'),
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasil.required' => 'Hasil audit wajib diisi.',
            'hasil.in' => 'Hasil audit tidak valid.',
            'aset_ruang_ditemukan_id.required' => 'Ruang ditemukan wajib diisi untuk salah ruang.',
        ];
    }
}
