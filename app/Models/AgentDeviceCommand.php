<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDeviceCommand extends Model
{
    /** @use HasFactory<\Database\Factories\AgentDeviceCommandFactory> */
    use HasFactory;

    public const TYPE_LIST_WINDOWS = 'list_windows';

    public const TYPE_LIST_PROCESSES = 'list_processes';

    public const TYPE_KILL_PID = 'kill_pid';

    public const TYPE_CAPTURE_DESKTOP = 'capture_desktop';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_LIST_WINDOWS,
        self::TYPE_LIST_PROCESSES,
        self::TYPE_KILL_PID,
        self::TYPE_CAPTURE_DESKTOP,
    ];

    protected $fillable = [
        'monitored_device_id',
        'type',
        'payload',
        'status',
        'result',
        'created_by',
        'sent_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'sent_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MonitoredDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitoredDevice::class, 'monitored_device_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT], true);
    }
}
