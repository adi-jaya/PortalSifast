<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetKategori extends Model
{
    protected $table = 'aset_kategori';

    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
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
        return $this->hasMany(AsetBarang::class, 'aset_kategori_id');
    }

    public function nonAlkes(): HasMany
    {
        return $this->hasMany(AsetNonAlkes::class, 'aset_kategori_id');
    }
}
