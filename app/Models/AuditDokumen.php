<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditDokumen extends Model
{
    public $timestamps = false;

    protected $table = 'audit_dokumen';

    protected $fillable = [
        'dokumen_id',
        'user_id',
        'aksi',
        'status_lama',
        'status_baru',
        'catatan',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
