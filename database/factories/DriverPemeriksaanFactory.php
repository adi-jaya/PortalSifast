<?php

namespace Database\Factories;

use App\Models\DriverKendaraan;
use App\Models\DriverPemeriksaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverPemeriksaan>
 */
class DriverPemeriksaanFactory extends Factory
{
    protected $model = DriverPemeriksaan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggal = now()->toDateString();

        return [
            'driver_kendaraan_id' => DriverKendaraan::factory(),
            'petugas_id' => User::factory(),
            'tanggal' => $tanggal,
            'pemeriksaan_ke' => 1,
            'waktu_pemeriksaan' => now(),
            'status' => DriverPemeriksaan::STATUS_SELESAI,
            'catatan' => null,
        ];
    }
}
