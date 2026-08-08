<?php

namespace App\Services\Inventaris;

class PemetaanStatusAset
{
    /**
     * @return array{kondisi: string, status_fungsi: string, tingkat_kerusakan: string}
     */
    public static function dariStatusSimrs(?string $status): array
    {
        return match ($status) {
            'Ada' => [
                'kondisi' => 'Ada',
                'status_fungsi' => 'berfungsi',
                'tingkat_kerusakan' => 'baik',
            ],
            'Rusak' => [
                'kondisi' => 'Rusak',
                'status_fungsi' => 'tidak_berfungsi',
                'tingkat_kerusakan' => 'rusak_berat',
            ],
            'Hilang' => [
                'kondisi' => 'Hilang',
                'status_fungsi' => 'tidak_berfungsi',
                'tingkat_kerusakan' => 'baik',
            ],
            'Perbaikan' => [
                'kondisi' => 'Perbaikan',
                'status_fungsi' => 'tidak_berfungsi',
                'tingkat_kerusakan' => 'rusak_ringan',
            ],
            'Dipinjam' => [
                'kondisi' => 'Dipinjam',
                'status_fungsi' => 'berfungsi',
                'tingkat_kerusakan' => 'baik',
            ],
            default => [
                'kondisi' => $status ?: 'Ada',
                'status_fungsi' => 'berfungsi',
                'tingkat_kerusakan' => 'baik',
            ],
        };
    }

    /**
     * @return array{kondisi: string, status_fungsi: string, tingkat_kerusakan: string}
     */
    public static function dariInputForm(?string $statusFungsi, ?string $tingkatKerusakan): array
    {
        $fungsi = $statusFungsi ?: 'berfungsi';
        $kerusakan = $tingkatKerusakan ?: 'baik';

        $kondisi = match (true) {
            $fungsi === 'tidak_berfungsi' && $kerusakan === 'rusak_berat' => 'Rusak',
            $fungsi === 'tidak_berfungsi' => 'Perbaikan',
            $kerusakan === 'rusak_ringan' => 'Perbaikan',
            default => 'Ada',
        };

        return [
            'kondisi' => $kondisi,
            'status_fungsi' => $fungsi,
            'tingkat_kerusakan' => $kerusakan,
        ];
    }
}
