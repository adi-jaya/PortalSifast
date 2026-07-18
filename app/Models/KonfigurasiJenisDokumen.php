<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KonfigurasiJenisDokumen extends Model
{
    protected $table = 'konfigurasi_jenis_dokumen';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'deskripsi',
        'format_nomor',
        'prefix_kode_rs',
        'halaman_inject',
        'posisi_x',
        'posisi_y',
        'ukuran_font',
        'alignment',
        'varian_kop',
        'tipe_workflow',
        'butuh_tte_direktur',
        'pakai_salam_islami',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'butuh_tte_direktur' => 'boolean',
            'pakai_salam_islami' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'kode_jenis', 'kode');
    }
}
