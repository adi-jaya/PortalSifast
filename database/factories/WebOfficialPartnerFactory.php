<?php

namespace Database\Factories;

use App\Models\WebOfficialPartner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebOfficialPartner>
 */
class WebOfficialPartnerFactory extends Factory
{
    protected $model = WebOfficialPartner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'name' => $name,
            'category' => fake()->randomElement(['Asuransi', 'Bank', 'Perusahaan', 'Lembaga']),
            'description' => fake()->optional()->sentence(),
            'logo_url' => 'https://cdn.rsasitifatimah.id/rekanan/'.Str::slug($name).'.png',
            'website_url' => fake()->optional()->url(),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
