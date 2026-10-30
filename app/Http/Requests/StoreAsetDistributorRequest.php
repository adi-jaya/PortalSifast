<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsetDistributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $kode = trim((string) $this->input('kode_distributor', ''));
        $nama = trim((string) $this->input('nama_distributor', ''));
        $alamat = trim((string) $this->input('alamat', ''));
        $noTelp = trim((string) $this->input('no_telp', ''));
        $email = trim((string) $this->input('email', ''));

        $this->merge([
            'kode_distributor' => $kode === '' ? null : strtoupper($kode),
            'nama_distributor' => $nama,
            'alamat' => $alamat === '' ? null : $alamat,
            'no_telp' => $noTelp === '' ? null : $noTelp,
            'email' => $email === '' ? null : $email,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_distributor' => ['required', 'string', 'min:2', 'max:120', Rule::unique('aset_distributor', 'nama_distributor')],
            'kode_distributor' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('aset_distributor', 'kode_distributor')],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_distributor.required' => 'Nama distributor wajib diisi.',
            'nama_distributor.min' => 'Nama distributor minimal 2 karakter.',
            'nama_distributor.unique' => 'Nama distributor sudah digunakan.',
            'kode_distributor.unique' => 'Kode distributor sudah digunakan.',
            'kode_distributor.regex' => 'Kode distributor hanya boleh huruf, angka, strip, atau underscore.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
