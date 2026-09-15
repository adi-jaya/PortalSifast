<?php

use App\Models\Portal;
use App\Services\Portal\AdminPortalService;

beforeEach(function (): void {
    $this->service = new AdminPortalService;
});

it('paginates and filters portals with category and search', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes']);
    Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN']);

    $result = $this->service->paginatePortals(['search' => 'SIRS']);
    expect($result['portals']->total())->toBe(1)
        ->and($result['portals']->first()['name'])->toBe('SIRS Online')
        ->and($result['categories'])->toContain('Kemenkes', 'BKKBN');
});

it('returns trimmed, non-empty, and deduplicated categories in getFormData and paginatePortals', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes']);
    Portal::factory()->create(['name' => 'SIHA 2', 'category' => ' Kemenkes ']);
    Portal::factory()->create(['name' => 'SITB Jatim', 'category' => 'kemenkes']);
    Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN']);
    Portal::factory()->create(['name' => 'New SIGA', 'category' => 'bkkbn']);

    $formData = $this->service->getFormData();
    $categories = $formData['categories']->values()->all();

    expect(count($categories))->toBe(2)
        ->and(array_map('strtolower', $categories))->toEqualCanonicalizing(['kemenkes', 'bkkbn']);
});

it('stores a portal with auto-slug and default sort_order and form_config', function (): void {
    $portal = $this->service->storePortal([
        'name' => 'SIGA Kemendukbangga',
        'category' => 'BKKBN',
        'url' => 'https://newsiga-siga.kemendukbangga.go.id/#/login',
        'auth_type' => 'shared',
        'shared_username' => 'siga_user',
        'shared_password' => 'Secret123!',
    ]);

    expect($portal->slug)->toBe('siga-kemendukbangga')
        ->and($portal->sort_order)->toBeGreaterThan(0)
        ->and($portal->form_config)->toBeArray()
        ->and($portal->shared_password)->toBe('Secret123!');
});

it('updates portal preserving existing shared_password when password input is empty', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'SIHA Lama',
        'shared_password' => 'OldPasswordSecret#',
    ]);

    $updated = $this->service->updatePortal($portal, [
        'name' => 'SIHA Baru',
        'shared_password' => '',
    ]);

    expect($updated->name)->toBe('SIHA Baru')
        ->and($updated->shared_password)->toBe('OldPasswordSecret#');
});

it('formats portal metadata for edit without leaking shared password plaintext', function (): void {
    $portal = Portal::factory()->create([
        'shared_password' => 'SecretPlaintext!',
    ]);

    $data = $this->service->formatPortalForEdit($portal);

    expect($data['has_shared_password'])->toBeTrue()
        ->and($data)->not->toHaveKey('shared_password');
});

it('toggles portal active status and deletes portal', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    $newStatus = $this->service->toggleActive($portal);
    expect($newStatus)->toBeFalse()
        ->and($portal->fresh()->is_active)->toBeFalse();

    expect($this->service->destroyPortal($portal))->toBeTrue();
    expect(Portal::find($portal->id))->toBeNull();
});
