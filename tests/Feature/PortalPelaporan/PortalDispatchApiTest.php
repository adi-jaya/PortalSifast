<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;

beforeEach(function (): void {
    $this->user = User::factory()->staff()->create();
    $this->portal = Portal::factory()->create([
        'name' => 'SIRIKA BKKBN',
        'slug' => 'sirika-bkkbn',
        'is_active' => true,
        'shared_username' => 'bkkbn_user',
        'shared_password' => 'SecretBkkbn#1',
    ]);
});

it('rejects unauthenticated requests with redirect/unauthorized', function (): void {
    $this->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertUnauthorized();
});

it('rejects user without mapping with 403 forbidden', function (): void {
    $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertForbidden();
});

it('successfully dispatches credentials to authorized user', function (): void {
    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('portal.slug', 'sirika-bkkbn')
        ->assertJsonPath('credentials.username', 'bkkbn_user')
        ->assertJsonPath('credentials.password', 'SecretBkkbn#1');
});

it('rejects dispatch token if portal is inactive with 403', function (): void {
    $this->portal->update(['is_active' => false]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertForbidden();
});
