<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasPegawai extends Model
{
    protected $connection = 'dbsimrs';

    protected $table = 'berkas_pegawai';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'nik',
        'tgl_uploud',
        'kode_berkas',
        'berkas',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'nik', 'nik');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterBerkasPegawai::class, 'kode_berkas', 'kode');
    }

    public function publicUrl(): ?string
    {
        $base = rtrim((string) config('services.berkas_pegawai.public_base_url'), '/');
        $path = ltrim((string) $this->berkas, '/');

        if ($base === '' || $path === '') {
            return null;
        }

        return $base.'/'.$path;
    }
}
