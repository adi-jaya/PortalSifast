<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebOfficialPromo extends Model
{
    /** @use HasFactory<\Database\Factories\WebOfficialPromoFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'label',
        'excerpt',
        'body',
        'cover_url',
        'start_date',
        'end_date',
        'is_featured',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<WebOfficialPromo>  $query
     * @return Builder<WebOfficialPromo>
     */
    public function scopeActivePublic(Builder $query): Builder
    {
        $now = now('Asia/Jakarta');

        return $query
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public static function generateUniqueSlug(string $title, ?string $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public static function nextSortOrder(): int
    {
        return (int) (static::query()->max('sort_order') ?? 0) + 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicListArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'label' => $this->label,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover_url,
            'startDate' => $this->start_date?->toIso8601String(),
            'endDate' => $this->end_date?->toIso8601String(),
            'isFeatured' => $this->is_featured,
            'sortOrder' => $this->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicDetailArray(): array
    {
        return [
            ...$this->toPublicListArray(),
            'body' => $this->body,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        return [
            'id' => $this->id,
            ...$this->toPublicListArray(),
            'isActive' => $this->is_active,
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
            'label' => $this->label,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'cover' => $this->cover_url,
            'startDate' => $this->start_date?->toIso8601String(),
            'endDate' => $this->end_date?->toIso8601String(),
            'isFeatured' => $this->is_featured,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
