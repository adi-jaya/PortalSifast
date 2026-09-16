<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\PortalAggregatorService;

beforeEach(function (): void {
    $this->service = new PortalAggregatorService;

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'email' => 'staff.aggregator@rsasf.co.id',
    ]);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin.aggregator@rsasf.co.id',
    ]);
});

it('returns only active portals where staff has active mapping', function (): void {
    $activePortalWithAccess = Portal::factory()->create([
        'name' => 'SIRIKA BKKBN',
        'category' => 'BKKBN',
        'is_active' => true,
    ]);

    $activePortalWithoutAccess = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'category' => 'Kemenkes',
        'is_active' => true,
    ]);

    $inactivePortalWithAccess = Portal::factory()->create([
        'name' => 'SITB Jatim Lama',
        'category' => 'Kemenkes',
        'is_active' => false,
    ]);

    // Active credential for active portal
    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $activePortalWithAccess->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    // Active credential for inactive portal (should be ignored)
    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $inactivePortalWithAccess->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $portals = $this->service->getUserPortals($this->staff);

    expect($portals)->toHaveCount(1)
        ->and($portals[0]['id'])->toBe($activePortalWithAccess->id)
        ->and($portals[0]['name'])->toBe('SIRIKA BKKBN')
        ->and($portals[0]['credential_type'])->toBe('use_shared');
});

it('excludes portals where staff mapping is inactive', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $portal->id,
        'is_active' => false,
    ]);

    $portals = $this->service->getUserPortals($this->staff);

    expect($portals)->toBeEmpty();
});

it('allows admin to see all active portals with fallback to shared credentials', function (): void {
    $sharedPortal = Portal::factory()->create([
        'name' => 'SIRS Online',
        'auth_type' => 'shared',
        'is_active' => true,
    ]);

    $bothPortal = Portal::factory()->create([
        'name' => 'SIGA BKKBN',
        'auth_type' => 'both',
        'is_active' => true,
    ]);

    $inactivePortal = Portal::factory()->create([
        'name' => 'Legacy Inactive',
        'is_active' => false,
    ]);

    $portals = $this->service->getUserPortals($this->admin);

    expect($portals)->toHaveCount(2)
        ->and(collect($portals)->pluck('name')->all())->toContain('SIRS Online', 'SIGA BKKBN')
        ->and(collect($portals)->pluck('name')->all())->not->toContain('Legacy Inactive')
        ->and($portals[0]['credential_type'])->toBe('use_shared');
});

it('uses explicit mapping credentials for admin if mapping exists', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'SIHA Personal',
        'auth_type' => 'personal',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->admin->id,
        'portal_id' => $portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'admin_personal_user',
        'personal_password' => 'AdminSecretPass123',
        'is_active' => true,
    ]);

    $portals = $this->service->getUserPortals($this->admin);

    expect($portals)->toHaveCount(1)
        ->and($portals[0]['credential_type'])->toBe('personal')
        ->and($portals[0]['personal_username'])->toBe('admin_personal_user')
        ->and($portals[0]['has_personal_credential'])->toBeTrue()
        ->and($portals[0]['can_configure_personal'])->toBeTrue();
});

it('formats portal card attributes with icon_url and zero password leakage', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'Mutu Fasyankes',
        'slug' => 'mutu-fasyankes',
        'category' => 'Mutu & Akreditasi',
        'url' => 'https://mutufasyankes.kemkes.go.id',
        'description' => 'Pelaporan mutu nasional',
        'icon_path' => 'portal-logos/mutu.png',
        'auth_type' => 'both',
        'shared_password' => 'ConfidentialPass123',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $portals = $this->service->getUserPortals($this->staff);

    expect($portals[0])->toMatchArray([
        'id' => $portal->id,
        'name' => 'Mutu Fasyankes',
        'slug' => 'mutu-fasyankes',
        'category' => 'Mutu & Akreditasi',
        'url' => 'https://mutufasyankes.kemkes.go.id',
        'icon_path' => 'portal-logos/mutu.png',
        'icon_url' => '/storage/portal-logos/mutu.png',
        'description' => 'Pelaporan mutu nasional',
        'auth_type' => 'both',
        'credential_type' => 'use_shared',
        'personal_username' => null,
        'has_personal_credential' => false,
        'can_configure_personal' => true,
    ]);

    // Ensure no password field is leaked in card payload
    expect(array_key_exists('shared_password', $portals[0]))->toBeFalse();
    expect(array_key_exists('personal_password', $portals[0]))->toBeFalse();
});

it('filters portals by category and search keyword', function (): void {
    $portal1 = Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes', 'is_active' => true]);
    $portal2 = Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN', 'is_active' => true]);
    $portal3 = Portal::factory()->create(['name' => 'SITB Tuberkulosis', 'category' => 'Kemenkes', 'is_active' => true]);

    foreach ([$portal1, $portal2, $portal3] as $portal) {
        UserPortalCredential::factory()->create([
            'user_id' => $this->staff->id,
            'portal_id' => $portal->id,
            'is_active' => true,
        ]);
    }

    // Filter category
    $kemenkesPortals = $this->service->getUserPortals($this->staff, category: 'Kemenkes');
    expect($kemenkesPortals)->toHaveCount(2)
        ->and(collect($kemenkesPortals)->pluck('name')->all())->toEqualCanonicalizing(['SIRS Online', 'SITB Tuberkulosis']);

    // Filter search keyword
    $searchPortals = $this->service->getUserPortals($this->staff, search: 'Tuberkulosis');
    expect($searchPortals)->toHaveCount(1)
        ->and($searchPortals[0]['name'])->toBe('SITB Tuberkulosis');
});

it('returns unique, deduplicated, sorted list of categories for user portals', function (): void {
    $p1 = Portal::factory()->create(['category' => 'Mutu & Akreditasi', 'is_active' => true]);
    $p2 = Portal::factory()->create(['category' => 'Kemenkes', 'is_active' => true]);
    $p3 = Portal::factory()->create(['category' => 'Kemenkes', 'is_active' => true]);

    foreach ([$p1, $p2, $p3] as $p) {
        UserPortalCredential::factory()->create([
            'user_id' => $this->staff->id,
            'portal_id' => $p->id,
            'is_active' => true,
        ]);
    }

    $categories = $this->service->getCategoriesForUser($this->staff);

    expect($categories)->toBe(['Kemenkes', 'Mutu & Akreditasi']);
});

it('allows superadmin defined in config to access all active portals', function (): void {
    config(['auth.superadmin_emails' => ['superadmin.portal@rsasf.co.id']]);

    $superadmin = User::factory()->create([
        'role' => 'staff',
        'email' => 'superadmin.portal@rsasf.co.id',
    ]);

    Portal::factory()->create(['name' => 'Portal A', 'is_active' => true]);
    Portal::factory()->create(['name' => 'Portal B Inactive', 'is_active' => false]);

    $portals = $this->service->getUserPortals($superadmin);

    expect($portals)->toHaveCount(1)
        ->and($portals[0]['name'])->toBe('Portal A');
});
