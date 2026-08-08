<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatroliRuang extends Model
{
    protected $table = 'patroli_ruang';

    protected $fillable = [
        'patroli_area_id',
        'kode',
        'nama',
        'patroli_template_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(PatroliArea::class, 'patroli_area_id');
    }

    public function patroliTemplate(): BelongsTo
    {
        return $this->belongsTo(PatroliTemplate::class, 'patroli_template_id');
    }

    public function patroliCheckins(): HasMany
    {
        return $this->hasMany(PatroliCheckin::class, 'patroli_ruang_id');
    }
}
