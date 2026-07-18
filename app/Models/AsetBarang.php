<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsetBarang extends Model
{
    use SoftDeletes;

    protected $table = 'aset_barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'jumlah',
        'aset_kategori_id',
        'aset_jenis_id',
        'aset_merk_id',
        'aset_produsen_id',
        'aset_aspak_alat_id',
        'kode_produsen',
        'id_merk',
        'id_kategori',
        'id_jenis',
        'tahun_produksi',
        'isbn',
        'kelas_aset',
        'wajib_kalibrasi',
        'umur_ekonomis_bulan',
        'no_akl_akd',
        'daya_watt',
        'level_teknologi',
        'tahun_mulai_operasi',
        'nilai_residu',
        'hash_sumber',
        'disinkron_pada',
        'sumber_hilang_pada',
    ];

    protected function casts(): array
    {
        return [
            'wajib_kalibrasi' => 'boolean',
            'tahun_produksi' => 'integer',
            'tahun_mulai_operasi' => 'integer',
            'jumlah' => 'integer',
            'daya_watt' => 'integer',
            'umur_ekonomis_bulan' => 'integer',
            'nilai_residu' => 'decimal:2',
            'disinkron_pada' => 'datetime',
            'sumber_hilang_pada' => 'datetime',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(AsetKategori::class, 'aset_kategori_id');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(AsetJenis::class, 'aset_jenis_id');
    }

    public function merk(): BelongsTo
    {
        return $this->belongsTo(AsetMerk::class, 'aset_merk_id');
    }

    public function produsen(): BelongsTo
    {
        return $this->belongsTo(AsetProdusen::class, 'aset_produsen_id');
    }

    public function aspak(): BelongsTo
    {
        return $this->belongsTo(AsetAspakAlat::class, 'aset_aspak_alat_id');
    }

    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'aset_barang_id');
    }

    public static function hitungUlangJumlah(int $barangId): void
    {
        $count = Aset::query()
            ->where('aset_barang_id', $barangId)
            ->whereNotIn('siklus_hidup', ['dihapus', 'disposed'])
            ->count();

        self::query()->whereKey($barangId)->update(['jumlah' => $count]);
    }
}
