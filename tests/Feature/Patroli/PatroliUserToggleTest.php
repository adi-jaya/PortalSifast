<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('superadmin can toggle can_access_patroli on user update', function () {
    $superadminEmail = 'superadmin+'.uniqid().'@example.com';
    config()->set('auth.superadmin_emails', [mb_strtolower($superadminEmail)]);

    $actor = User::factory()->admin()->create([
        'email' => $superadminEmail,
    ]);
    $target = User::factory()->staff()->create([
        'can_access_patroli' => false,
    ]);

    actingAs($actor)
        ->from("/users/{$target->id}/edit")
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role' => $target->role,
            'dep_id' => $target->dep_id,
            'can_access_patroli' => true,
        ])
        ->assertRedirect();

    expect($actor->fresh()->canManagePatroliAccess())->toBeTrue()
        ->and($target->fresh()->can_access_patroli)->toBeTrue();
});

test('non superadmin cannot toggle can_access_patroli', function () {
    $actor = User::factory()->admin()->create([
        'can_access_patroli' => false,
    ]);
    $target = User::factory()->staff()->create([
        'can_access_patroli' => false,
    ]);

    actingAs($actor)
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role' => $target->role,
            'dep_id' => $target->dep_id,
            'can_access_patroli' => true,
        ])
        ->assertRedirect();

    expect($target->fresh()->can_access_patroli)->toBeFalse();
});
