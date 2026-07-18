<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaRegulasi extends Model
{
    protected $table = 'meta_regulasi';

    protected $primaryKey = 'dokumen_id';

    public $incrementing = false;

    protected $fillable = [
        'dokumen_id',
        'dasar_hukum',
        'menimbang',
        'mengingat',
        'diktum',
        'nomor_revisi',
        'keterangan_lampiran',
        'tags_clinical_pathway',
    ];

    protected function casts(): array
    {
        return [
            'tags_clinical_pathway' => 'array',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }
}
