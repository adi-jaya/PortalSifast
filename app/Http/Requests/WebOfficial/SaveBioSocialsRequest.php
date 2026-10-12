<?php

namespace App\Http\Requests\WebOfficial;

use App\Models\WebOfficialBioSocial;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveBioSocialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebOfficial() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'socials' => ['required', 'array', 'max:'.count(WebOfficialBioSocial::PLATFORMS)],
            'socials.*.platform' => ['required', 'string', 'distinct', Rule::in(WebOfficialBioSocial::PLATFORMS)],
            'socials.*.url' => ['nullable', 'string', 'max:500', 'url:https'],
            'socials.*.isActive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('socials', []) as $i => $social) {
                if (filter_var($social['isActive'] ?? false, FILTER_VALIDATE_BOOLEAN) && blank($social['url'] ?? null)) {
                    $validator->errors()->add("socials.$i.url", 'URL wajib diisi jika platform aktif.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'socials.*.url.url' => 'URL harus diawali https://.',
        ];
    }
}
