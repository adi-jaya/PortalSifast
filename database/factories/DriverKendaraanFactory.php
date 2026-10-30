<?php

namespace Database\Factories;

use App\Models\DriverKendaraan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverKendaraan>
 */
class DriverKendaraanFactory extends Factory
{
    protected $model = DriverKendaraan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->randomElement(['AMB APV', 'AMB HIACE', 'ERTIGA', 'INNOVA']).' '.fake()->unique()->numerify('##'),
            'no_polisi' => 'W '.fake()->numerify('####').' XX',
            'merk' => 'Toyota',
            'model' => fake()->word(),
            'tahun' => fake()->numberBetween(2018, 2026),
            'status' => DriverKendaraan::STATUS_AKTIF,
        ];
    }
}
