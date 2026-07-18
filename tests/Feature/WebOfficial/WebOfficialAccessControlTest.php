<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('staff with web official flag can open web official dashboard', function (): void {
    $user = User::factory()->staff()->create([
        'can_manage_web_official' => true,
    ]);

    actingAs($user)->get('/web-official')->assertOk();
});

test('staff without web official flag cannot open web official dashboard', function (): void {
    $user = User::factory()->staff()->create([
        'can_manage_web_official' => false,
    ]);

    actingAs($user)->get('/web-official')->assertForbidden();
});

test('admin can open web official dashboard without explicit flag', function (): void {
    $admin = User::factory()->admin()->create([
        'can_manage_web_official' => false,
    ]);

    actingAs($admin)->get('/web-official')->assertOk();
});

test('superadmin email bypasses web official access flag', function (): void {
    $superadminEmail = 'superadmin+'.uniqid().'@example.com';
    config()->set('auth.superadmin_emails', [$superadminEmail]);

    $user = User::factory()->staff()->create([
        'email' => $superadminEmail,
        'can_manage_web_official' => false,
    ]);

    actingAs($user)->get('/web-official')->assertOk();
});

test('admin can assign web official access when editing user', function (): void {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create([
        'can_manage_web_official' => false,
    ]);

    actingAs($admin)
        ->put("/users/{$staff->id}", [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'staff',
            'dep_id' => $staff->dep_id,
            'can_manage_web_official' => true,
        ])
        ->assertRedirect(route('users.index'));

    expect($staff->fresh()->can_manage_web_official)->toBeTrue();
});

test('staff cannot assign web official access when editing user', function (): void {
    $staffEditor = User::factory()->staff()->create([
        'can_manage_web_official' => true,
    ]);
    $target = User::factory()->staff()->create([
        'can_manage_web_official' => false,
    ]);

    actingAs($staffEditor)
        ->put("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'staff',
            'dep_id' => $target->dep_id,
            'can_manage_web_official' => true,
        ])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->can_manage_web_official)->toBeFalse();
});

test('login api returns can_manage_web_official for staff with flag', function (): void {
    $user = User::factory()->staff()->create([
        'can_manage_web_official' => true,
        'password' => bcrypt('password'),
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.can_manage_web_official', true);
});

test('login api returns can_manage_web_official false for staff without flag', function (): void {
    $user = User::factory()->staff()->create([
        'can_manage_web_official' => false,
        'password' => bcrypt('password'),
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.can_manage_web_official', false);
});
