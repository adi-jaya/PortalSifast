<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMetricSample extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'monitored_device_id',
        'cpu_percent',
        'ram_percent',
        'disk_percent',
        'uptime_seconds',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_percent' => 'decimal:2',
            'ram_percent' => 'decimal:2',
            'disk_percent' => 'decimal:2',
            'uptime_seconds' => 'integer',
            'collected_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitoredDevice::class, 'monitored_device_id');
    }
}
