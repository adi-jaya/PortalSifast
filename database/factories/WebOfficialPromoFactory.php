<?php

namespace Database\Factories;

use App\Models\WebOfficialPromo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebOfficialPromo>
 */
class WebOfficialPromoFactory extends Factory
{
    protected $model = WebOfficialPromo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'slug' => WebOfficialPromo::generateUniqueSlug($title),
            'title' => $title,
            'label' => 'PROMO SPESIAL',
            'excerpt' => fake()->text(140),
            'body' => fake()->paragraphs(3, true),
            'cover_url' => 'https://cdn.rsasitifatimah.id/promosi/'.fake()->uuid().'.jpg',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_featured' => false,
            'sort_order' => WebOfficialPromo::nextSortOrder(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subDay(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }
}
