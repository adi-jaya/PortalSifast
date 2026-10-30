<?php

use App\Models\Portal;
use App\Services\Portal\AdminPortalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('stores a portal with an uploaded icon file to public storage disk', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('kemenkes.png', 100, 100);

    $portal = $this->service->storePortal([
        'name' => 'Portal SIRS Kemkes',
        'category' => 'Kemenkes',
        'url' => 'https://sirs.kemkes.go.id',
        'auth_type' => 'shared',
        'icon_file' => $file,
    ]);

    expect($portal->icon_path)->not->toBeNull()
        ->and($portal->icon_path)->toStartWith('portals/portal-sirs-kemkes-')
        ->and(Storage::disk('public')->exists($portal->icon_path))->toBeTrue();
});

it('updates portal logo by storing new file and deleting previous file', function (): void {
    Storage::fake('public');
    $oldFile = UploadedFile::fake()->image('old-logo.png', 100, 100);
    $portal = $this->service->storePortal([
        'name' => 'SIRIKA BKKBN',
        'category' => 'BKKBN',
        'url' => 'https://sirika.bkkbn.go.id',
        'auth_type' => 'shared',
        'icon_file' => $oldFile,
    ]);
    $oldPath = $portal->icon_path;
    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();

    $newFile = UploadedFile::fake()->image('new-logo.png', 120, 120);
    $updated = $this->service->updatePortalLogo($portal, $newFile);

    expect($updated->icon_path)->not->toBe($oldPath)
        ->and(Storage::disk('public')->exists($updated->icon_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse();
});

it('removes portal logo and deletes file from public disk', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('logo.png', 100, 100);
    $portal = $this->service->storePortal([
        'name' => 'MPDN Kemkes',
        'category' => 'Kemenkes',
        'url' => 'https://mpdn.kemkes.go.id',
        'auth_type' => 'shared',
        'icon_file' => $file,
    ]);
    $path = $portal->icon_path;
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $this->service->removePortalLogo($portal);

    expect($portal->fresh()->icon_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('cleans up physical logo file when destroying portal', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('to-delete.png', 100, 100);
    $portal = $this->service->storePortal([
        'name' => 'Portal to Delete',
        'category' => 'Kemenkes',
        'url' => 'https://example.com',
        'auth_type' => 'shared',
        'icon_file' => $file,
    ]);
    $path = $portal->icon_path;
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $this->service->destroyPortal($portal);

    expect(Portal::find($portal->id))->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('includes icon_url in paginatePortals and formatPortalForEdit', function (): void {
    Storage::fake('public');
    $portal = Portal::factory()->create([
        'name' => 'Test Accessor Portal',
        'icon_path' => 'portals/test-accessor.png',
    ]);

    $editData = $this->service->formatPortalForEdit($portal);
    expect($editData)->toHaveKey('icon_url')
        ->and($editData['icon_url'])->toBe(Storage::disk('public')->url('portals/test-accessor.png'));

    $paginated = $this->service->paginatePortals(['search' => 'Test Accessor Portal']);
    expect($paginated['portals']->first())->toHaveKey('icon_url')
        ->and($paginated['portals']->first()['icon_url'])->toBe(Storage::disk('public')->url('portals/test-accessor.png'));
});

it('safely resolves file extension using MIME type rather than client extension to prevent RCE', function (): void {
    Storage::fake('public');
    $maliciousFile = UploadedFile::fake()->image('exploit.php', 100, 100);

    $portal = $this->service->storePortal([
        'name' => 'Exploit Portal',
        'category' => 'Security',
        'url' => 'https://security.example.com',
        'auth_type' => 'shared',
        'icon_file' => $maliciousFile,
    ]);

    expect($portal->icon_path)->toEndWith('.png')
        ->and($portal->icon_path)->not->toContain('.php')
        ->and(Storage::disk('public')->exists($portal->icon_path))->toBeTrue();

    $anotherMaliciousFile = UploadedFile::fake()->image('shell.php', 100, 100);
    $updated = $this->service->updatePortalLogo($portal, $anotherMaliciousFile);

    expect($updated->icon_path)->toEndWith('.png')
        ->and($updated->icon_path)->not->toContain('.php')
        ->and(Storage::disk('public')->exists($updated->icon_path))->toBeTrue();

    // Also verify when MIME type is explicitly image/png with a .php client filename
    $mimePngFile = UploadedFile::fake()->image('backdoor.php', 100, 100)->mimeType('image/png');
    $updatedWithMime = $this->service->updatePortalLogo($portal, $mimePngFile);

    expect($updatedWithMime->icon_path)->toEndWith('.png')
        ->and($updatedWithMime->icon_path)->not->toContain('.php')
        ->and(Storage::disk('public')->exists($updatedWithMime->icon_path))->toBeTrue();
});

it('does not remove logo when remove_logo is string false in updatePortal', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('logo.png', 100, 100);
    $portal = $this->service->storePortal([
        'name' => 'Retain Logo Portal',
        'category' => 'Kemenkes',
        'url' => 'https://example.com',
        'auth_type' => 'shared',
        'icon_file' => $file,
    ]);
    $path = $portal->icon_path;
    expect(Storage::disk('public')->exists($path))->toBeTrue();

    // Passing 'false' as string (as commonly sent by multipart/form-data)
    $this->service->updatePortal($portal, [
        'name' => 'Retain Logo Portal Updated',
        'remove_logo' => 'false',
    ]);

    expect($portal->fresh()->icon_path)->toBe($path)
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    // Passing boolean false
    $this->service->updatePortal($portal, [
        'remove_logo' => false,
    ]);

    expect($portal->fresh()->icon_path)->toBe($path)
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    // Passing boolean true removes the logo
    $this->service->updatePortal($portal, [
        'remove_logo' => true,
    ]);

    expect($portal->fresh()->icon_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});
