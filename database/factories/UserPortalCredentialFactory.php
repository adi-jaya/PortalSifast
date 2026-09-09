<?php

namespace Database\Factories;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPortalCredential>
 */
class UserPortalCredentialFactory extends Factory
{
    protected $model = UserPortalCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'portal_id' => Portal::factory(),
            'credential_type' => 'use_shared',
            'personal_username' => null,
            'personal_password' => null,
            'personal_extra_fields' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'credential_type' => 'personal',
            'personal_username' => fake()->userName(),
            'personal_password' => 'PersonalSecret123!',
        ]);
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'credential_type' => 'use_shared',
            'personal_username' => null,
            'personal_password' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
