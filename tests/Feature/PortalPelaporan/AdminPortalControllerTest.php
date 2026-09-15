<?php

use App\Models\Portal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        'email' => 'admin.portal@rsasf.co.id',
    ]);

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'email' => 'staff.portal@rsasf.co.id',
    ]);
});

it('forbids unauthenticated users and staff from accessing admin portals', function (): void {
    $this->get(route('admin.portals.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($this->staff)
        ->get(route('admin.portals.index'))
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->get(route('admin.portals.create'))
        ->assertForbidden();
});

it('allows admin to render portal index with list and filters', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes']);
    Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN']);

    $this->actingAs($this->admin)
        ->get(route('admin.portals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/portals/index')
            ->has('portals.data', 2)
            ->has('categories')
            ->has('filters')
        );
});

it('allows admin to render create page with deduplicated categories', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes']);
    Portal::factory()->create(['name' => 'SIHA 2', 'category' => ' Kemenkes ']);
    Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN']);

    $this->actingAs($this->admin)
        ->get(route('admin.portals.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/portals/create')
            ->has('categories', 2)
            ->where('categories', ['BKKBN', 'Kemenkes'])
        );
});

it('allows admin to create a new portal with encrypted password and json config', function (): void {
    $payload = [
        'name' => 'SITB Jawa Timur',
        'slug' => 'sitb-jatim',
        'category' => 'Kemenkes',
        'url' => 'https://jatim.sitb.id/sitb2024/app',
        'url_pattern' => '*://*.sitb.id/*',
        'description' => 'Sistem Informasi Tuberkulosis Jawa Timur',
        'auth_type' => 'shared',
        'shared_username' => 'fasyankes_sifast',
        'shared_password' => 'RahasiaRS123!',
        'form_config' => [
            'is_spa' => false,
            'wait_timeout_ms' => 8000,
            'username_field' => ['selectors' => ["input[name='username']"]],
            'password_field' => ['selectors' => ["input[name='password']"]],
            'auto_submit' => false,
        ],
        'is_active' => true,
        'sort_order' => 5,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('admin.portals.store'), $payload);

    $response->assertRedirect(route('admin.portals.index'))
        ->assertSessionHas('success');

    $portal = Portal::where('slug', 'sitb-jatim')->first();
    expect($portal)->not->toBeNull()
        ->and($portal->name)->toBe('SITB Jawa Timur')
        ->and($portal->shared_username)->toBe('fasyankes_sifast')
        ->and($portal->shared_password)->toBe('RahasiaRS123!') // auto decrypt via cast
        ->and($portal->form_config['wait_timeout_ms'])->toBe(8000);
});

it('auto-generates slug from name if slug is not provided', function (): void {
    $payload = [
        'name' => 'MPDN Kemkes RI',
        'category' => 'Kemenkes',
        'url' => 'https://mpdn.kemkes.go.id/masuk',
        'auth_type' => 'personal',
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.portals.store'), $payload)
        ->assertRedirect(route('admin.portals.index'));

    $portal = Portal::where('name', 'MPDN Kemkes RI')->first();
    expect($portal)->slug->toBe('mpdn-kemkes-ri');
});

it('allows admin to edit and update a portal without erasing existing shared password', function (): void {
    $portal = Portal::factory()->create([
        'name' => 'SIHA 2.0',
        'slug' => 'siha-2',
        'auth_type' => 'shared',
        'shared_username' => 'siha_user',
        'shared_password' => 'OldEncryptedSecretPass!',
    ]);

    // Render edit page - verify shared password is not exposed in plaintext
    $this->actingAs($this->admin)
        ->get(route('admin.portals.edit', $portal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/portals/edit')
            ->where('portal.id', $portal->id)
            ->where('portal.has_shared_password', true)
            ->missing('portal.shared_password')
        );

    // Update portal without filling shared_password -> existing password must remain
    $updatePayload = [
        'name' => 'SIHA Kemenkes PIMS',
        'slug' => 'siha-kemenkes-pims',
        'category' => 'Kemenkes',
        'url' => 'https://sihapims2.kemkes.go.id/login',
        'auth_type' => 'shared',
        'shared_username' => 'siha_user_updated',
        'shared_password' => '', // empty
        'is_active' => true,
    ];

    $this->actingAs($this->admin)
        ->put(route('admin.portals.update', $portal), $updatePayload)
        ->assertRedirect(route('admin.portals.index'))
        ->assertSessionHas('success');

    $portal->refresh();
    expect($portal->name)->toBe('SIHA Kemenkes PIMS')
        ->and($portal->shared_username)->toBe('siha_user_updated')
        ->and($portal->shared_password)->toBe('OldEncryptedSecretPass!');
});

it('allows admin to toggle active status of a portal', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.portals.toggle-active', $portal))
        ->assertBack()
        ->assertSessionHas('success');

    expect($portal->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.portals.toggle-active', $portal))
        ->assertBack();

    expect($portal->fresh()->is_active)->toBeTrue();
});

it('allows admin to delete a portal', function (): void {
    $portal = Portal::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.portals.destroy', $portal))
        ->assertRedirect(route('admin.portals.index'))
        ->assertSessionHas('success');

    expect(Portal::find($portal->id))->toBeNull();
});

it('validates that icon_file must be a valid image under 2MB in store request', function (): void {
    Storage::fake('public');

    // Test invalid mime type (text file)
    $invalidFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
    $response = $this->actingAs($this->admin)
        ->post(route('admin.portals.store'), [
            'name' => 'Portal Invalid File',
            'category' => 'Kemenkes',
            'url' => 'https://example.com/login',
            'auth_type' => 'shared',
            'icon_file' => $invalidFile,
        ]);

    $response->assertSessionHasErrors('icon_file');

    // Test oversized file (> 2048 KB)
    $oversizedFile = UploadedFile::fake()->create('huge.png', 3000, 'image/png');
    $responseOversized = $this->actingAs($this->admin)
        ->post(route('admin.portals.store'), [
            'name' => 'Portal Huge File',
            'category' => 'Kemenkes',
            'url' => 'https://example.com/login',
            'auth_type' => 'shared',
            'icon_file' => $oversizedFile,
        ]);

    $responseOversized->assertSessionHasErrors('icon_file');
});
