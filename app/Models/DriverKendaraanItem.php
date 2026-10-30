<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverKendaraanItem extends Model
{
    protected $table = 'driver_kendaraan_item';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_kendaraan_id',
        'driver_checklist_item_id',
        'berlaku',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berlaku' => 'boolean',
        ];
    }

    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(DriverKendaraan::class, 'driver_kendaraan_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(DriverChecklistItem::class, 'driver_checklist_item_id');
    }
}
