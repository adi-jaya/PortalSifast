<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi dikendalikan oleh Gate di Controller
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'extra_fields' => ['nullable', 'array'],
        ];
    }
}
