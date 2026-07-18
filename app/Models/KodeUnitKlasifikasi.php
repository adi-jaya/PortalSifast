<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeUnitKlasifikasi extends Model
{
    protected $table = 'kode_unit_klasifikasi';

    protected $fillable = [
        'kode',
        'nama',
        'dep_id',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(CounterNomorDokumen::class);
    }
}
