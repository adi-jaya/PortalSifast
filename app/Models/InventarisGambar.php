<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarisGambar extends Model
{
    protected $connection = 'dbsimrs';

    protected $table = 'inventaris_gambar';

    protected $primaryKey = 'no_inventaris';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_inventaris',
        'photo',
    ];

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'no_inventaris', 'no_inventaris');
    }

    public function getRouteKeyName(): string
    {
        return 'no_inventaris';
    }
}
