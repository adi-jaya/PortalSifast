<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetRiwayat extends Model
{
    public $timestamps = false;

    protected $table = 'aset_riwayat';

    protected $fillable = [
        'aset_id',
        'pengguna_id',
        'jenis_peristiwa',
        'nilai_lama',
        'nilai_baru',
        'keterangan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'nilai_lama' => 'array',
            'nilai_baru' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
