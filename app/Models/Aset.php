<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aset extends Model
{
    use SoftDeletes;

    protected $table = 'aset';

    protected $fillable = [
        'kode_aset',
        'no_simrs',
        'no_seri',
        'aset_barang_id',
        'kode_ruang_registrasi',
        'aset_ruang_id',
        'aset_distributor_id',
        'tahun_registrasi',
        'asal_barang',
        'tanggal_pengadaan',
        'harga',
        'status_sumber',
        'kondisi',
        'status_fungsi',
        'tingkat_kerusakan',
        'siklus_hidup',
        'status_ketersediaan',
        'penanggung_jawab_id',
        'path_foto_sumber',
        'hash_sumber',
        'disinkron_pada',
        'sumber_hilang_pada',
        'diverifikasi_pada',
        'diverifikasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengadaan' => 'date',
            'harga' => 'decimal:2',
            'tahun_registrasi' => 'integer',
            'disinkron_pada' => 'datetime',
            'sumber_hilang_pada' => 'datetime',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'kode_aset';
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(AsetBarang::class, 'aset_barang_id');
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(AsetRuang::class, 'aset_ruang_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(AsetDistributor::class, 'aset_distributor_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(AsetFoto::class, 'aset_id');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(AsetFoto::class, 'aset_id')->where('utama', true);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(AsetRiwayat::class, 'aset_id');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'asset_id');
    }

    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditAsetItem::class, 'aset_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(AsetPeminjaman::class, 'aset_id');
    }

    public function peminjamanAktif(): HasOne
    {
        return $this->hasOne(AsetPeminjaman::class, 'aset_id')
            ->where('status', 'dipinjam')
            ->latestOfMany();
    }

    public function mutasiLokasi(): HasMany
    {
        return $this->hasMany(AsetMutasiLokasi::class, 'aset_id');
    }
}
