<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractSimrsRiwayat extends Model
{
    protected $connection = 'dbsimrs';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    public static function publicBerkasUrl(?string $path): ?string
    {
        $base = rtrim((string) config('services.berkas_pegawai.public_base_url'), '/');
        $relative = trim(str_replace('\\', '/', (string) $path));
        $basename = basename($relative);

        if ($base === '' || $relative === '' || $basename === '' || $basename === 'berkas' || ! str_contains($basename, '.')) {
            return null;
        }

        return $base.'/'.ltrim($relative, '/');
    }
}
