<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatroliCheckin extends Model
{
    protected $table = 'patroli_checkin';

    protected $fillable = [
        'patroli_ruang_id',
        'user_id',
        'patroli_template_id',
        'checked_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(PatroliRuang::class, 'patroli_ruang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PatroliTemplate::class, 'patroli_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PatroliCheckinItem::class, 'patroli_checkin_id');
    }
}
