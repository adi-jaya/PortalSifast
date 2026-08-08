<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterNomorDokumen extends Model
{
    protected $table = 'counter_nomor_dokumen';

    protected $fillable = [
        'kode_unit_klasifikasi_id',
        'tahun',
        'counter',
    ];

    public function unitKlasifikasi(): BelongsTo
    {
        return $this->belongsTo(KodeUnitKlasifikasi::class, 'kode_unit_klasifikasi_id');
    }
}
