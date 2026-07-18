<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeSifatNaskah extends Model
{
    protected $table = 'kode_sifat_naskah';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kode',
        'nama',
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
        return $this->hasMany(Dokumen::class, 'kode_sifat', 'kode');
    }
}
