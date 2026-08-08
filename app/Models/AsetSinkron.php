<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetSinkron extends Model
{
    protected $table = 'aset_sinkron';

    protected $fillable = [
        'dipicu_oleh',
        'status',
        'mulai_pada',
        'selesai_pada',
        'jumlah_baru',
        'jumlah_berubah',
        'jumlah_sama',
        'jumlah_hilang',
        'jumlah_gagal',
        'ringkasan',
    ];

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'ringkasan' => 'array',
            'jumlah_baru' => 'integer',
            'jumlah_berubah' => 'integer',
            'jumlah_sama' => 'integer',
            'jumlah_hilang' => 'integer',
            'jumlah_gagal' => 'integer',
        ];
    }

    public function pemicu(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipicu_oleh');
    }
}
