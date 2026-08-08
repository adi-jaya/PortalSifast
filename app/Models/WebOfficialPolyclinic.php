<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebOfficialPolyclinic extends Model
{
    /** @use HasFactory<\Database\Factories\WebOfficialPolyclinicFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'kd_poli',
        'slug',
        'label',
        'simrs_name',
        'name_override',
        'short_description',
        'long_description',
        'photo_url',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<WebOfficialPolyclinic>  $query
     * @return Builder<WebOfficialPolyclinic>
     */
    public function scopeActivePublic(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function displayName(): string
    {
        return filled($this->name_override) ? (string) $this->name_override : (string) $this->simrs_name;
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
    public function toPublicListArray(): array
    {
        return [
            'slug' => $this->slug,
            'kdPoli' => $this->kd_poli,
            'label' => $this->label,
            'name' => $this->displayName(),
            'shortDescription' => $this->short_description,
            'photo' => $this->photo_url,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $doctors
     * @return array<string, mixed>
     */
    public function toPublicDetailArray(array $doctors): array
    {
        return [
            ...$this->toPublicListArray(),
            'longDescription' => $this->long_description,
            'doctors' => $doctors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        return [
            'id' => $this->id,
            'kdPoli' => $this->kd_poli,
            'slug' => $this->slug,
            'label' => $this->label,
            'name' => $this->displayName(),
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
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
            'kdPoli' => $this->kd_poli,
            'slug' => $this->slug,
            'label' => $this->label,
            'simrsName' => $this->simrs_name,
            'nameOverride' => $this->name_override,
            'name' => $this->displayName(),
            'shortDescription' => $this->short_description,
            'longDescription' => $this->long_description,
            'photo' => $this->photo_url,
            'icon' => $this->icon,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
