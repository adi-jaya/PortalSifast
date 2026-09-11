<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Support\Facades\DB;

it('encrypts shared password on portal and decrypts on access', function (): void {
    $portal = Portal::create([
        'name' => 'SIRS Online',
        'slug' => 'sirs-online',
        'category' => 'Kemenkes',
        'url' => 'https://akun-yankes.kemkes.go.id/',
        'auth_type' => 'shared',
        'shared_username' => 'rs_user_sirs',
        'shared_password' => 'RahasiaRS2026!',
        'form_config' => ['is_spa' => true],
    ]);

    $rawRecord = DB::table('portals')->where('id', $portal->id)->first();
    expect($rawRecord->shared_password)->not->toBe('RahasiaRS2026!')
        ->and($portal->shared_password)->toBe('RahasiaRS2026!');
});

it('encrypts personal password on user_portal_credentials and decrypts on access', function (): void {
    $user = User::factory()->create();
    $portal = Portal::create([
        'name' => 'SATU SEHAT',
        'slug' => 'satusehat',
        'category' => 'Kemenkes',
        'url' => 'https://satusehat.kemkes.go.id/',
        'auth_type' => 'personal',
    ]);

    $credential = UserPortalCredential::create([
        'user_id' => $user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'dokter.andi@rsasf.co.id',
        'personal_password' => 'PribadiDokter#123',
    ]);

    $rawRecord = DB::table('user_portal_credentials')->where('id', $credential->id)->first();
    expect($rawRecord->personal_password)->not->toBe('PribadiDokter#123')
        ->and($credential->personal_password)->toBe('PribadiDokter#123');
});

it('supportsShared and supportsPersonal helpers work correctly based on auth_type', function (): void {
    $sharedPortal = new Portal(['auth_type' => 'shared']);
    expect($sharedPortal->supportsShared())->toBeTrue()
        ->and($sharedPortal->supportsPersonal())->toBeFalse();

    $personalPortal = new Portal(['auth_type' => 'personal']);
    expect($personalPortal->supportsShared())->toBeFalse()
        ->and($personalPortal->supportsPersonal())->toBeTrue();

    $bothPortal = new Portal(['auth_type' => 'both']);
    expect($bothPortal->supportsShared())->toBeTrue()
        ->and($bothPortal->supportsPersonal())->toBeTrue();
});

it('verifies relationship from User to portalCredentials and portals', function (): void {
    $user = User::factory()->create();
    $portal = Portal::create([
        'name' => 'MPDN',
        'slug' => 'mpdn',
        'category' => 'Kemenkes',
        'url' => 'https://mpdn.kemkes.go.id/masuk',
        'auth_type' => 'both',
    ]);

    UserPortalCredential::create([
        'user_id' => $user->id,
        'portal_id' => $portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    expect($user->portalCredentials)->toHaveCount(1)
        ->and($user->portalCredentials->first()->portal->id)->toBe($portal->id)
        ->and($user->portals)->toHaveCount(1)
        ->and($user->portals->first()->name)->toBe('MPDN')
        ->and(array_key_exists('personal_password', $user->portals->first()->pivot->getAttributes()))->toBeFalse();
});

it('creates portal and credential records using factories', function (): void {
    $portal = Portal::factory()->shared()->create();
    expect($portal->auth_type)->toBe('shared')
        ->and($portal->shared_username)->not->toBeEmpty();

    $credential = UserPortalCredential::factory()->personal()->create();
    expect($credential->credential_type)->toBe('personal')
        ->and($credential->personal_username)->not->toBeEmpty()
        ->and($credential->user)->not->toBeNull()
        ->and($credential->portal)->not->toBeNull();
});
