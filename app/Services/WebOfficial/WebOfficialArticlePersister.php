<?php

namespace App\Services\WebOfficial;

use App\Enums\WebOfficialArticleStatus;
use App\Models\WebOfficialArticle;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\UploadedFile;

final class WebOfficialArticlePersister
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function fill(WebOfficialArticle $article, array $validated, bool $isCreate): void
    {
        if (array_key_exists('title', $validated)) {
            $article->title = $validated['title'];
        }

        if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
            $article->slug = $validated['slug'];
        } elseif ($isCreate && isset($validated['title'])) {
            $article->slug = WebOfficialArticle::generateUniqueSlug($validated['title']);
        } elseif (array_key_exists('title', $validated) && ! array_key_exists('slug', $validated)) {
            $article->slug = WebOfficialArticle::generateUniqueSlug($validated['title'], $article->id);
        }

        if (array_key_exists('category', $validated)) {
            $article->category = $validated['category'];
        }

        if (array_key_exists('excerpt', $validated)) {
            $article->excerpt = $validated['excerpt'];
        }

        if (array_key_exists('body', $validated)) {
            $article->body = $validated['body'];
        }

        if (array_key_exists('cover', $validated)) {
            $article->cover_url = $validated['cover'];
        }

        if (array_key_exists('valid_until', $validated)) {
            $article->valid_until = $validated['valid_until'];
        }

        if (array_key_exists('status', $validated)) {
            $article->status = $validated['status'];
        } elseif ($isCreate) {
            $article->status = WebOfficialArticleStatus::Draft;
        }
    }

    public function applyCoverUpload(WebOfficialArticle $article, UploadedFile $file, WebOfficialMediaStorageService $storage): void
    {
        $result = $storage->store($file, 'informasi');
        $article->cover_url = $result['url'];
    }
}
