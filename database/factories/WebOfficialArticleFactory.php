<?php

namespace Database\Factories;

use App\Enums\WebOfficialArticleCategory;
use App\Enums\WebOfficialArticleStatus;
use App\Models\WebOfficialArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebOfficialArticle>
 */
class WebOfficialArticleFactory extends Factory
{
    protected $model = WebOfficialArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'slug' => WebOfficialArticle::generateUniqueSlug($title),
            'title' => $title,
            'category' => fake()->randomElement(WebOfficialArticleCategory::cases()),
            'excerpt' => fake()->paragraph(1),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'cover_url' => 'https://cdn.rsasitifatimah.id/informasi/'.fake()->uuid().'.jpg',
            'valid_until' => null,
            'status' => WebOfficialArticleStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => WebOfficialArticleStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function promo(): static
    {
        return $this->state(fn (): array => [
            'category' => WebOfficialArticleCategory::Promo,
            'valid_until' => now()->addMonths(3),
        ]);
    }
}
