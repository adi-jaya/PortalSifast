<?php

namespace App\Http\Controllers\WebOfficial;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebOfficial\StoreWebOfficialArticleWebRequest;
use App\Http\Requests\WebOfficial\UpdateWebOfficialArticleWebRequest;
use App\Models\WebOfficialArticle;
use App\Services\WebOfficial\WebOfficialArticlePersister;
use App\Services\WebOfficialMediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebOfficialArticleWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebOfficialArticle::query()->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search);
            });
        }

        $articles = $query->paginate(20)->withQueryString();

        return Inertia::render('web-official/articles/index', [
            'articles' => $articles->through(fn (WebOfficialArticle $article): array => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'category' => $article->category?->value,
                'status' => $article->status?->value,
                'published_at' => $article->published_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
            ]),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'category' => $request->string('category')->toString(),
            ],
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('web-official/articles/create', [
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(
        StoreWebOfficialArticleWebRequest $request,
        WebOfficialArticlePersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $article = new WebOfficialArticle;
        $validated = $request->validated();
        $persister->fill($article, $validated, isCreate: true);

        if ($request->hasFile('cover_file')) {
            $persister->applyCoverUpload($article, $request->file('cover_file'), $storage);
        }

        $article->applyPublishedTimestamp();
        $article->save();

        return redirect()
            ->route('web-official.articles.index')
            ->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function edit(WebOfficialArticle $article): Response
    {
        return Inertia::render('web-official/articles/edit', [
            'article' => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'category' => $article->category?->value,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'cover' => $article->cover_url,
                'valid_until' => $article->valid_until?->format('Y-m-d\TH:i'),
                'status' => $article->status?->value,
            ],
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(
        UpdateWebOfficialArticleWebRequest $request,
        WebOfficialArticle $article,
        WebOfficialArticlePersister $persister,
        WebOfficialMediaStorageService $storage,
    ): RedirectResponse {
        $validated = $request->validated();
        $persister->fill($article, $validated, isCreate: false);

        if ($request->hasFile('cover_file')) {
            $persister->applyCoverUpload($article, $request->file('cover_file'), $storage);
        }

        $article->applyPublishedTimestamp();
        $article->save();

        return redirect()
            ->route('web-official.articles.index')
            ->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(WebOfficialArticle $article): RedirectResponse
    {
        $article->status = WebOfficialArticleStatus::Archived;
        $article->save();

        return redirect()
            ->route('web-official.articles.index')
            ->with('success', 'Artikel berhasil diarsipkan.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(): array
    {
        return array_map(
            static fn (WebOfficialArticleCategory $category): array => [
                'value' => $category->value,
                'label' => $category->value,
            ],
            WebOfficialArticleCategory::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        $labels = [
            WebOfficialArticleStatus::Draft->value => 'Draft',
            WebOfficialArticleStatus::Published->value => 'Published',
            WebOfficialArticleStatus::Archived->value => 'Arsip',
        ];

        return array_map(
            static fn (WebOfficialArticleStatus $status): array => [
                'value' => $status->value,
                'label' => $labels[$status->value] ?? $status->value,
            ],
            WebOfficialArticleStatus::cases(),
        );
    }
}
