<?php

namespace Database\Factories;

use App\Models\WebOfficialBioLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebOfficialBioLink>
 */
class WebOfficialBioLinkFactory extends Factory
{
    protected $model = WebOfficialBioLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(4),
            'url' => '/'.fake()->slug(2),
            'icon' => 'link',
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
            'featured' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
