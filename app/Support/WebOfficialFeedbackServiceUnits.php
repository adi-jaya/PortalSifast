<?php

namespace App\Support;

final class WebOfficialFeedbackServiceUnits
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'IGD (Unit Gawat Darurat)',
            'Rawat Inap',
            'Rawat Jalan / Poliklinik',
            'Farmasi',
            'Laboratorium',
            'Radiologi',
            'Administrasi & Pendaftaran',
            'Keuangan & Billing',
            'Kebersihan & Sanitasi',
            'Layanan Umum',
            'Lainnya',
        ];
    }
}
