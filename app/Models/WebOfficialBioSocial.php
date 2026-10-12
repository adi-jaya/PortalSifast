<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebOfficialBioSocial extends Model
{
    public const PLATFORMS = ['instagram', 'facebook', 'youtube', 'tiktok'];

    protected $primaryKey = 'platform';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['platform', 'url', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(WebOfficialBioLink::PUBLIC_CACHE_KEY));
    }

    /**
     * Keempat platform dalam urutan tetap, termasuk yang belum ada barisnya.
     *
     * @return list<array{platform: string, url: string, isActive: bool}>
     */
    public static function adminList(): array
    {
        $rows = static::query()->get()->keyBy('platform');

        return array_map(fn (string $platform): array => [
            'platform' => $platform,
            'url' => (string) ($rows[$platform]->url ?? ''),
            'isActive' => (bool) ($rows[$platform]->is_active ?? false),
        ], self::PLATFORMS);
    }

    /**
     * @param  list<array{platform: string, url?: string|null, isActive?: bool|null}>  $socials
     */
    public static function saveAll(array $socials): void
    {
        foreach ($socials as $social) {
            static::query()->updateOrCreate(
                ['platform' => $social['platform']],
                ['url' => (string) ($social['url'] ?? ''), 'is_active' => (bool) ($social['isActive'] ?? false)],
            );
        }
    }
}
