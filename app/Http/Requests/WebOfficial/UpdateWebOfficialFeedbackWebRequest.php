<?php

namespace App\Http\Requests\WebOfficial;

use App\Enums\WebOfficialFeedbackStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialFeedbackWebRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(WebOfficialFeedbackStatus::values())],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
