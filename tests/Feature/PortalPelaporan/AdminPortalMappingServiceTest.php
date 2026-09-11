<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\AdminPortalMappingService;

beforeEach(function (): void {
    $this->service = new AdminPortalMappingService;
    $this->portal1 = Portal::factory()->create(['name' => 'SIRIKA BKKBN', 'auth_type' => 'shared']);
    $this->portal2 = Portal::factory()->create(['name' => 'MPDN Kemenkes', 'auth_type' => 'both']);
});

it('prepares mapping matrix data for portal view mode', function (): void {
    $user = User::factory()->create(['name' => 'Petugas Satu']);
    UserPortalCredential::create([
        'user_id' => $user->id,
        'portal_id' => $this->portal1->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $data = $this->service->getMappingData($this->portal1->id, null, 'portal');

    expect($data['view_mode'])->toBe('portal')
        ->and($data['selected_portal']['id'])->toBe($this->portal1->id)
        ->and($data['portal_credentials'])->toHaveKey($user->id);
});

it('syncs multiple user assignments for a portal transactionally', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->service->syncPortalUsers($this->portal1, [
        ['user_id' => $userA->id, 'has_access' => true, 'credential_type' => 'use_shared', 'notes' => 'Akses KB'],
        ['user_id' => $userB->id, 'has_access' => true, 'credential_type' => 'use_shared', 'notes' => 'Akses Poli'],
    ]);

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->count())->toBe(2);

    // Revoke userA
    $this->service->syncPortalUsers($this->portal1, [
        ['user_id' => $userA->id, 'has_access' => false],
    ]);

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->count())->toBe(1)
        ->and(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $userA->id)->exists())->toBeFalse();
});

it('syncs multiple portal assignments for a single user transactionally', function (): void {
    $user = User::factory()->create();

    $this->service->syncUserPortals($user, [
        ['portal_id' => $this->portal1->id, 'has_access' => true, 'credential_type' => 'use_shared'],
        ['portal_id' => $this->portal2->id, 'has_access' => true, 'credential_type' => 'personal'],
    ]);

    expect(UserPortalCredential::where('user_id', $user->id)->count())->toBe(2);
});

it('updates and deletes single credential mappings via service', function (): void {
    $user = User::factory()->create();
    $cred = UserPortalCredential::create([
        'user_id' => $user->id,
        'portal_id' => $this->portal1->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $updated = $this->service->updateCredential($cred, [
        'credential_type' => 'personal',
        'is_active' => false,
        'notes' => 'Nonaktif sementara',
    ]);

    expect($updated->credential_type)->toBe('personal')
        ->and($updated->is_active)->toBeFalse();

    expect($this->service->destroyCredential($cred))->toBeTrue();
    expect(UserPortalCredential::find($cred->id))->toBeNull();
});

it('saves a single mapping assignment directly via saveSingleAssignment (instant auto-save)', function (): void {
    $user = User::factory()->create();

    $cred = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'use_shared', 'PJ Pelaporan OK');
    expect($cred)->not->toBeNull()
        ->and($cred->portal_id)->toBe($this->portal1->id)
        ->and($cred->user_id)->toBe($user->id)
        ->and($cred->credential_type)->toBe('use_shared')
        ->and($cred->notes)->toBe('PJ Pelaporan OK');

    // Revoke single assignment
    $deleted = $this->service->saveSingleAssignment($this->portal1->id, $user->id, false);
    expect($deleted)->toBeNull()
        ->and(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

it('preserves existing notes when saveSingleAssignment is called with null notes', function (): void {
    $user = User::factory()->create();

    $cred = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'use_shared', 'Catatan Awal Tetap');
    expect($cred->notes)->toBe('Catatan Awal Tetap');

    // Update credential type with null notes -> existing notes must be preserved
    $updated = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'personal', null);
    expect($updated->credential_type)->toBe('personal')
        ->and($updated->notes)->toBe('Catatan Awal Tetap');
});

it('filters out null and empty string departments in mapping data', function (): void {
    User::factory()->create(['dep_id' => 'POLI']);
    User::factory()->create(['dep_id' => 'IGD']);
    User::factory()->create(['dep_id' => '']);
    User::factory()->create(['dep_id' => null]);

    $data = $this->service->getMappingData($this->portal1->id, null, 'portal');

    expect($data['departments']->all())->toContain('IGD', 'POLI')
        ->and($data['departments']->all())->not->toContain('')
        ->and($data['departments']->all())->not->toContain(null);
});
