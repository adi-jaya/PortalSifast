<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetCounterNomor extends Model
{
    protected $table = 'aset_counter_nomor';

    protected $fillable = [
        'awalan',
        'kode_ruang',
        'tahun',
        'terakhir',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'terakhir' => 'integer',
        ];
    }
}
