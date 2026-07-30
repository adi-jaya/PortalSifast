<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatroliCheckinItem extends Model
{
    public const STATUS_BERFUNGSI = 'berfungsi';

    public const STATUS_TIDAK_BERFUNGSI = 'tidak_berfungsi';

    public const STATUS_TIDAK_DICEK = 'tidak_dicek';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_BERFUNGSI,
        self::STATUS_TIDAK_BERFUNGSI,
        self::STATUS_TIDAK_DICEK,
    ];

    protected $table = 'patroli_checkin_item';

    protected $fillable = [
        'patroli_checkin_id',
        'patroli_template_item_id',
        'nama_item',
        'status',
    ];

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(PatroliCheckin::class, 'patroli_checkin_id');
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(PatroliTemplateItem::class, 'patroli_template_item_id');
    }
}
