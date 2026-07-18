<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetRuang extends Model
{
    protected $table = 'aset_ruang';

    protected $fillable = [
        'kode_ruang',
        'nama_ruang',
        'sumber_hilang_pada',
    ];

    protected function casts(): array
    {
        return [
            'sumber_hilang_pada' => 'datetime',
        ];
    }

    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'aset_ruang_id');
    }
}
