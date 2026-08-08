<?php

namespace App\Http\Requests\WebOfficial;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebOfficialArticleWebRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:5', 'max:300'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:web_official_articles,slug'],
            'category' => ['required', 'string', Rule::in(WebOfficialArticleCategory::values())],
            'excerpt' => ['required', 'string', 'min:10', 'max:500'],
            'body' => ['required', 'string', 'min:20'],
            'cover' => ['nullable', 'string', 'url', 'max:500', 'required_without:cover_file'],
            'cover_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048', 'required_without:cover'],
            'valid_until' => [
                Rule::requiredIf(fn (): bool => $this->input('category') === WebOfficialArticleCategory::Promo->value),
                'nullable',
                'date',
            ],
            'status' => ['nullable', 'string', Rule::in(WebOfficialArticleStatus::values())],
        ];
    }
}
