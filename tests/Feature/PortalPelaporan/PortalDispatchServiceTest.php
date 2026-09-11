<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\PortalDispatchService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function (): void {
    $this->service = new PortalDispatchService;
    $this->user = User::factory()->staff()->create();
});

it('dispatches shared credentials for user with use_shared mapping', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'SIRS Online',
        'slug' => 'sirs-online',
        'url' => 'https://akun-yankes.kemkes.go.id/',
        'auth_type' => 'shared',
        'shared_username' => 'rs_shared_sirs',
        'shared_password' => 'PlainSharedSecret123',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $payload = $this->service->dispatch($this->user, $portal);

    expect($payload['success'])->toBeTrue()
        ->and($payload['portal']['slug'])->toBe('sirs-online')
        ->and($payload['credentials']['type'])->toBe('shared')
        ->and($payload['credentials']['username'])->toBe('rs_shared_sirs')
        ->and($payload['credentials']['password'])->toBe('PlainSharedSecret123')
        ->and($payload)->toHaveKey('dispatched_at');
});

it('dispatches personal credentials when mapping is personal and portal supports personal', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'SATU SEHAT',
        'slug' => 'satusehat',
        'auth_type' => 'both',
        'shared_username' => 'rs_fallback',
        'shared_password' => 'fallback123',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'petugas.satusehat@rsasf.co.id',
        'personal_password' => 'PersonalSecret999!',
        'is_active' => true,
    ]);

    $payload = $this->service->dispatch($this->user, $portal);

    expect($payload['credentials']['type'])->toBe('personal')
        ->and($payload['credentials']['username'])->toBe('petugas.satusehat@rsasf.co.id')
        ->and($payload['credentials']['password'])->toBe('PersonalSecret999!');
});

it('throws AccessDeniedHttpException when user has no active mapping', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    $this->service->dispatch($this->user, $portal);
})->throws(AccessDeniedHttpException::class);

it('throws AccessDeniedHttpException when portal is inactive', function (): void {
    $portal = Portal::factory()->create(['is_active' => false]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $this->service->dispatch($this->user, $portal);
})->throws(AccessDeniedHttpException::class, 'Portal pelaporan ini sedang nonaktif.');

it('dispatches shared credentials for admin without explicit mapping', function (): void {
    $admin = User::factory()->admin()->create();
    $portal = Portal::factory()->create([
        'name' => 'SIRS Online',
        'slug' => 'sirs-online',
        'auth_type' => 'shared',
        'shared_username' => 'admin_shared',
        'shared_password' => 'AdminSecret123',
        'is_active' => true,
    ]);

    $payload = $this->service->dispatch($admin, $portal);

    expect($payload['success'])->toBeTrue()
        ->and($payload['credentials']['type'])->toBe('shared')
        ->and($payload['credentials']['username'])->toBe('admin_shared')
        ->and($payload['credentials']['password'])->toBe('AdminSecret123');
});

it('throws AccessDeniedHttpException when admin accesses strictly personal portal without personal credentials', function (): void {
    $admin = User::factory()->admin()->create();
    $portal = Portal::factory()->create([
        'name' => 'Personal Only Portal',
        'slug' => 'personal-only',
        'auth_type' => 'personal',
        'is_active' => true,
    ]);

    $this->service->dispatch($admin, $portal);
})->throws(AccessDeniedHttpException::class, 'Portal ini bertipe personal dan memerlukan konfigurasi akun personal.');

it('throws AccessDeniedHttpException when non-admin has use_shared mapping on strictly personal portal', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'Personal Only Portal',
        'slug' => 'personal-only-staff',
        'auth_type' => 'personal',
        'is_active' => true,
    ]);

    UserPortalCredential::create([
        'user_id' => $this->user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $this->service->dispatch($this->user, $portal);
})->throws(AccessDeniedHttpException::class, 'Portal ini bertipe personal dan memerlukan konfigurasi kredensial personal.');
