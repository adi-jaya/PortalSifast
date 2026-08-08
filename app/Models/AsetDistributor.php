<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetDistributor extends Model
{
    protected $table = 'aset_distributor';

    protected $fillable = [
        'kode_distributor',
        'nama_distributor',
        'alamat',
        'no_telp',
        'email',
        'hash_sumber',
        'disinkron_pada',
    ];

    protected function casts(): array
    {
        return [
            'disinkron_pada' => 'datetime',
        ];
    }

    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'aset_distributor_id');
    }
}
