<?php

namespace Database\Factories;

use App\Models\WebOfficialRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebOfficialRoom>
 */
class WebOfficialRoomFactory extends Factory
{
    protected $model = WebOfficialRoom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug' => WebOfficialRoom::generateUniqueSlug($name),
            'name' => ucwords($name),
            'tagline' => fake()->optional()->sentence(2),
            'badge' => fake()->optional()->word(),
            'description' => fake()->paragraph(2),
            'price' => fake()->numberBetween(200000, 3000000),
            'photo_url' => 'https://cdn.rsasitifatimah.id/kamar/'.fake()->uuid().'.jpg',
            'facilities' => ['AC', 'TV', 'WiFi', 'Kamar Mandi Dalam'],
            'sort_order' => WebOfficialRoom::nextSortOrder(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
