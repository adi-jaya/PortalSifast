<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed akun admin awal untuk environment lokal/testing.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',
                'source' => 'manual',
                'email_verified_at' => now(),
            ]
        );
    }
}
