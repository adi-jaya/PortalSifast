<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAsetItem extends Model
{
    protected $table = 'audit_aset_item';

    protected $fillable = [
        'audit_aset_id',
        'aset_id',
        'kode_aset',
        'hasil',
        'kondisi_aktual',
        'aset_ruang_ditemukan_id',
        'catatan',
        'path_foto_bukti',
        'dicek_oleh',
        'dicek_pada',
    ];

    protected function casts(): array
    {
        return [
            'dicek_pada' => 'datetime',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(AuditAset::class, 'audit_aset_id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function ruangDitemukan(): BelongsTo
    {
        return $this->belongsTo(AsetRuang::class, 'aset_ruang_ditemukan_id');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicek_oleh');
    }
}
