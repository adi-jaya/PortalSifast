<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetAspakAlat extends Model
{
    protected $table = 'aset_aspak_alat';

    protected $fillable = [
        'id_alat_aspak',
        'nama_alat',
        'kode',
        'parent_id',
        'alat_path',
        'sinonim',
        'wajib_kalibrasi',
        'durasi_kalibrasi_hari',
    ];

    protected function casts(): array
    {
        return [
            'wajib_kalibrasi' => 'boolean',
            'durasi_kalibrasi_hari' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function barang(): HasMany
    {
        return $this->hasMany(AsetBarang::class, 'aset_aspak_alat_id');
    }
}
