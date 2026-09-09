<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;

beforeEach(function (): void {
    $this->user = User::factory()->staff()->create();
    $this->portal = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'slug' => 'mpdn-kemenkes',
        'auth_type' => 'both',
        'is_active' => true,
    ]);
});

it('rejects unauthenticated user', function (): void {
    $this->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
        'username' => 'dr.fatimah',
        'password' => 'DokterPass123',
    ])->assertUnauthorized();
});

it('rejects user without mapping with 403 forbidden', function (): void {
    $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.fatimah',
            'password' => 'DokterPass123',
        ])->assertForbidden();
});

it('allows authorized user to update personal username and password', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.fatimah@rsasf.co.id',
            'password' => 'PasswordBaruDokter#2026',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $mapping->refresh();
    expect($mapping->credential_type)->toBe('personal')
        ->and($mapping->personal_username)->toBe('dr.fatimah@rsasf.co.id')
        ->and($mapping->personal_password)->toBe('PasswordBaruDokter#2026');
});

it('preserves existing password when updating username with null password', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'dr.lama',
        'personal_password' => 'PasswordTetapAda',
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.baru@rsasf.co.id',
            'password' => null,
        ])->assertOk();

    $mapping->refresh();
    expect($mapping->personal_username)->toBe('dr.baru@rsasf.co.id')
        ->and($mapping->personal_password)->toBe('PasswordTetapAda');
});
