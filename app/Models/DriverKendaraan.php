<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverKendaraan extends Model
{
    /** @use HasFactory<\Database\Factories\DriverKendaraanFactory> */
    use HasFactory;

    protected $table = 'driver_kendaraan';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'no_polisi',
        'merk',
        'model',
        'tahun',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function checklistItems(): BelongsToMany
    {
        return $this->belongsToMany(
            DriverChecklistItem::class,
            'driver_kendaraan_item',
            'driver_kendaraan_id',
            'driver_checklist_item_id',
        )->withPivot(['berlaku'])->withTimestamps();
    }

    public function kendaraanItems(): HasMany
    {
        return $this->hasMany(DriverKendaraanItem::class, 'driver_kendaraan_id');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(DriverPemeriksaan::class, 'driver_kendaraan_id');
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }
}
