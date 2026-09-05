<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('staff with berkas kepegawaian flag can open index', function (): void {
    $user = User::factory()->staff()->create([
        'can_access_berkas_kepegawaian' => true,
    ]);

    actingAs($user)->get('/berkas-kepegawaian')->assertOk();
});

test('staff without berkas kepegawaian flag cannot open index', function (): void {
    $user = User::factory()->staff()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($user)->get('/berkas-kepegawaian')->assertForbidden();
});

test('admin can open berkas kepegawaian without explicit flag', function (): void {
    $admin = User::factory()->admin()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($admin)->get('/berkas-kepegawaian')->assertOk();
});

test('admin can assign berkas kepegawaian access flag when editing user', function (): void {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create([
        'can_access_berkas_kepegawaian' => false,
    ]);

    actingAs($admin)
        ->put("/users/{$staff->id}", [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'staff',
            'dep_id' => $staff->dep_id,
            'can_access_berkas_kepegawaian' => true,
        ])
        ->assertRedirect(route('users.index'));

    $staff->refresh();

    expect($staff->can_access_berkas_kepegawaian)->toBeTrue();
});
