<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAsetPeminjamanRequest extends FormRequest
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
            'peminjam_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'peminjam_nik' => ['nullable', 'string', 'max:30'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_kembali_rencana' => ['nullable', 'date', 'after_or_equal:tanggal_pinjam'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasUser = filled($this->input('peminjam_user_id'));
            $hasNik = filled($this->input('peminjam_nik'));
            if ($hasUser === $hasNik) {
                $validator->errors()->add('peminjam', 'Pilih tepat satu: user portal atau pegawai (NIK).');
            }
        });
    }
}
