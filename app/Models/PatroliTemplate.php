<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatroliTemplate extends Model
{
    protected $table = 'patroli_template';

    protected $fillable = [
        'nama',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PatroliTemplateItem::class, 'patroli_template_id')->orderBy('urutan');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public function ruang(): HasMany
    {
        return $this->hasMany(PatroliRuang::class, 'patroli_template_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(PatroliCheckin::class, 'patroli_template_id');
    }
}
