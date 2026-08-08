<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MonitoredDevice extends Model
{
    /** @use HasFactory<\Database\Factories\MonitoredDeviceFactory> */
    use HasFactory;

    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'uuid',
        'hostname',
        'computer_name',
        'ip_address',
        'mac_address',
        'api_key_prefix',
        'api_key_hash',
        'agent_version',
        'status',
        'last_seen_at',
        'last_cpu_percent',
        'last_ram_percent',
        'last_disk_percent',
        'uptime_seconds',
        'critical_software',
        'usb_inventory',
        'sensors',
        'aset_id',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_cpu_percent' => 'decimal:2',
            'last_ram_percent' => 'decimal:2',
            'last_disk_percent' => 'decimal:2',
            'uptime_seconds' => 'integer',
            'critical_software' => 'array',
            'usb_inventory' => 'array',
            'sensors' => 'array',
        ];
    }

    public function hardware(): HasOne
    {
        return $this->hasOne(DeviceHardware::class);
    }

    public function metricSamples(): HasMany
    {
        return $this->hasMany(DeviceMetricSample::class);
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }
}
