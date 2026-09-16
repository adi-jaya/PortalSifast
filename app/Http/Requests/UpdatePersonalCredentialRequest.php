<?php

namespace App\Http\Requests;

use App\Models\Portal;
use App\Models\UserPortalCredential;
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
        /** @var Portal|null $portal */
        $portal = $this->route('portal');
        $user = $this->user();

        $hasExistingPassword = false;
        if ($portal && $user) {
            $credential = UserPortalCredential::where('user_id', $user->id)
                ->where('portal_id', $portal->id)
                ->first();
            $hasExistingPassword = filled($credential?->getRawOriginal('personal_password'));
        }

        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => [$hasExistingPassword ? 'nullable' : 'required', 'string', 'min:4', 'max:255'],
            'extra_fields' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Password akun pribadi wajib diisi untuk konfigurasi awal.',
        ];
    }
}
