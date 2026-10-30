<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPemeriksaanDetail extends Model
{
    protected $table = 'driver_pemeriksaan_detail';

    public const HASIL_LAYAK = 'layak';

    public const HASIL_TIDAK_LAYAK = 'tidak_layak';

    public const HASIL_NA = 'na';

    /**
     * @var list<string>
     */
    public const HASILS = [
        self::HASIL_LAYAK,
        self::HASIL_TIDAK_LAYAK,
        self::HASIL_NA,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_pemeriksaan_id',
        'driver_checklist_item_id',
        'hasil',
        'temuan',
        'rekomendasi',
        'keterangan',
    ];

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(DriverPemeriksaan::class, 'driver_pemeriksaan_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(DriverChecklistItem::class, 'driver_checklist_item_id');
    }
}
