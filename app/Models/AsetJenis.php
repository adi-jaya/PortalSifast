<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetJenis extends Model
{
    protected $table = 'aset_jenis';

    protected $fillable = [
        'kode_jenis',
        'nama_jenis',
        'aset_merk_id',
        'hash_sumber',
        'disinkron_pada',
    ];

    protected function casts(): array
    {
        return [
            'disinkron_pada' => 'datetime',
        ];
    }

    public function merk(): BelongsTo
    {
        return $this->belongsTo(AsetMerk::class, 'aset_merk_id');
    }

    public function barang(): HasMany
    {
        return $this->hasMany(AsetBarang::class, 'aset_jenis_id');
    }
}
