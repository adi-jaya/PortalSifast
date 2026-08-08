<?php

namespace Database\Factories;

use App\Enums\WebOfficialFeedbackStatus;
use App\Models\WebOfficialFeedback;
use App\Support\WebOfficialFeedbackServiceUnits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebOfficialFeedback>
 */
class WebOfficialFeedbackFactory extends Factory
{
    protected $model = WebOfficialFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'service_unit' => fake()->randomElement(WebOfficialFeedbackServiceUnits::all()),
            'rating' => fake()->numberBetween(1, 5),
            'message' => fake()->paragraph(),
            'status' => WebOfficialFeedbackStatus::New,
            'source' => 'website-official',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'admin_notes' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WebOfficialFeedbackStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WebOfficialFeedbackStatus::Archived,
        ]);
    }
}
