<?php

namespace App\Services\Inventaris;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GeneratorKodeMasterAset
{
    public function generate(string $nama, Model $model, string $kodeColumn): string
    {
        $base = $this->baseFromNama($nama);
        $kode = $base;
        $suffix = 1;

        while ($model::query()->where($kodeColumn, $kode)->exists()) {
            $suffix++;
            $kode = substr($base, 0, max(1, 18 - strlen((string) $suffix))).$suffix;
        }

        return $kode;
    }

    private function baseFromNama(string $nama): string
    {
        $slug = strtoupper(Str::slug($nama, ''));
        if (strlen($slug) >= 2) {
            return substr($slug, 0, 20);
        }

        $alnum = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nama) ?? '');

        return substr($alnum !== '' ? $alnum : 'MR', 0, 20);
    }
}
