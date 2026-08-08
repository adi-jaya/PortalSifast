<?php

namespace App\Models;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebOfficialArticle extends Model
{
    /** @use HasFactory<\Database\Factories\WebOfficialArticleFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'category',
        'excerpt',
        'body',
        'cover_url',
        'valid_until',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
            'published_at' => 'datetime',
            'category' => WebOfficialArticleCategory::class,
            'status' => WebOfficialArticleStatus::class,
        ];
    }

    /**
     * @param  Builder<WebOfficialArticle>  $query
     * @return Builder<WebOfficialArticle>
     */
    public function scopePublishedPublic(Builder $query): Builder
    {
        return $query
            ->where('status', WebOfficialArticleStatus::Published)
            ->where(function (Builder $builder): void {
                $builder
                    ->where('category', '!=', WebOfficialArticleCategory::Promo->value)
                    ->orWhereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public static function generateUniqueSlug(string $title, ?string $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function applyPublishedTimestamp(): void
    {
        if ($this->status === WebOfficialArticleStatus::Published && $this->published_at === null) {
            $this->published_at = now();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicListArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->value,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover_url,
            'date' => $this->published_at?->toIso8601String(),
            'validUntil' => $this->valid_until?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicDetailArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->value,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'cover' => $this->cover_url,
            'date' => $this->published_at?->toIso8601String(),
            'validUntil' => $this->valid_until?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->value,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover_url,
            'status' => $this->status?->value,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminDetailArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->value,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'cover' => $this->cover_url,
            'date' => $this->published_at?->toIso8601String(),
            'validUntil' => $this->valid_until?->toIso8601String(),
            'status' => $this->status?->value,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
