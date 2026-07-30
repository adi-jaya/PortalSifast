<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatroliArea extends Model
{
    protected $table = 'patroli_area';

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

    public function ruang(): HasMany
    {
        return $this->hasMany(PatroliRuang::class, 'patroli_area_id')->orderBy('nama');
    }

    public function activeRuang(): HasMany
    {
        return $this->ruang()->where('is_active', true);
    }
}
