<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistribusiDokumen extends Model
{
    protected $table = 'distribusi_dokumen';

    protected $fillable = [
        'dokumen_id',
        'dep_id',
        'dikirim_oleh',
        'dikirim_pada',
        'diterima_oleh',
        'diterima_pada',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'dikirim_pada' => 'datetime',
            'diterima_pada' => 'datetime',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }
}
