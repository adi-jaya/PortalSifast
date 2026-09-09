<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPortalCredential extends Model
{
    /** @use HasFactory<\Database\Factories\UserPortalCredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'portal_id',
        'credential_type',
        'personal_username',
        'personal_password',
        'personal_extra_fields',
        'is_active',
        'notes',
    ];

    protected $hidden = [
        'personal_password',
    ];

    protected function casts(): array
    {
        return [
            'personal_password' => 'encrypted',
            'personal_extra_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class, 'portal_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPersonal(): bool
    {
        return $this->credential_type === 'personal';
    }

    public function isShared(): bool
    {
        return $this->credential_type === 'use_shared';
    }
}
