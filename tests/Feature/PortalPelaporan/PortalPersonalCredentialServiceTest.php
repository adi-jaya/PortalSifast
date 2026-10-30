<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\PortalPersonalCredentialService;

beforeEach(function (): void {
    $this->service = new PortalPersonalCredentialService;
    $this->user = User::factory()->staff()->create();
    $this->portal = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'slug' => 'mpdn-kemenkes',
        'auth_type' => 'both',
        'is_active' => true,
    ]);
});

it('updates personal username and password via service', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $updated = $this->service->updatePersonalCredential($this->user, $this->portal, [
        'username' => 'dr.fatimah@rsasf.co.id',
        'password' => 'PasswordBaruDokter#2026',
    ]);

    expect($updated->credential_type)->toBe('personal')
        ->and($updated->personal_username)->toBe('dr.fatimah@rsasf.co.id')
        ->and($updated->personal_password)->toBe('PasswordBaruDokter#2026');
});

it('preserves existing password when password is not supplied', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'dr.lama',
        'personal_password' => 'PasswordTetapAda',
        'is_active' => true,
    ]);

    $updated = $this->service->updatePersonalCredential($this->user, $this->portal, [
        'username' => 'dr.baru@rsasf.co.id',
        'password' => null,
    ]);

    expect($updated->personal_username)->toBe('dr.baru@rsasf.co.id')
        ->and($updated->personal_password)->toBe('PasswordTetapAda');
});
