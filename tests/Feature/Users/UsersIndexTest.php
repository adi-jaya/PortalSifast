<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('users index includes web official access flags per user', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Admin Portal']);

    $staffWithAccess = User::factory()->staff()->create([
        'name' => 'Staff Humas',
        'can_manage_web_official' => true,
    ]);

    $staffWithoutAccess = User::factory()->staff()->create([
        'name' => 'Staff Umum',
        'can_manage_web_official' => false,
    ]);

    actingAs($admin)
        ->get('/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users.data', 3)
            ->where('users.data', function ($users) use ($staffWithAccess, $staffWithoutAccess): bool {
                $rows = collect($users);

                $withAccess = $rows->firstWhere('id', $staffWithAccess->id);
                $withoutAccess = $rows->firstWhere('id', $staffWithoutAccess->id);
                $adminRow = $rows->firstWhere('role', 'admin');

                return $withAccess['has_web_official_access'] === true
                    && $withAccess['can_manage_web_official'] === true
                    && $withoutAccess['has_web_official_access'] === false
                    && $withoutAccess['can_manage_web_official'] === false
                    && $adminRow['has_web_official_access'] === true;
            }));
});
