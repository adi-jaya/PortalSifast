<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetPengaturan extends Model
{
    protected $table = 'aset_pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
    ];
}
