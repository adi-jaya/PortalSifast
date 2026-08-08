<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetNonAlkes extends Model
{
    protected $table = 'aset_non_alkes';

    protected $fillable = [
        'id_alat',
        'nama_alat',
        'alat_code',
        'kode',
        'parent_id',
        'aset_kategori_id',
        'level',
        'alat_path',
        'alat_ket',
        'sinonim',
        'deleted',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'deleted' => 'boolean',
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

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(AsetKategori::class, 'aset_kategori_id');
    }

    public function barang(): HasMany
    {
        return $this->hasMany(AsetBarang::class, 'aset_non_alkes_id');
    }

    /**
     * Leaf = node aktif yang tidak punya anak (boleh dipilih sebagai nama barang).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLeaf(Builder $query): Builder
    {
        return $query
            ->where('deleted', false)
            ->whereDoesntHave('children', fn (Builder $q) => $q->where('deleted', false));
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
                ->orWhere('id_alat', 'like', $like);
        });
    }

    public function isLeaf(): bool
    {
        if ($this->deleted) {
            return false;
        }

        return ! $this->children()->where('deleted', false)->exists();
    }

    /**
     * Kategori efektif: milik node ini, atau ancestor terdekat yang punya aset_kategori_id.
     */
    public function resolvedKategoriId(): ?int
    {
        $node = $this->resolvedKategoriNode();

        return $node?->aset_kategori_id ? (int) $node->aset_kategori_id : null;
    }

    public function resolvedKategoriNama(): ?string
    {
        $node = $this->resolvedKategoriNode();
        if ($node === null) {
            return null;
        }

        if ($node->relationLoaded('kategori')) {
            return $node->kategori?->nama_kategori;
        }

        return AsetKategori::query()->whereKey($node->aset_kategori_id)->value('nama_kategori');
    }

    private function resolvedKategoriNode(): ?self
    {
        $node = $this;
        $guard = 0;

        while ($node !== null && $guard < 20) {
            if ($node->aset_kategori_id) {
                return $node;
            }

            if (! $node->relationLoaded('parent')) {
                $node->load('parent');
            }

            $node = $node->parent;
            $guard++;
        }

        return null;
    }
}
