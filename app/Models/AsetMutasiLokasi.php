<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetMutasiLokasi extends Model
{
    protected $table = 'aset_mutasi_lokasi';

    protected $fillable = [
        'nomor',
        'aset_id',
        'aset_ruang_asal_id',
        'aset_ruang_tujuan_id',
        'penerima_user_id',
        'penerima_nik',
        'dicatat_oleh_user_id',
        'tanggal_mutasi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mutasi' => 'datetime',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function ruangAsal(): BelongsTo
    {
        return $this->belongsTo(AsetRuang::class, 'aset_ruang_asal_id');
    }

    public function ruangTujuan(): BelongsTo
    {
        return $this->belongsTo(AsetRuang::class, 'aset_ruang_tujuan_id');
    }

    public function penerimaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_user_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh_user_id');
    }
}
