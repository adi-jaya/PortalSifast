<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverChecklistItem extends Model
{
    /** @use HasFactory<\Database\Factories\DriverChecklistItemFactory> */
    use HasFactory;

    protected $table = 'driver_checklist_item';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'kategori',
        'urutan',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function kendaraan(): BelongsToMany
    {
        return $this->belongsToMany(
            DriverKendaraan::class,
            'driver_kendaraan_item',
            'driver_checklist_item_id',
            'driver_kendaraan_id',
        )->withPivot(['berlaku'])->withTimestamps();
    }

    public function kendaraanItems(): HasMany
    {
        return $this->hasMany(DriverKendaraanItem::class, 'driver_checklist_item_id');
    }
}
