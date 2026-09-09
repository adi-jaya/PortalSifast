<?php

namespace Database\Factories;

use App\Models\Portal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Portal>
 */
class PortalFactory extends Factory
{
    protected $model = Portal::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(['Kemenkes', 'BKKBN', 'Mutu & Akreditasi']),
            'url' => fake()->url(),
            'url_pattern' => null,
            'icon_path' => null,
            'description' => fake()->sentence(),
            'auth_type' => 'both',
            'shared_username' => 'rs_shared_user',
            'shared_password' => 'secret123',
            'shared_extra_fields' => null,
            'form_config' => [
                'is_spa' => false,
                'username_field' => ['selectors' => ['#username', "input[name='username']"]],
                'password_field' => ['selectors' => ['#password', "input[name='password']"]],
                'auto_submit' => false,
            ],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_type' => 'shared',
            'shared_username' => 'rs_instansi_account',
            'shared_password' => 'SharedPasswordRS!',
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_type' => 'personal',
            'shared_username' => null,
            'shared_password' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
