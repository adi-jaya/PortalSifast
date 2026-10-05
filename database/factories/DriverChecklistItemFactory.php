<?php

namespace Database\Factories;

use App\Models\DriverChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverChecklistItem>
 */
class DriverChecklistItemFactory extends Factory
{
    protected $model = DriverChecklistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
            'kategori' => 'umum',
            'urutan' => fake()->numberBetween(1, 50),
            'aktif' => true,
        ];
    }
}
