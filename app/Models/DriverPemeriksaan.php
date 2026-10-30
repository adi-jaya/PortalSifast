<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverPemeriksaan extends Model
{
    /** @use HasFactory<\Database\Factories\DriverPemeriksaanFactory> */
    use HasFactory;

    protected $table = 'driver_pemeriksaan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_kendaraan_id',
        'petugas_id',
        'tanggal',
        'pemeriksaan_ke',
        'waktu_pemeriksaan',
        'status',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_pemeriksaan' => 'datetime',
            'pemeriksaan_ke' => 'integer',
        ];
    }

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(DriverKendaraan::class, 'driver_kendaraan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DriverPemeriksaanDetail::class, 'driver_pemeriksaan_id');
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    public function hasTemuanTidakLayak(): bool
    {
        return $this->details()
            ->where('hasil', DriverPemeriksaanDetail::HASIL_TIDAK_LAYAK)
            ->exists();
    }
}
