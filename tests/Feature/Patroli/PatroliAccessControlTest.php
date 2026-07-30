<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('user with patroli access can open checkin page', function () {
    $user = User::factory()->create([
        'can_access_patroli' => true,
    ]);

    actingAs($user)->get('/patroli/checkin')->assertOk();
});

test('user without patroli access cannot open checkin page', function () {
    $user = User::factory()->create([
        'can_access_patroli' => false,
    ]);

    actingAs($user)->get('/patroli/checkin')->assertForbidden();
});

test('superadmin email bypasses patroli access flag', function () {
    $superadminEmail = 'superadmin+'.uniqid().'@example.com';
    config()->set('auth.superadmin_emails', [$superadminEmail]);

    $user = User::factory()->create([
        'email' => $superadminEmail,
        'can_access_patroli' => false,
    ]);

    actingAs($user)->get('/patroli/checkin')->assertOk();
});
