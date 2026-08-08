<?php

namespace App\Http\Requests\WebOfficial;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialArticleWebRequest extends FormRequest
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
        $articleId = $this->route('article')?->id;

        return [
            'title' => ['required', 'string', 'min:5', 'max:300'],
            'slug' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_articles', 'slug')->ignore($articleId),
            ],
            'category' => ['required', 'string', Rule::in(WebOfficialArticleCategory::values())],
            'excerpt' => ['required', 'string', 'min:10', 'max:500'],
            'body' => ['required', 'string', 'min:20'],
            'cover' => ['nullable', 'string', 'url', 'max:500'],
            'cover_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'valid_until' => [
                Rule::requiredIf(fn (): bool => $this->input('category') === WebOfficialArticleCategory::Promo->value),
                'nullable',
                'date',
            ],
            'status' => ['required', 'string', Rule::in(WebOfficialArticleStatus::values())],
        ];
    }
}
