<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetPeminjaman extends Model
{
    protected $table = 'aset_peminjaman';

    protected $fillable = [
        'nomor',
        'aset_id',
        'peminjam_user_id',
        'peminjam_nik',
        'diserahkan_oleh_user_id',
        'tanggal_pinjam',
        'tanggal_kembali_rencana',
        'tanggal_kembali_aktual',
        'diterima_kembali_oleh_user_id',
        'status',
        'catatan',
        'kondisi_kembali',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'datetime',
            'tanggal_kembali_rencana' => 'date',
            'tanggal_kembali_aktual' => 'datetime',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function peminjamUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peminjam_user_id');
    }

    public function diserahkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diserahkan_oleh_user_id');
    }

    public function diterimaKembaliOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_kembali_oleh_user_id');
    }

    public function isTerlambat(): bool
    {
        return $this->status === 'dipinjam'
            && $this->tanggal_kembali_rencana !== null
            && $this->tanggal_kembali_rencana->lt(now()->startOfDay());
    }

    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->where('status', 'dipinjam')
            ->whereNotNull('tanggal_kembali_rencana')
            ->whereDate('tanggal_kembali_rencana', '<', now()->toDateString());
    }

    public function scopeDipinjam(Builder $query): Builder
    {
        return $query->where('status', 'dipinjam');
    }
}
