<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetDokumen extends Model
{
    public const TIPE = [
        'kontrak',
        'ba_penerimaan',
        'akl_akd',
        'ba_uji_fungsi',
        'ba_uji_coba',
        'manual',
        'pendukung',
        'lainnya',
    ];

    public const LINGKUP_UNIT = 'unit';

    public const LINGKUP_BARANG = 'barang';

    protected $table = 'aset_dokumen';

    protected $fillable = [
        'judul',
        'tipe',
        'lingkup',
        'aset_id',
        'aset_barang_id',
        'path',
        'nama_asli',
        'mime',
        'ukuran',
        'diunggah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(AsetBarang::class, 'aset_barang_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function labelTipe(): string
    {
        return match ($this->tipe) {
            'kontrak' => 'Kontrak',
            'ba_penerimaan' => 'BA. Penerimaan',
            'akl_akd' => 'Doc AKD/AKL',
            'ba_uji_fungsi' => 'BA Uji Fungsi',
            'ba_uji_coba' => 'BA Uji Coba',
            'manual' => 'Buku panduan / Manual',
            'pendukung' => 'File pendukung',
            default => 'Lainnya',
        };
    }
}
