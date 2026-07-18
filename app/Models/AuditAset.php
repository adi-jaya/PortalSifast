<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditAset extends Model
{
    protected $table = 'audit_aset';

    protected $fillable = [
        'aset_ruang_id',
        'judul',
        'status',
        'dimulai_oleh',
        'disetujui_oleh',
        'dimulai_pada',
        'selesai_pada',
        'disetujui_pada',
        'catatan',
        'ringkasan',
    ];

    protected function casts(): array
    {
        return [
            'dimulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
            'ringkasan' => 'array',
        ];
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(AsetRuang::class, 'aset_ruang_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditAsetItem::class, 'audit_aset_id');
    }

    public function pemula(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dimulai_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function masihBisaDiedit(): bool
    {
        return in_array($this->status, ['berjalan', 'selesai'], true);
    }

    public function hitungRingkasan(): array
    {
        $items = $this->items()->get(['hasil']);

        return [
            'total' => $items->count(),
            'belum_dicek' => $items->whereNull('hasil')->count(),
            'ditemukan' => $items->where('hasil', 'ditemukan')->count(),
            'tidak_ditemukan' => $items->where('hasil', 'tidak_ditemukan')->count(),
            'salah_ruang' => $items->where('hasil', 'salah_ruang')->count(),
        ];
    }
}
