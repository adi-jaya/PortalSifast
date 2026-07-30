<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatroliTemplateItem extends Model
{
    protected $table = 'patroli_template_item';

    protected $fillable = [
        'patroli_template_id',
        'nama',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PatroliTemplate::class, 'patroli_template_id');
    }

    public function checkinItems(): HasMany
    {
        return $this->hasMany(PatroliCheckinItem::class, 'patroli_template_item_id');
    }
}
