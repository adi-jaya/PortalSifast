<?php

namespace App\Http\Requests\Api\WebOfficial;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOfficialArticleRequest extends FormRequest
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
        $articleId = $this->route('article')?->id ?? $this->route('article');

        return [
            'title' => ['sometimes', 'string', 'min:5', 'max:300'],
            'slug' => [
                'sometimes',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('web_official_articles', 'slug')->ignore($articleId),
            ],
            'category' => ['sometimes', 'string', Rule::in(WebOfficialArticleCategory::values())],
            'excerpt' => ['sometimes', 'string', 'min:10', 'max:500'],
            'body' => ['sometimes', 'string', 'min:20'],
            'cover' => ['sometimes', 'string', 'url', 'max:500'],
            'validUntil' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(WebOfficialArticleStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug sudah digunakan.',
        ];
    }
}
