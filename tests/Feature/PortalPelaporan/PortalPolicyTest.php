<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->staff = User::factory()->staff()->create();
    $this->otherStaff = User::factory()->staff()->create();
});

it('allows admin to manage portals and view admin listings', function (): void {
    expect(Gate::forUser($this->admin)->allows('manage', Portal::class))->toBeTrue()
        ->and(Gate::forUser($this->admin)->allows('viewAnyAdmin', Portal::class))->toBeTrue()
        ->and(Gate::forUser($this->staff)->allows('manage', Portal::class))->toBeFalse()
        ->and(Gate::forUser($this->staff)->allows('viewAnyAdmin', Portal::class))->toBeFalse();
});

it('allows user to view portal only if active mapping exists and portal is active', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    expect(Gate::forUser($this->staff)->allows('view', $portal))->toBeFalse();

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $portal->id,
        'is_active' => true,
    ]);

    expect(Gate::forUser($this->staff)->allows('view', $portal))->toBeTrue()
        ->and(Gate::forUser($this->otherStaff)->allows('view', $portal))->toBeFalse();

    // Inactive portal blocks view for regular staff
    $portal->update(['is_active' => false]);
    expect(Gate::forUser($this->staff)->allows('view', $portal->fresh()))->toBeFalse()
        ->and(Gate::forUser($this->admin)->allows('view', $portal->fresh()))->toBeFalse();
});

it('allows dispatchToken only if portal is active and user mapping is active', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $portal->id,
        'is_active' => true,
    ]);

    expect(Gate::forUser($this->staff)->allows('dispatchToken', $portal))->toBeTrue();

    // Inactive mapping
    $mapping->update(['is_active' => false]);
    expect(Gate::forUser($this->staff)->allows('dispatchToken', $portal->fresh()))->toBeFalse();

    // Active mapping but inactive portal
    $mapping->update(['is_active' => true]);
    $portal->update(['is_active' => false]);
    expect(Gate::forUser($this->staff)->allows('dispatchToken', $portal->fresh()))->toBeFalse();
});

it('allows updatePersonalCredential only if portal supports personal and active mapping exists', function (): void {
    $sharedPortal = Portal::factory()->create(['auth_type' => 'shared', 'is_active' => true]);
    $bothPortal = Portal::factory()->create(['auth_type' => 'both', 'is_active' => true]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $sharedPortal->id,
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $bothPortal->id,
        'is_active' => true,
    ]);

    // Portal shared tidak boleh update credential personal
    expect(Gate::forUser($this->staff)->allows('updatePersonalCredential', $sharedPortal))->toBeFalse()
        ->and(Gate::forUser($this->staff)->allows('updatePersonalCredential', $bothPortal))->toBeTrue()
        ->and(Gate::forUser($this->otherStaff)->allows('updatePersonalCredential', $bothPortal))->toBeFalse();
});
