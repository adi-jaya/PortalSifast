<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebOfficialPartner extends Model
{
    /** @use HasFactory<\Database\Factories\WebOfficialPartnerFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'logo_url',
        'website_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<WebOfficialPartner>  $query
     * @return Builder<WebOfficialPartner>
     */
    public function scopeActivePublic(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name);
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
    public function toPublicArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'logo' => $this->logo_url,
            'websiteUrl' => $this->website_url,
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
            'name' => $this->name,
            'category' => $this->category,
            'logo' => $this->logo_url,
            'websiteUrl' => $this->website_url,
            'isActive' => $this->is_active,
            'sortOrder' => $this->sort_order,
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
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'logo' => $this->logo_url,
            'websiteUrl' => $this->website_url,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
