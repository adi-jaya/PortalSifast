<?php

namespace Database\Factories;

use App\Models\WebOfficialPolyclinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebOfficialPolyclinic>
 */
class WebOfficialPolyclinicFactory extends Factory
{
    protected $model = WebOfficialPolyclinic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Klinik '.fake()->words(2, true);

        return [
            'kd_poli' => strtoupper(fake()->bothify('??##')),
            'slug' => WebOfficialPolyclinic::generateUniqueSlug($name),
            'label' => 'KLINIK SPESIALIS',
            'simrs_name' => $name,
            'name_override' => null,
            'short_description' => fake()->sentence(12),
            'long_description' => fake()->paragraphs(2, true),
            'photo_url' => 'https://cdn.rsasitifatimah.id/poliklinik/'.fake()->uuid().'.jpg',
            'icon' => null,
            'sort_order' => WebOfficialPolyclinic::nextSortOrder(),
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
