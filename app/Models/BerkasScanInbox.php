<?php

namespace App\Models;

use Database\Factories\BerkasScanInboxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasScanInbox extends Model
{
    /** @use HasFactory<BerkasScanInboxFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    protected $table = 'berkas_scan_inbox';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'original_filename',
        'stored_path',
        'suggested_kode',
        'suggested_label',
        'confidence',
        'ocr_excerpt',
        'ocr_failed',
        'status',
        'nik',
        'confirmed_kode',
        'confirmed_by',
        'agent_label',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'ocr_failed' => 'boolean',
            'confirmed_by' => 'integer',
        ];
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isActionable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED], true);
    }
}
