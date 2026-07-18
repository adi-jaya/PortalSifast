<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetProdusen extends Model
{
    protected $table = 'aset_produsen';

    protected $fillable = [
        'kode_produsen',
        'nama_produsen',
        'alamat',
        'no_telp',
        'email',
        'website',
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
        return $this->hasMany(AsetBarang::class, 'aset_produsen_id');
    }
}
