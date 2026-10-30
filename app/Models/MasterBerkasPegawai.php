<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterBerkasPegawai extends Model
{
    protected $connection = 'dbsimrs';

    protected $table = 'master_berkas_pegawai';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama_berkas',
        'kategori',
        'no_urut',
    ];

    public function berkasPegawai(): HasMany
    {
        return $this->hasMany(BerkasPegawai::class, 'kode_berkas', 'kode');
    }
}
