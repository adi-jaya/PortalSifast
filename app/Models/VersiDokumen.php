<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersiDokumen extends Model
{
    protected $table = 'versi_dokumen';

    protected $fillable = [
        'dokumen_id',
        'nomor_versi',
        'nomor_revisi',
        'file_asli',
        'file_bernomor',
        'file_final',
        'hash_sha256',
        'ukuran_kb',
        'jumlah_halaman',
        'catatan_perubahan',
        'dibuat_oleh',
    ];

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function filePathForViewer(): ?string
    {
        return $this->file_final ?? $this->file_bernomor ?? $this->file_asli;
    }
}
