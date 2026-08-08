<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetMerk extends Model
{
    protected $table = 'aset_merk';

    protected $fillable = [
        'kode_merk',
        'nama_merk',
        'hash_sumber',
        'disinkron_pada',
    ];

    protected function casts(): array
    {
        return [
            'disinkron_pada' => 'datetime',
        ];
    }

    public function barang(): HasMany
    {
        return $this->hasMany(AsetBarang::class, 'aset_merk_id');
    }

    public function jenis(): HasMany
    {
        return $this->hasMany(AsetJenis::class, 'aset_merk_id');
    }
}
