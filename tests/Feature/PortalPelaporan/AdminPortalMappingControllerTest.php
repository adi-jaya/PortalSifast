<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config()->set('inertia.testing.ensure_pages_exist', false);

    if (! TestResponse::hasMacro('assertBack')) {
        TestResponse::macro('assertBack', function () {
            return $this->assertRedirect();
        });
    }

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin.mapping@rsasf.co.id',
    ]);

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'email' => 'staff.mapping@rsasf.co.id',
    ]);

    $this->portal1 = Portal::factory()->create([
        'name' => 'SIRIKA BKKBN',
        'slug' => 'sirika-bkkbn',
        'auth_type' => 'shared',
    ]);

    $this->portal2 = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'slug' => 'mpdn-kemenkes',
        'auth_type' => 'both',
    ]);
});

it('forbids staff from accessing mapping pages or endpoints', function (): void {
    $this->actingAs($this->staff)
        ->get(route('admin.portals.mapping.index'))
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->post(route('admin.portals.mapping.sync-portal'), [])
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->post(route('admin.portals.mapping.sync-user'), [])
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->postJson(route('admin.portals.mapping.save-row'), [])
        ->assertForbidden();

    $cred = UserPortalCredential::create([
        'user_id' => $this->staff->id,
        'portal_id' => $this->portal1->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $this->actingAs($this->staff)
        ->patch(route('admin.portals.mapping.update-credential', $cred), [])
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->delete(route('admin.portals.mapping.destroy-credential', $cred))
        ->assertForbidden();
});

it('allows admin to render mapping page with portals and users', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.portals.mapping.index', ['portal_id' => $this->portal1->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/portals/mapping')
            ->has('portals')
            ->has('users')
            ->has('selected_portal')
            ->has('view_mode')
        );
});

it('allows admin to sync multiple users for a portal (syncPortal)', function (): void {
    $userA = User::factory()->create(['name' => 'Petugas A']);
    $userB = User::factory()->create(['name' => 'Petugas B']);

    $payload = [
        'portal_id' => $this->portal1->id,
        'assignments' => [
            [
                'user_id' => $userA->id,
                'has_access' => true,
                'credential_type' => 'use_shared',
                'notes' => 'Petugas KB',
            ],
            [
                'user_id' => $userB->id,
                'has_access' => true,
                'credential_type' => 'use_shared',
                'notes' => 'Petugas Poli',
            ],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.portals.mapping.sync-portal'), $payload)
        ->assertBack()
        ->assertSessionHas('success');

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->count())->toBe(2);

    $credA = UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $userA->id)->first();
    expect($credA)->not->toBeNull()
        ->and($credA->credential_type)->toBe('use_shared')
        ->and($credA->notes)->toBe('Petugas KB');
});

it('revokes access when has_access is false during syncPortal', function (): void {
    $userA = User::factory()->create();
    UserPortalCredential::create([
        'user_id' => $userA->id,
        'portal_id' => $this->portal1->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $payload = [
        'portal_id' => $this->portal1->id,
        'assignments' => [
            [
                'user_id' => $userA->id,
                'has_access' => false,
            ],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.portals.mapping.sync-portal'), $payload)
        ->assertBack();

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $userA->id)->exists())->toBeFalse();
});

it('allows admin to sync multiple portals for a single user (syncUser)', function (): void {
    $targetUser = User::factory()->create(['name' => 'Petugas Rekam Medis']);

    $payload = [
        'user_id' => $targetUser->id,
        'assignments' => [
            [
                'portal_id' => $this->portal1->id,
                'has_access' => true,
                'credential_type' => 'use_shared',
                'notes' => 'Akses SIRIKA',
            ],
            [
                'portal_id' => $this->portal2->id,
                'has_access' => true,
                'credential_type' => 'personal',
                'notes' => 'Akses MPDN Mandiri',
            ],
        ],
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.portals.mapping.sync-user'), $payload)
        ->assertBack()
        ->assertSessionHas('success');

    $creds = UserPortalCredential::where('user_id', $targetUser->id)->get();
    expect($creds)->toHaveCount(2);

    $cred2 = $creds->firstWhere('portal_id', $this->portal2->id);
    expect($cred2->credential_type)->toBe('personal');
});

it('allows admin to update a single credential mapping and delete it', function (): void {
    $cred = UserPortalCredential::create([
        'user_id' => $this->staff->id,
        'portal_id' => $this->portal2->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    // Update credential
    $this->actingAs($this->admin)
        ->patch(route('admin.portals.mapping.update-credential', $cred), [
            'credential_type' => 'personal',
            'is_active' => false,
            'notes' => 'Nonaktif sementara',
        ])
        ->assertBack()
        ->assertSessionHas('success');

    $cred->refresh();
    expect($cred->credential_type)->toBe('personal')
        ->and($cred->is_active)->toBeFalse()
        ->and($cred->notes)->toBe('Nonaktif sementara');

    // Delete credential
    $this->actingAs($this->admin)
        ->delete(route('admin.portals.mapping.destroy-credential', $cred))
        ->assertBack()
        ->assertSessionHas('success');

    expect(UserPortalCredential::find($cred->id))->toBeNull();
});

it('saves single row assignment via saveRow endpoint (instant auto-save)', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->postJson(route('admin.portals.mapping.save-row'), [
            'portal_id' => $this->portal1->id,
            'user_id' => $user->id,
            'has_access' => true,
            'credential_type' => 'use_shared',
            'notes' => 'Akses Instan Auto-Save',
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'credential' => [
                'portal_id' => $this->portal1->id,
                'user_id' => $user->id,
                'credential_type' => 'use_shared',
                'notes' => 'Akses Instan Auto-Save',
            ],
        ]);

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $user->id)->exists())->toBeTrue();

    // Preserve notes when notes key is omitted from payload
    $this->actingAs($this->admin)
        ->postJson(route('admin.portals.mapping.save-row'), [
            'portal_id' => $this->portal1->id,
            'user_id' => $user->id,
            'has_access' => true,
            'credential_type' => 'personal',
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'credential' => [
                'credential_type' => 'personal',
                'notes' => 'Akses Instan Auto-Save',
            ],
        ]);

    // Clear notes when notes key is explicitly sent as null
    $this->actingAs($this->admin)
        ->postJson(route('admin.portals.mapping.save-row'), [
            'portal_id' => $this->portal1->id,
            'user_id' => $user->id,
            'has_access' => true,
            'credential_type' => 'personal',
            'notes' => null,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'credential' => [
                'notes' => null,
            ],
        ]);

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $user->id)->first()->notes)->toBeNull();

    // Revoke access via saveRow with has_access: false
    $this->actingAs($this->admin)
        ->postJson(route('admin.portals.mapping.save-row'), [
            'portal_id' => $this->portal1->id,
            'user_id' => $user->id,
            'has_access' => false,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'credential' => null,
        ]);

    expect(UserPortalCredential::where('portal_id', $this->portal1->id)->where('user_id', $user->id)->exists())->toBeFalse();
});
