<?php

namespace App\Models;

use Database\Factories\WebOfficialBioLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebOfficialBioLink extends Model
{
    /** @use HasFactory<WebOfficialBioLinkFactory> */
    use HasFactory, HasUuids;

    public const PUBLIC_CACHE_KEY = 'webofficial.bio-page';

    public const MAX_ACTIVE = 20;

    /** Kunci ikon yang dikenali frontend website. */
    public const ICONS = [
        'whatsapp', 'phone', 'stethoscope', 'hospital', 'bed', 'star', 'message-square', 'calendar',
        'megaphone', 'heart-pulse', 'smartphone', 'file-text', 'briefcase', 'map-pin', 'globe', 'link',
    ];

    protected $fillable = [
        'title',
        'description',
        'url',
        'icon',
        'image_url',
        'featured',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::PUBLIC_CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Aktif dan berada di dalam periode tayang.
     *
     * @param  Builder<WebOfficialBioLink>  $query
     * @return Builder<WebOfficialBioLink>
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    public static function nextSortOrder(): int
    {
        return (int) static::query()->max('sort_order') + 1;
    }

    /**
     * Isi ulang sort_order mulai 1 sesuai urutan $ids. Tautan yang tidak dikirim ditaruh di belakang.
     *
     * @param  list<string>  $ids
     */
    public static function reorder(array $ids): void
    {
        $rest = static::query()->whereNotIn('id', $ids)
            ->orderBy('sort_order')->orderBy('created_at')
            ->pluck('id')->all();

        DB::transaction(function () use ($ids, $rest): void {
            foreach (array_values([...$ids, ...$rest]) as $i => $id) {
                static::query()->whereKey($id)->update(['sort_order' => $i + 1]);
            }
        });

        Cache::forget(self::PUBLIC_CACHE_KEY);
    }

    /**
     * Payload halaman /bio (di-cache 5 menit, dihapus otomatis saat admin menyimpan).
     *
     * @return array{links: list<array<string, mixed>>, socials: list<array{platform: string, url: string}>}
     */
    public static function publicPage(): array
    {
        return Cache::remember(self::PUBLIC_CACHE_KEY, 300, fn (): array => [
            'links' => static::query()->visible()
                ->orderBy('sort_order')->orderBy('created_at')
                ->get()->map->toPublicArray()->values()->all(),
            'socials' => WebOfficialBioSocial::query()
                ->where('is_active', true)->where('url', '!=', '')
                ->get()
                ->sortBy(fn (WebOfficialBioSocial $s) => array_search($s->platform, WebOfficialBioSocial::PLATFORMS, true))
                ->map(fn (WebOfficialBioSocial $s): array => ['platform' => $s->platform, 'url' => $s->url])
                ->values()->all(),
        ]);
    }

    /**
     * Petakan payload camelCase tervalidasi ke atribut kolom.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function attributesFrom(array $validated): array
    {
        $map = [
            'title' => 'title',
            'description' => 'description',
            'url' => 'url',
            'icon' => 'icon',
            'imageUrl' => 'image_url',
            'featured' => 'featured',
            'sortOrder' => 'sort_order',
            'isActive' => 'is_active',
            'startsAt' => 'starts_at',
            'endsAt' => 'ends_at',
        ];

        $attributes = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $attributes[$column] = $validated[$input];
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'icon' => $this->icon,
            'imageUrl' => $this->image_url,
            'featured' => $this->featured ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'icon' => $this->icon,
            'imageUrl' => $this->image_url,
            'featured' => $this->featured,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'startsAt' => $this->starts_at?->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
