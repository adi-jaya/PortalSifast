<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portal extends Model
{
    /** @use HasFactory<\Database\Factories\PortalFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'url',
        'url_pattern',
        'icon_path',
        'description',
        'auth_type',
        'shared_username',
        'shared_password',
        'shared_extra_fields',
        'form_config',
        'is_active',
        'sort_order',
    ];

    protected $hidden = [
        'shared_password',
    ];

    protected function casts(): array
    {
        return [
            'shared_password' => 'encrypted',
            'shared_extra_fields' => 'array',
            'form_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function userCredentials(): HasMany
    {
        return $this->hasMany(UserPortalCredential::class, 'portal_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_portal_credentials', 'portal_id', 'user_id')
            ->withPivot(['id', 'credential_type', 'personal_username', 'personal_password', 'personal_extra_fields', 'is_active', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function supportsShared(): bool
    {
        return in_array($this->auth_type, ['shared', 'both'], true);
    }

    public function supportsPersonal(): bool
    {
        return in_array($this->auth_type, ['personal', 'both'], true);
    }
}
