<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetAspakAlat extends Model
{
    protected $table = 'aset_aspak_alat';

    protected $fillable = [
        'id_alat_aspak',
        'nama_alat',
        'kode',
        'alat_code',
        'parent_id',
        'alat_path',
        'alat_ket',
        'sinonim',
        'wajib_kalibrasi',
        'durasi_kalibrasi_hari',
    ];

    protected function casts(): array
    {
        return [
            'wajib_kalibrasi' => 'boolean',
            'durasi_kalibrasi_hari' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function barang(): HasMany
    {
        return $this->hasMany(AsetBarang::class, 'aset_aspak_alat_id');
    }

    /**
     * Leaf = node yang tidak punya anak (boleh dipilih sebagai nama barang medis).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLeaf(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('nama_alat', 'like', $like)
                ->orWhere('kode', 'like', $like)
                ->orWhere('alat_code', 'like', $like)
                ->orWhere('sinonim', 'like', $like)
                ->orWhere('id_alat_aspak', 'like', $like);
        });
    }

    public function isLeaf(): bool
    {
        return ! $this->children()->exists();
    }
}
