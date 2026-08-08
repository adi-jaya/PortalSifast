<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAsetMutasiLokasiRequest extends FormRequest
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
            'aset_id' => ['required', 'integer', 'exists:aset,id'],
            'aset_ruang_tujuan_id' => ['required', 'integer', 'exists:aset_ruang,id'],
            'penerima_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'penerima_nik' => ['nullable', 'string', 'max:30'],
            'tanggal_mutasi' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasUser = filled($this->input('penerima_user_id'));
            $hasNik = filled($this->input('penerima_nik'));
            if ($hasUser === $hasNik) {
                $validator->errors()->add('penerima', 'Pilih tepat satu: user portal atau pegawai (NIK).');
            }
        });
    }
}
