<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('simmutu flag manager email can toggle simmutu flags on user update', function () {
    $managerEmail = 'admin@portalsifats.com';
    config()->set('auth.simmutu_flag_manager_emails', [mb_strtolower($managerEmail)]);
    config()->set('auth.superadmin_emails', []);

    $actor = User::factory()->admin()->create([
        'email' => $managerEmail,
    ]);
    $target = User::factory()->staff()->create([
        'can_manage_mutu' => false,
        'can_input_mutu' => false,
        'can_view_mutu_dashboard' => false,
    ]);

    actingAs($actor)
        ->from("/users/{$target->id}/edit")
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role' => $target->role,
            'dep_id' => $target->dep_id,
            'can_manage_mutu' => true,
            'can_input_mutu' => true,
            'can_view_mutu_dashboard' => true,
        ])
        ->assertRedirect();

    expect($actor->fresh()->canManageMutuAccess())->toBeTrue()
        ->and($target->fresh()->can_manage_mutu)->toBeTrue()
        ->and($target->fresh()->can_input_mutu)->toBeTrue()
        ->and($target->fresh()->can_view_mutu_dashboard)->toBeTrue();
});

test('superadmin can still toggle simmutu flags on user update', function () {
    $superadminEmail = 'superadmin+'.uniqid('', true).'@example.com';
    config()->set('auth.superadmin_emails', [mb_strtolower($superadminEmail)]);
    config()->set('auth.simmutu_flag_manager_emails', []);

    $actor = User::factory()->admin()->create([
        'email' => $superadminEmail,
    ]);
    $target = User::factory()->staff()->create([
        'can_manage_mutu' => false,
        'can_input_mutu' => false,
        'can_view_mutu_dashboard' => false,
    ]);

    actingAs($actor)
        ->from("/users/{$target->id}/edit")
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role' => $target->role,
            'dep_id' => $target->dep_id,
            'can_manage_mutu' => true,
            'can_input_mutu' => true,
            'can_view_mutu_dashboard' => true,
        ])
        ->assertRedirect();

    expect($target->fresh()->can_manage_mutu)->toBeTrue()
        ->and($target->fresh()->can_input_mutu)->toBeTrue()
        ->and($target->fresh()->can_view_mutu_dashboard)->toBeTrue();
});

test('admin without simmutu flag manager email cannot toggle simmutu flags', function () {
    config()->set('auth.superadmin_emails', []);
    config()->set('auth.simmutu_flag_manager_emails', ['admin@portalsifats.com']);

    $actor = User::factory()->admin()->create([
        'email' => 'other-admin@example.com',
    ]);
    $target = User::factory()->staff()->create([
        'can_manage_mutu' => false,
        'can_input_mutu' => false,
        'can_view_mutu_dashboard' => false,
    ]);

    actingAs($actor)
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'role' => $target->role,
            'dep_id' => $target->dep_id,
            'can_manage_mutu' => true,
            'can_input_mutu' => true,
            'can_view_mutu_dashboard' => true,
        ])
        ->assertRedirect();

    expect($actor->fresh()->canManageMutuAccess())->toBeFalse()
        ->and($target->fresh()->can_manage_mutu)->toBeFalse()
        ->and($target->fresh()->can_input_mutu)->toBeFalse()
        ->and($target->fresh()->can_view_mutu_dashboard)->toBeFalse();
});

test('edit user page exposes canManageMutuAccess for flag manager email', function () {
    $managerEmail = 'admin@portalsifats.com';
    config()->set('auth.simmutu_flag_manager_emails', [mb_strtolower($managerEmail)]);
    config()->set('auth.superadmin_emails', []);

    $actor = User::factory()->admin()->create([
        'email' => $managerEmail,
    ]);
    $target = User::factory()->staff()->create();

    actingAs($actor)
        ->get("/users/{$target->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/edit')
            ->where('canManageMutuAccess', true));
});
