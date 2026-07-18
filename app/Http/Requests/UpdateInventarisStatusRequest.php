<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventarisStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status_barang' => ['required', 'string', 'in:Ada,Rusak,Hilang,Perbaikan,Dipinjam'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_barang.required' => 'Status barang wajib dipilih.',
            'status_barang.in' => 'Status barang harus salah satu dari: Ada, Rusak, Hilang, Perbaikan, atau Dipinjam',
        ];
    }
}
