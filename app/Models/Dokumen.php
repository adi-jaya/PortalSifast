<?php

namespace App\Models;

use App\Enums\DokumenStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokumen extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen';

    protected $fillable = [
        'nomor_dokumen',
        'judul',
        'kategori',
        'kode_jenis',
        'dep_id',
        'kode_unit_klasifikasi_id',
        'kode_sifat',
        'tingkat',
        'dibuat_oleh',
        'penandatangan_nik',
        'penandatangan_nama',
        'penandatangan_jabatan',
        'disetujui_oleh',
        'versi_saat_ini_id',
        'status',
        'tanggal_ditetapkan',
        'tanggal_hijriyah',
        'tanggal_berlaku',
        'tanggal_review',
        'tanggal_kadaluarsa',
        'diarsipkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'status' => DokumenStatus::class,
            'tanggal_ditetapkan' => 'date',
            'tanggal_berlaku' => 'date',
            'tanggal_review' => 'date',
            'tanggal_kadaluarsa' => 'datetime',
            'diarsipkan_pada' => 'datetime',
        ];
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(KonfigurasiJenisDokumen::class, 'kode_jenis', 'kode');
    }

    public function unitKlasifikasi(): BelongsTo
    {
        return $this->belongsTo(KodeUnitKlasifikasi::class, 'kode_unit_klasifikasi_id');
    }

    public function sifat(): BelongsTo
    {
        return $this->belongsTo(KodeSifatNaskah::class, 'kode_sifat', 'kode');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function versiSaatIni(): BelongsTo
    {
        return $this->belongsTo(VersiDokumen::class, 'versi_saat_ini_id');
    }

    public function versi(): HasMany
    {
        return $this->hasMany(VersiDokumen::class)->orderByDesc('nomor_versi');
    }

    public function metaRegulasi(): HasOne
    {
        return $this->hasOne(MetaRegulasi::class);
    }

    public function audit(): HasMany
    {
        return $this->hasMany(AuditDokumen::class)->orderByDesc('created_at');
    }

    public function distribusi(): HasMany
    {
        return $this->hasMany(DistribusiDokumen::class);
    }
}
