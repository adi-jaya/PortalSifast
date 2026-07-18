<?php

namespace App\Http\Requests\Api\WebOfficial;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebOfficialArticleRequest extends FormRequest
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
            'cover' => ['required', 'string', 'url', 'max:500'],
            'validUntil' => [
                Rule::requiredIf(fn (): bool => $this->input('category') === WebOfficialArticleCategory::Promo->value),
                'nullable',
                'date',
            ],
            'status' => ['nullable', 'string', Rule::in(WebOfficialArticleStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'category.required' => 'Kategori wajib dipilih.',
            'excerpt.required' => 'Ringkasan wajib diisi.',
            'body.required' => 'Isi artikel wajib diisi.',
            'cover.required' => 'Cover wajib diisi.',
            'cover.url' => 'URL cover tidak valid.',
            'validUntil.required' => 'Tanggal berlaku wajib diisi untuk kategori Promo.',
            'slug.unique' => 'Slug sudah digunakan.',
        ];
    }
}
