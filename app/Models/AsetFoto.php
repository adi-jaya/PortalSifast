<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetFoto extends Model
{
    protected $table = 'aset_foto';

    protected $fillable = [
        'aset_id',
        'path',
        'utama',
        'diunggah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'utama' => 'boolean',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
