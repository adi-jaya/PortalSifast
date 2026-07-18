<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHardware extends Model
{
    protected $table = 'device_hardware';

    protected $fillable = [
        'monitored_device_id',
        'os',
        'os_version',
        'architecture',
        'cpu_model',
        'cpu_cores',
        'ram_total_mb',
        'disk_total_gb',
        'manufacturer',
        'model',
        'serial_number',
        'motherboard',
        'bios',
        'boot_time',
        'timezone',
        'domain',
        'username',
    ];

    protected function casts(): array
    {
        return [
            'boot_time' => 'datetime',
            'cpu_cores' => 'integer',
            'ram_total_mb' => 'integer',
            'disk_total_gb' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitoredDevice::class, 'monitored_device_id');
    }
}
