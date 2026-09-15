# Master Portal Logo Upload Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement secure, performant, and reactive external portal logo file uploads, public disk storage lifecycle management, model accessor serialization, and responsive UI management for Modul 13 Portal Pelaporan Eksternal SIMRS.

**Architecture:** Use Laravel's `public` storage disk (`storage/app/public/portals/`) exposed directly via the public symlink with collision-resistant naming (`portals/{slug}-{hash8}.{ext}`) and automatic deletion of superseded/orphan files. Expose logos via Eloquent accessor `icon_url` with `$appends = ['icon_url']` for seamless JSON serialization across Inertia props and browser extension APIs. Provide a hybrid UI in Inertia React (`portal-form.tsx` and `index.tsx`) supporting both full-form multipart submit (with `_method: 'put'` method spoofing for editing) and instant upload/removal actions.

**Tech Stack:** Laravel 11/12, PHP 8.2+, Pest 3, React 19, Inertia.js v2, TypeScript, Tailwind CSS v4, Lucide React.

**Spec:** [docs/superpowers/specs/2026-09-15-portal-logo-upload-design.md](../../superpowers/specs/2026-09-15-portal-logo-upload-design.md)

## Global Constraints

- Storage disk: strictly `public` (`storage/app/public/portals/`).
- File naming convention: `portals/{slug}-{hash8}.{ext}` where `hash8` is 8 characters from `Str::random(8)`.
- Accepted mime types: `png,jpg,jpeg,webp,svg` with max size: 2048 KB (2 MB).
- Database schema: `icon_path` remains `VARCHAR(255) NULLABLE` without requiring database migrations.
- Model serialization: `$appends = ['icon_url']` and accessor `iconUrl(): Attribute` on `App\Models\Portal`.
- Authorization: All admin logo operations protected by `Gate::authorize('manage', Portal::class)` and `can:manage,App\Models\Portal`.
- Frontend form: Standard submit button label is strictly **"Simpan"**.
- Lifecycle guarantees: Replacing a logo deletes the old file, removing a logo deletes the file and nulls `icon_path`, deleting a portal deletes the logo file.

---

### Task 1: Eloquent Model Accessor & Serialization (`Portal.php`)

**Files:**
- Modify: `app/Models/Portal.php`
- Test: `tests/Feature/PortalPelaporan/PortalModelTest.php`

**Interfaces:**
- Consumes: `icon_path` column from `portals` table, `Illuminate\Support\Facades\Storage`.
- Produces: `Portal::$appends = ['icon_url']`, `Portal::iconUrl(): Attribute` returning `?string`.

- [ ] **Step 1: Write the failing tests for `icon_url` accessor and array serialization**

Add the following tests to the end of `tests/Feature/PortalPelaporan/PortalModelTest.php`:

```php
it('provides icon_url attribute pointing to public storage disk when icon_path is set', function (): void {
    Storage::fake('public');
    $portal = Portal::factory()->create(['icon_path' => 'portals/test-logo.png']);

    expect($portal->icon_url)->toBe(Storage::disk('public')->url('portals/test-logo.png'))
        ->and($portal->toArray())->toHaveKey('icon_url')
        ->and($portal->toArray()['icon_url'])->toBe(Storage::disk('public')->url('portals/test-logo.png'));
});

it('returns null for icon_url when icon_path is null', function (): void {
    $portal = Portal::factory()->create(['icon_path' => null]);

    expect($portal->icon_url)->toBeNull()
        ->and($portal->toArray())->toHaveKey('icon_url')
        ->and($portal->toArray()['icon_url'])->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: FAIL with `toArray() does not have key icon_url` or `icon_url` undefined on model.

- [ ] **Step 3: Implement accessor and appends in `Portal.php`**

Modify `app/Models/Portal.php`:
1. Import `Illuminate\Database\Eloquent\Casts\Attribute` and `Illuminate\Support\Facades\Storage`.
2. Add `$appends = ['icon_url'];`.
3. Define `protected function iconUrl(): Attribute`.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Portal extends Model
{
    /** @use HasFactory<\Database\Factories\PortalFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'url',
        'url_pattern',
        'icon_path',
        'description',
        'auth_type',
        'shared_username',
        'shared_password',
        'shared_extra_fields',
        'form_config',
        'is_active',
        'sort_order',
    ];

    protected $hidden = [
        'shared_password',
    ];

    protected $appends = [
        'icon_url',
    ];

    protected function casts(): array
    {
        return [
            'shared_password' => 'encrypted',
            'shared_extra_fields' => 'array',
            'form_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->icon_path
                ? Storage::disk('public')->url($this->icon_path)
                : null,
        );
    }

    public function userCredentials(): HasMany
    {
        return $this->hasMany(UserPortalCredential::class, 'portal_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_portal_credentials', 'portal_id', 'user_id')
            ->withPivot(['id', 'credential_type', 'personal_username', 'personal_extra_fields', 'is_active', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function supportsShared(): bool
    {
        return in_array($this->auth_type, ['shared', 'both'], true);
    }

    public function supportsPersonal(): bool
    {
        return in_array($this->auth_type, ['personal', 'both'], true);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: PASS (all tests pass).

- [ ] **Step 5: Commit changes**

```bash
git add app/Models/Portal.php tests/Feature/PortalPelaporan/PortalModelTest.php
git commit -m "feat(portal): add icon_url accessor and serialization to Portal model"
```

---

### Task 2: Form Request Validation for Logo Uploads (`PortalRequest.php` & `UploadPortalLogoRequest.php`)

**Files:**
- Create: `app/Http/Requests/Admin/UploadPortalLogoRequest.php`
- Modify: `app/Http/Requests/Admin/PortalRequest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`

**Interfaces:**
- Consumes: `Portal` policy `manage`.
- Produces:
  - `PortalRequest`: validates `icon_file` as nullable file (png,jpg,jpeg,webp,svg, max 2048 KB) and `remove_logo` as nullable boolean.
  - `UploadPortalLogoRequest`: validates `icon_file` as required file (png,jpg,jpeg,webp,svg, max 2048 KB).

- [ ] **Step 1: Write the failing tests for logo request validation**

Add these tests to `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalControllerTest.php --filter="validates that icon_file must be a valid image"`  
Expected: FAIL because `icon_file` is not currently recognized or validated in `PortalRequest`.

- [ ] **Step 3: Implement `UploadPortalLogoRequest` and update `PortalRequest`**

1. Create `app/Http/Requests/Admin/UploadPortalLogoRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class UploadPortalLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'icon_file' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'max:2048',
            ],
        ];
    }
}
```

2. Modify `app/Http/Requests/Admin/PortalRequest.php`:
Replace `'icon_path' => ['nullable', 'string', 'max:255'],` with:

```php
            'icon_file' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'max:2048',
            ],
            'remove_logo' => [
                'nullable',
                'boolean',
            ],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalControllerTest.php --filter="validates that icon_file must be a valid image"`  
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add app/Http/Requests/Admin/PortalRequest.php app/Http/Requests/Admin/UploadPortalLogoRequest.php tests/Feature/PortalPelaporan/AdminPortalControllerTest.php
git commit -m "feat(portal): add logo file validation in portal requests"
```

---

### Task 3: Service Layer File Storage & Lifecycle (`AdminPortalService.php`)

**Files:**
- Modify: `app/Services/Portal/AdminPortalService.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`

**Interfaces:**
- Consumes: `Portal` model, `Illuminate\Http\UploadedFile`, `Illuminate\Support\Facades\Storage`.
- Produces:
  - `AdminPortalService::updatePortalLogo(Portal $portal, UploadedFile $file): Portal`
  - `AdminPortalService::removePortalLogo(Portal $portal): Portal`
  - Updated `storePortal(array $data): Portal` (stores `icon_file`, sets `icon_path`, unsets file keys)
  - Updated `updatePortal(Portal $portal, array $data): Portal` (handles `remove_logo` and `icon_file`)
  - Updated `destroyPortal(Portal $portal): bool` (deletes storage file before record deletion)
  - Updated `paginatePortals()` and `formatPortalForEdit()` (includes `'icon_url' => $portal->icon_url`)

- [ ] **Step 1: Write failing unit tests for logo operations and lifecycle**

Add these tests to `tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`  
Expected: FAIL with `Call to undefined method AdminPortalService::updatePortalLogo()`.

- [ ] **Step 3: Implement storage lifecycle logic in `AdminPortalService.php`**

Modify `app/Services/Portal/AdminPortalService.php`:
1. Add imports:
   ```php
   use Illuminate\Http\UploadedFile;
   use Illuminate\Support\Facades\Storage;
   ```
2. In `paginatePortals()` transformation array (around line 57), add:
   ```php
   'icon_url' => $portal->icon_url,
   ```
3. In `formatPortalForEdit()` array (around line 161), add:
   ```php
   'icon_url' => $portal->icon_url,
   ```
4. Implement `storePortal(array $data)`:
   - Handle file upload if `$data['icon_file'] ?? null` is `UploadedFile`.
   - Store as `portals/{slug}-{hash8}.{ext}`.
   - Unset `icon_file` and `remove_logo` before `Portal::create()`.
5. Implement `updatePortal(Portal $portal, array $data)`:
   - If `!empty($data['remove_logo'])`, call `removePortalLogo($portal)`.
   - Else if `($data['icon_file'] ?? null) instanceof UploadedFile`, call `updatePortalLogo($portal, $data['icon_file'])`.
   - Unset `icon_file` and `remove_logo` before `$portal->update()`.
6. Implement `updatePortalLogo(Portal $portal, UploadedFile $file): Portal`:
   - Delete old file via `deleteIconFile($portal->icon_path)`.
   - Store new file as `portals/{slug}-{hash8}.{ext}`.
   - Update `$portal->update(['icon_path' => $path])`.
7. Implement `removePortalLogo(Portal $portal): Portal`:
   - Delete old file via `deleteIconFile($portal->icon_path)`.
   - Update `$portal->update(['icon_path' => null])`.
8. Implement `destroyPortal(Portal $portal): bool`:
   - Delete old file via `deleteIconFile($portal->icon_path)`.
   - Delete record via `(bool) $portal->delete()`.
9. Implement `deleteIconFile(?string $path): void`:
   ```php
   private function deleteIconFile(?string $path): void
   {
       if ($path && Storage::disk('public')->exists($path)) {
           Storage::disk('public')->delete($path);
       }
   }
   ```

Complete updated code for `AdminPortalService.php`:

```php
<?php

namespace App\Services\Portal;

use App\Models\Portal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminPortalService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     portals: LengthAwarePaginator,
     *     categories: Collection<int, string>,
     *     filters: array{search: string, category: string, status: string}
     * }
     */
    public function paginatePortals(array $filters = []): array
    {
        $query = Portal::query()->withCount('userCredentials');

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('category', 'like', "%{$escaped}%")
                    ->orWhere('url', 'like', "%{$escaped}%");
            });
        }

        $category = (string) ($filters['category'] ?? '');
        if ($category !== '' && $category !== '_all') {
            $query->where('category', $category);
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $portals = $query->ordered()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Portal $portal): array => [
                'id' => $portal->id,
                'name' => $portal->name,
                'slug' => $portal->slug,
                'category' => $portal->category,
                'url' => $portal->url,
                'url_pattern' => $portal->url_pattern,
                'icon_path' => $portal->icon_path,
                'icon_url' => $portal->icon_url,
                'description' => $portal->description,
                'auth_type' => $portal->auth_type,
                'shared_username' => $portal->shared_username,
                'has_shared_password' => filled($portal->shared_password),
                'is_active' => $portal->is_active,
                'sort_order' => $portal->sort_order,
                'user_credentials_count' => $portal->user_credentials_count ?? 0,
                'updated_at' => $portal->updated_at?->format('Y-m-d H:i'),
            ]);

        $categories = $this->getCategories();

        return [
            'portals' => $portals,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
            ],
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function getCategories(): Collection
    {
        return Portal::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn ($category): string => trim((string) $category))
            ->filter(fn (string $category): bool => $category !== '')
            ->unique(fn (string $category): string => mb_strtolower($category))
            ->sort(fn (string $a, string $b): int => strnatcasecmp($a, $b))
            ->values();
    }

    /**
     * @return array{default_form_config: array<string, mixed>, categories: Collection<int, string>}
     */
    public function getFormData(): array
    {
        $defaultFormConfig = [
            'is_spa' => false,
            'wait_timeout_ms' => 10000,
            'username_field' => [
                'selectors' => ["input[name='username']", "input[name='email']", '#username', '#email'],
            ],
            'password_field' => [
                'selectors' => ["input[name='password']", '#password'],
            ],
            'extra_fields' => [],
            'auto_submit' => false,
        ];

        return [
            'default_form_config' => $defaultFormConfig,
            'categories' => $this->getCategories(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storePortal(array $data): Portal
    {
        if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        if (! isset($data['sort_order']) || $data['sort_order'] === null) {
            $data['sort_order'] = (Portal::max('sort_order') ?? 0) + 10;
        }

        if (empty($data['form_config'])) {
            $data['form_config'] = [
                'is_spa' => false,
                'wait_timeout_ms' => 10000,
                'username_field' => ['selectors' => ["input[name='username']", '#username']],
                'password_field' => ['selectors' => ["input[name='password']", '#password']],
                'extra_fields' => [],
                'auto_submit' => false,
            ];
        }

        if (($data['icon_file'] ?? null) instanceof UploadedFile) {
            $file = $data['icon_file'];
            $slug = $data['slug'] ?? Str::slug((string) ($data['name'] ?? 'portal'));
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
            $filename = sprintf('%s-%s.%s', $slug, Str::random(8), $extension);
            $data['icon_path'] = $file->storeAs('portals', $filename, 'public');
        }

        unset($data['icon_file'], $data['remove_logo']);

        return Portal::create($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPortalForEdit(Portal $portal): array
    {
        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'slug' => $portal->slug,
            'category' => $portal->category,
            'url' => $portal->url,
            'url_pattern' => $portal->url_pattern,
            'icon_path' => $portal->icon_path,
            'icon_url' => $portal->icon_url,
            'description' => $portal->description,
            'auth_type' => $portal->auth_type,
            'shared_username' => $portal->shared_username,
            'has_shared_password' => filled($portal->shared_password),
            'shared_extra_fields' => $portal->shared_extra_fields,
            'form_config' => $portal->form_config,
            'is_active' => (bool) $portal->is_active,
            'sort_order' => (int) $portal->sort_order,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePortal(Portal $portal, array $data): Portal
    {
        if (! empty($data['remove_logo'])) {
            $this->removePortalLogo($portal);
        } elseif (($data['icon_file'] ?? null) instanceof UploadedFile) {
            $this->updatePortalLogo($portal, $data['icon_file']);
        }

        unset($data['icon_file'], $data['remove_logo']);

        if (! filled($data['shared_password'] ?? null)) {
            unset($data['shared_password']);
        }

        $portal->update($data);

        return $portal;
    }

    public function updatePortalLogo(Portal $portal, UploadedFile $file): Portal
    {
        $this->deleteIconFile($portal->icon_path);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $filename = sprintf('%s-%s.%s', $portal->slug, Str::random(8), $extension);
        $path = $file->storeAs('portals', $filename, 'public');

        $portal->update(['icon_path' => $path]);

        return $portal;
    }

    public function removePortalLogo(Portal $portal): Portal
    {
        $this->deleteIconFile($portal->icon_path);
        $portal->update(['icon_path' => null]);

        return $portal;
    }

    public function toggleActive(Portal $portal): bool
    {
        $newStatus = ! $portal->is_active;
        $portal->update(['is_active' => $newStatus]);

        return $newStatus;
    }

    public function destroyPortal(Portal $portal): bool
    {
        $this->deleteIconFile($portal->icon_path);

        return (bool) $portal->delete();
    }

    private function deleteIconFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`  
Expected: PASS (all tests pass).

- [ ] **Step 5: Commit changes**

```bash
git add app/Services/Portal/AdminPortalService.php tests/Feature/PortalPelaporan/AdminPortalServiceTest.php
git commit -m "feat(portal): implement logo upload and storage lifecycle in AdminPortalService"
```

---

### Task 4: Controller Endpoints & Web Routes (`AdminPortalController.php` & `routes/web.php`)

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminPortalController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`

**Interfaces:**
- Consumes: `AdminPortalService::updatePortalLogo()`, `AdminPortalService::removePortalLogo()`, `UploadPortalLogoRequest`.
- Produces:
  - Route `POST /admin/portals/{portal}/logo` (`admin.portals.logo.upload`)
  - Route `DELETE /admin/portals/{portal}/logo` (`admin.portals.logo.remove`)
  - Controller methods: `AdminPortalController::uploadLogo()` and `AdminPortalController::removeLogo()`

- [ ] **Step 1: Write failing controller feature tests for logo endpoints and lifecycle**

Add these tests to `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`:

```php
it('allows admin to quickly upload logo via dedicated endpoint', function (): void {
    Storage::fake('public');
    $portal = Portal::factory()->create(['slug' => 'portal-quick-upload']);
    $file = UploadedFile::fake()->image('quick-logo.png', 100, 100);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.portals.logo.upload', $portal), [
            'icon_file' => $file,
        ]);

    $response->assertBack()
        ->assertSessionHas('success', 'Logo portal berhasil diperbarui.');

    $portal->refresh();
    expect($portal->icon_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($portal->icon_path))->toBeTrue();
});

it('allows admin to quickly remove logo via dedicated endpoint', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('quick-remove.png', 100, 100);
    $portal = Portal::factory()->create(['slug' => 'portal-quick-remove']);
    $path = $file->storeAs('portals', 'portal-quick-remove-12345678.png', 'public');
    $portal->update(['icon_path' => $path]);

    $response = $this->actingAs($this->admin)
        ->delete(route('admin.portals.logo.remove', $portal));

    $response->assertBack()
        ->assertSessionHas('success', 'Logo portal berhasil dihapus.');

    $portal->refresh();
    expect($portal->icon_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

it('forbids staff from uploading or removing logo on dedicated endpoints', function (): void {
    $portal = Portal::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('admin.portals.logo.upload', $portal), [
            'icon_file' => UploadedFile::fake()->image('staff.png'),
        ])
        ->assertForbidden();

    $this->actingAs($this->staff)
        ->delete(route('admin.portals.logo.remove', $portal))
        ->assertForbidden();
});

it('allows admin to update portal with new icon_file and remove_logo flag', function (): void {
    Storage::fake('public');
    $portal = Portal::factory()->create([
        'name' => 'Initial Portal',
        'icon_path' => 'portals/initial-logo.png',
    ]);
    Storage::disk('public')->put('portals/initial-logo.png', 'initial content');

    // 1. Update with new file
    $newFile = UploadedFile::fake()->image('updated.png');
    $this->actingAs($this->admin)
        ->put(route('admin.portals.update', $portal), [
            'name' => 'Updated Portal Name',
            'category' => 'Kemenkes',
            'url' => 'https://example.com',
            'auth_type' => 'shared',
            'icon_file' => $newFile,
        ])
        ->assertRedirect(route('admin.portals.index'));

    $portal->refresh();
    expect($portal->name)->toBe('Updated Portal Name')
        ->and(Storage::disk('public')->exists('portals/initial-logo.png'))->toBeFalse()
        ->and(Storage::disk('public')->exists($portal->icon_path))->toBeTrue();

    // 2. Update with remove_logo
    $currentPath = $portal->icon_path;
    $this->actingAs($this->admin)
        ->put(route('admin.portals.update', $portal), [
            'name' => 'Updated Portal Name',
            'category' => 'Kemenkes',
            'url' => 'https://example.com',
            'auth_type' => 'shared',
            'remove_logo' => true,
        ])
        ->assertRedirect(route('admin.portals.index'));

    $portal->refresh();
    expect($portal->icon_path)->toBeNull()
        ->and(Storage::disk('public')->exists($currentPath))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalControllerTest.php --filter="quickly upload logo"`  
Expected: FAIL with `Route [admin.portals.logo.upload] not defined`.

- [ ] **Step 3: Implement routes in `routes/web.php` and controller actions in `AdminPortalController.php`**

1. In `routes/web.php`, add routes inside `admin.portals.` group (around lines 144):

```php
        Route::post('/{portal}/logo', [AdminPortalController::class, 'uploadLogo'])->name('logo.upload');
        Route::delete('/{portal}/logo', [AdminPortalController::class, 'removeLogo'])->name('logo.remove');
```

2. In `app/Http/Controllers/Admin/AdminPortalController.php`:
Add `uploadLogo` and `removeLogo` methods:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortalRequest;
use App\Http\Requests\Admin\UploadPortalLogoRequest;
use App\Models\Portal;
use App\Services\Portal\AdminPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminPortalController extends Controller
{
    public function __construct(
        private AdminPortalService $portalService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage', Portal::class);

        $data = $this->portalService->paginatePortals($request->all());

        return Inertia::render('admin/portals/index', $data);
    }

    public function create(): Response
    {
        Gate::authorize('manage', Portal::class);

        return Inertia::render('admin/portals/create', $this->portalService->getFormData());
    }

    public function store(PortalRequest $request): RedirectResponse
    {
        $this->portalService->storePortal($request->validated());

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil ditambahkan.');
    }

    public function edit(Portal $portal): Response
    {
        Gate::authorize('manage', Portal::class);

        return Inertia::render('admin/portals/edit', [
            'portal' => $this->portalService->formatPortalForEdit($portal),
            'categories' => $this->portalService->getFormData()['categories'],
        ]);
    }

    public function update(PortalRequest $request, Portal $portal): RedirectResponse
    {
        $this->portalService->updatePortal($portal, $request->validated());

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil diperbarui.');
    }

    public function destroy(Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->destroyPortal($portal);

        return redirect()->route('admin.portals.index')
            ->with('success', 'Master portal eksternal berhasil dihapus.');
    }

    public function toggleActive(Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->toggleActive($portal);

        return back()->with('success', 'Status portal berhasil diubah.');
    }

    public function uploadLogo(UploadPortalLogoRequest $request, Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->updatePortalLogo($portal, $request->file('icon_file'));

        return back()->with('success', 'Logo portal berhasil diperbarui.');
    }

    public function removeLogo(Portal $portal): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->portalService->removePortalLogo($portal);

        return back()->with('success', 'Logo portal berhasil dihapus.');
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`  
Expected: PASS (all tests pass).

- [ ] **Step 5: Commit changes**

```bash
git add routes/web.php app/Http/Controllers/Admin/AdminPortalController.php tests/Feature/PortalPelaporan/AdminPortalControllerTest.php
git commit -m "feat(portal): add uploadLogo and removeLogo controller endpoints and routes"
```

---

### Task 5: Frontend TypeScript Types & Portal Index Logo Presentation (`portal.ts` & `index.tsx`)

**Files:**
- Modify: `resources/js/types/portal.ts`
- Modify: `resources/js/pages/admin/portals/index.tsx`

**Interfaces:**
- Consumes: `portal.icon_url` from backend.
- Produces: `Portal` interface with `icon_url?: string | null`, responsive logo badge rendering with image error fallback.

- [ ] **Step 1: Add `icon_url` to `Portal` TypeScript interface**

Modify `resources/js/types/portal.ts`:
Add `icon_url?: string | null;` after `icon_path: string | null;`:

```typescript
export interface Portal {
    id: number;
    name: string;
    slug: string;
    category: string;
    url: string;
    url_pattern: string | null;
    icon_path: string | null;
    icon_url?: string | null;
    description: string | null;
    auth_type: PortalAuthType;
    shared_username: string | null;
    has_shared_password?: boolean;
    shared_extra_fields: Record<string, unknown> | null;
    form_config: FormConfig | null;
    is_active: boolean;
    sort_order: number;
    user_credentials_count?: number;
    created_at?: string;
    updated_at?: string;
}
```

- [ ] **Step 2: Update Portal Target cell in `resources/js/pages/admin/portals/index.tsx`**

Replace the static `Globe` container (around line 271):
```tsx
<div className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
    <Globe className="size-4" />
</div>
```
With:
```tsx
<div className="mt-0.5 flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted/30">
    {portal.icon_url ? (
        <img
            src={portal.icon_url}
            alt={portal.name}
            className="size-full object-contain p-1"
            onError={(e) => {
                e.currentTarget.style.display = 'none';
                e.currentTarget.parentElement?.querySelector('.fallback-icon')?.classList.remove('hidden');
            }}
        />
    ) : null}
    <Globe className={cn("size-4 text-primary", portal.icon_url && "fallback-icon hidden")} />
</div>
```

- [ ] **Step 3: Run TypeScript check on portal pages**

Run: `npx tsc --noEmit | grep -i "portal" || true`  
Expected: Clean output with 0 errors.

- [ ] **Step 4: Commit changes**

```bash
git add resources/js/types/portal.ts resources/js/pages/admin/portals/index.tsx
git commit -m "feat(portal): render portal logo with fallback in admin portals table"
```

---

### Task 6: Frontend Portal Form with Logo Uploader (`portal-form.tsx`)

**Files:**
- Modify: `resources/js/pages/admin/portals/portal-form.tsx`

**Interfaces:**
- Consumes: `initialData.icon_url`, `initialData.id`, `errors.icon_file`, routes `admin.portals.store`, `admin.portals.update`, `admin.portals.logo.upload`, `admin.portals.logo.remove`.
- Produces: Comprehensive Logo Uploader UI component with preview, file chooser, instant quick upload, instant remove, staged remove, and standardized "Simpan" submit button.

- [ ] **Step 1: Implement Logo Uploader & Multipart Form Handling in `portal-form.tsx`**

Modify `resources/js/pages/admin/portals/portal-form.tsx`:
1. Import Lucide icons: `Upload`, `Trash2`, `Image as ImageIcon`, `X`.
2. Import `router` from `@inertiajs/react`.
3. Add `icon_file: File | null` and `remove_logo: boolean` to `useForm`.
4. Add file ref: `const fileInputRef = React.useRef<HTMLInputElement>(null);`.
5. Maintain `previewUrl` state created via `URL.createObjectURL(data.icon_file)`.
6. Implement `handleFileChange`, `handleCancelStagedFile`, `handleMarkRemoveLogo`, `handleQuickUpload`, and `handleInstantRemove`.
7. Update `handleSubmit` to support multipart submit with method spoofing `_method: 'put'` for editing:
   ```typescript
   const handleSubmit = (e: React.FormEvent) => {
       e.preventDefault();
       if (isEditing && initialData?.id) {
           post(`/admin/portals/${initialData.id}`, {
               forceFormData: true,
           });
       } else {
           post('/admin/portals', {
               forceFormData: true,
           });
       }
   };
   ```
8. In Section 1, replace `<Input id="icon_path" ... />` with the rich Logo Uploader block:
   - Preview frame $80 \times 80$ px (`size-20 rounded-xl border border-border bg-muted/20 flex items-center justify-center overflow-hidden`).
   - Image preview from `previewUrl` or `initialData.icon_url` (if not `remove_logo`).
   - Placeholder `<Globe className="size-8 text-muted-foreground/60" />` when empty.
   - Action buttons:
     - "Pilih Logo" (triggers hidden file input).
     - "Batal" (cancels staged file).
     - "Hapus Logo" (marks `remove_logo: true` or instant delete if in edit mode).
     - "Unggah Cepat" (instant upload in edit mode when a new file is staged).
   - Format hint: `"Format: PNG, JPG, WEBP, atau SVG (Maks. 2 MB). Disarankan rasio 1:1."`.
   - Error display for `errors.icon_file`.
9. Standardize the submit button label strictly to **"Simpan"**:
   ```tsx
   <Button type="submit" disabled={processing} className="gap-2">
       <Check className="size-4" />
       {processing ? 'Menyimpan...' : 'Simpan'}
   </Button>
   ```

Complete updated code for `resources/js/pages/admin/portals/portal-form.tsx`:

```tsx
import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Eye,
    EyeOff,
    Globe,
    Lock,
    Shield,
    Sparkles,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';
import { FormConfigEditor } from '@/components/portal/form-config-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { FormConfig, Portal, PortalAuthType } from '@/types';

interface PortalFormProps {
    initialData?: Partial<Portal>;
    categories: string[];
    isEditing?: boolean;
}

const DEFAULT_CATEGORIES = [
    'Kemenkes',
    'BKKBN',
    'Kemendukbangga',
    'Mutu & Akreditasi',
];

export function PortalForm({
    initialData,
    categories,
    isEditing = false,
}: PortalFormProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [isInstantUploading, setIsInstantUploading] = useState(false);
    const [isInstantRemoving, setIsInstantRemoving] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const categoryOptions = React.useMemo(() => {
        const seen = new Set<string>();
        const uniqueList: string[] = [];

        [
            ...(initialData?.category ? [initialData.category] : []),
            ...categories,
            ...DEFAULT_CATEGORIES,
        ].forEach((cat) => {
            const trimmed = (cat || '').trim();
            if (!trimmed) return;
            const normalized = trimmed.toLowerCase();
            if (!seen.has(normalized)) {
                seen.add(normalized);
                uniqueList.push(trimmed);
            }
        });

        return uniqueList.sort((a, b) =>
            a.localeCompare(b, undefined, { sensitivity: 'base' }),
        );
    }, [categories, initialData?.category]);

    const defaultFormConfig: FormConfig = {
        is_spa: false,
        wait_timeout_ms: 10000,
        username_field: {
            selectors: [
                "input[name='username']",
                "input[name='email']",
                '#username',
                '#email',
            ],
        },
        password_field: {
            selectors: ["input[name='password']", '#password'],
        },
        extra_fields: [],
        auto_submit: false,
    };

    const { data, setData, post, processing, errors } = useForm<{
        _method?: string;
        name: string;
        slug: string;
        category: string;
        url: string;
        url_pattern: string;
        icon_file: File | null;
        remove_logo: boolean;
        description: string;
        auth_type: PortalAuthType;
        shared_username: string;
        shared_password: string;
        form_config: FormConfig;
        is_active: boolean;
        sort_order: number;
    }>({
        ...(isEditing ? { _method: 'put' } : {}),
        name: initialData?.name ?? '',
        slug: initialData?.slug ?? '',
        category: initialData?.category ?? (categories[0] || 'Kemenkes'),
        url: initialData?.url ?? '',
        url_pattern: initialData?.url_pattern ?? '',
        icon_file: null,
        remove_logo: false,
        description: initialData?.description ?? '',
        auth_type: (initialData?.auth_type ?? 'both') as PortalAuthType,
        shared_username: initialData?.shared_username ?? '',
        shared_password: '',
        form_config: (initialData?.form_config ??
            defaultFormConfig) as FormConfig,
        is_active: initialData?.is_active ?? true,
        sort_order: initialData?.sort_order ?? 0,
    });

    useEffect(() => {
        if (data.icon_file) {
            const objectUrl = URL.createObjectURL(data.icon_file);
            setPreviewUrl(objectUrl);
            return () => URL.revokeObjectURL(objectUrl);
        } else {
            setPreviewUrl(null);
        }
    }, [data.icon_file]);

    const handleAutoSlug = (force = false) => {
        if (!data.name) return;
        if (!force && (isEditing || data.slug)) return;
        const slugified = data.name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-');
        setData('slug', slugified);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData((prev) => ({
            ...prev,
            icon_file: file,
            remove_logo: false,
        }));
    };

    const handleClearStagedFile = () => {
        setData('icon_file', null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleMarkRemoveLogo = () => {
        setData((prev) => ({
            ...prev,
            icon_file: null,
            remove_logo: true,
        }));
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleUndoRemoveLogo = () => {
        setData('remove_logo', false);
    };

    const handleQuickUpload = () => {
        if (!data.icon_file || !initialData?.id) return;
        setIsInstantUploading(true);
        router.post(
            `/admin/portals/${initialData.id}/logo`,
            { icon_file: data.icon_file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    handleClearStagedFile();
                },
                onFinish: () => {
                    setIsInstantUploading(false);
                },
            },
        );
    };

    const handleInstantRemove = () => {
        if (!initialData?.id) return;
        if (!confirm('Yakin ingin menghapus berkas logo ini secara langsung?')) {
            return;
        }
        setIsInstantRemoving(true);
        router.delete(`/admin/portals/${initialData.id}/logo`, {
            preserveScroll: true,
            onSuccess: () => {
                handleClearStagedFile();
                setData('remove_logo', false);
            },
            onFinish: () => {
                setIsInstantRemoving(false);
            },
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing && initialData?.id) {
            post(`/admin/portals/${initialData.id}`, {
                forceFormData: true,
            });
        } else {
            post('/admin/portals', {
                forceFormData: true,
            });
        }
    };

    const currentDisplayUrl =
        previewUrl || (!data.remove_logo ? initialData?.icon_url : null);
    const hasExistingLogo = Boolean(initialData?.icon_url && !data.remove_logo);
    const showSharedSection =
        data.auth_type === 'shared' || data.auth_type === 'both';

    return (
        <form onSubmit={handleSubmit} className="max-w-5xl space-y-6">
            {/* Section 1: Target Website Information */}
            <div className="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-2.5 border-b border-border pb-2">
                    <Globe className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">
                            Informasi Target Website
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Detail platform eksternal dan alamat login resmi
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="name">
                            Nama Resmi Portal{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            onBlur={() => handleAutoSlug(false)}
                            placeholder="Contoh: SIRS Online Kemkes"
                            className="mt-1"
                            required
                        />
                        {errors.name && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <Label htmlFor="slug">
                                Slug URL{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <button
                                type="button"
                                onClick={() => handleAutoSlug(true)}
                                className="flex items-center gap-1 text-xs text-primary hover:underline"
                            >
                                <Sparkles className="size-3" /> Auto Slug
                            </button>
                        </div>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            placeholder="sirs-online-kemkes"
                            className="mt-1 font-mono text-xs"
                            required
                        />
                        {errors.slug && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.slug}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="category">
                            Kategori Portal{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="category"
                            list="category-suggestions"
                            value={data.category}
                            onChange={(e) =>
                                setData('category', e.target.value)
                            }
                            placeholder="Pilih atau ketik kategori..."
                            className="mt-1"
                            required
                        />
                        <datalist id="category-suggestions">
                            {categoryOptions.map((cat) => (
                                <option key={cat} value={cat} />
                            ))}
                        </datalist>
                        {errors.category && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.category}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="url">
                            URL Form Login Target{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="url"
                            type="url"
                            value={data.url}
                            onChange={(e) => setData('url', e.target.value)}
                            placeholder="https://akun-yankes.kemkes.go.id"
                            className="mt-1 font-mono text-xs"
                            required
                        />
                        {errors.url && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.url}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="url_pattern">
                            URL Match Pattern (Wildcard)
                        </Label>
                        <Input
                            id="url_pattern"
                            value={data.url_pattern}
                            onChange={(e) =>
                                setData('url_pattern', e.target.value)
                            }
                            placeholder="*://*.kemkes.go.id/*"
                            className="mt-1 font-mono text-xs"
                        />
                        <p className="mt-1 text-[11px] text-muted-foreground">
                            Pola tab browser target untuk pencocokan injeksi
                            autofill
                        </p>
                    </div>

                    {/* Logo Uploader Box */}
                    <div className="md:col-span-2 space-y-2 rounded-xl border border-border/80 bg-muted/20 p-4">
                        <Label className="text-sm font-semibold text-foreground">
                            Berkas Logo Portal
                        </Label>
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            {/* 80x80 Preview Box */}
                            <div className="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-background shadow-xs">
                                {currentDisplayUrl ? (
                                    <img
                                        src={currentDisplayUrl}
                                        alt="Preview Logo"
                                        className="size-full object-contain p-1.5"
                                    />
                                ) : (
                                    <Globe className="size-8 text-muted-foreground/60" />
                                )}
                            </div>

                            <div className="flex flex-1 flex-col gap-2">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    onChange={handleFileChange}
                                    className="hidden"
                                    id="portal-logo-input"
                                />

                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            fileInputRef.current?.click()
                                        }
                                        className="gap-1.5 text-xs"
                                    >
                                        <Upload className="size-3.5" />
                                        {hasExistingLogo || data.icon_file
                                            ? 'Ganti Logo'
                                            : 'Pilih Logo'}
                                    </Button>

                                    {data.icon_file && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={handleClearStagedFile}
                                            className="gap-1 text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="size-3.5" /> Batal
                                        </Button>
                                    )}

                                    {isEditing &&
                                        data.icon_file &&
                                        initialData?.id && (
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                size="sm"
                                                disabled={isInstantUploading}
                                                onClick={handleQuickUpload}
                                                className="gap-1.5 text-xs font-medium"
                                            >
                                                <Upload className="size-3.5" />
                                                {isInstantUploading
                                                    ? 'Mengunggah...'
                                                    : 'Unggah Cepat'}
                                            </Button>
                                        )}

                                    {hasExistingLogo && !data.icon_file && (
                                        <>
                                            {isEditing && initialData?.id ? (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={isInstantRemoving}
                                                    onClick={handleInstantRemove}
                                                    className="gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="size-3.5" />
                                                    {isInstantRemoving
                                                        ? 'Menghapus...'
                                                        : 'Hapus Logo Instan'}
                                                </Button>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={handleMarkRemoveLogo}
                                                    className="gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="size-3.5" /> Hapus Logo
                                                </Button>
                                            )}
                                        </>
                                    )}

                                    {data.remove_logo && (
                                        <span className="flex items-center gap-2 text-xs text-destructive">
                                            <span>Logo akan dihapus saat disimpan</span>
                                            <button
                                                type="button"
                                                onClick={handleUndoRemoveLogo}
                                                className="underline hover:text-foreground"
                                            >
                                                Batalkan
                                            </button>
                                        </span>
                                    )}
                                </div>

                                <p className="text-[11px] text-muted-foreground">
                                    Format: PNG, JPG, WEBP, atau SVG (Maks. 2 MB). Disarankan rasio 1:1.
                                </p>

                                {errors.icon_file && (
                                    <p className="text-xs font-medium text-destructive">
                                        {errors.icon_file}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <Label htmlFor="description">
                        Deskripsi Singkat Portal
                    </Label>
                    <Textarea
                        id="description"
                        rows={2}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Deskripsi singkat fungsi pelaporan data pada sistem eksternal ini..."
                        className="mt-1 text-xs"
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="sort_order">
                            Urutan Tampilan (Sort Order)
                        </Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) =>
                                setData(
                                    'sort_order',
                                    parseInt(e.target.value, 10) || 0,
                                )
                            }
                            className="mt-1 w-32"
                        />
                    </div>

                    <div className="flex items-center gap-3 self-center pt-3">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="size-4 rounded border-input text-primary focus:ring-primary"
                        />
                        <Label
                            htmlFor="is_active"
                            className="cursor-pointer text-sm"
                        >
                            Portal Aktif di SIMRS
                        </Label>
                    </div>
                </div>
            </div>

            {/* Section 2: Account Policy & Shared Credentials */}
            <div className="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-2.5 border-b border-border pb-2">
                    <Shield className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">
                            Kebijakan Akun & Autentikasi
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Tentukan apakah login memakai akun bersama RS atau
                            akun personal staf
                        </p>
                    </div>
                </div>

                <div className="max-w-md">
                    <Label htmlFor="auth_type">
                        Kebijakan Login Petugas{' '}
                        <span className="text-destructive">*</span>
                    </Label>
                    <Select
                        value={data.auth_type}
                        onValueChange={(val) =>
                            setData('auth_type', val as PortalAuthType)
                        }
                    >
                        <SelectTrigger className="mt-1">
                            <SelectValue placeholder="Pilih kebijakan login" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="both">
                                Akun Bersama RS & Akun Pribadi (Fleksibel /
                                Hybrid)
                            </SelectItem>
                            <SelectItem value="shared">
                                Hanya Akun Bersama RS (Single Institutional
                                Account)
                            </SelectItem>
                            <SelectItem value="personal">
                                Hanya Akun Pribadi Petugas (Personal Account
                                Only)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.auth_type && (
                        <p className="mt-1 text-xs text-destructive">
                            {errors.auth_type}
                        </p>
                    )}
                </div>

                {showSharedSection && (
                    <div className="space-y-4 rounded-xl border border-border/80 bg-muted/30 p-4">
                        <div className="flex items-center gap-2">
                            <Lock className="size-4 text-amber-500" />
                            <h3 className="text-sm font-semibold text-foreground">
                                Kredensial Bersama Tingkat Rumah Sakit
                            </h3>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Kredensial ini disimpan terenkripsi di database dan
                            diinjeksi ke browser staf yang berwenang.
                        </p>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="shared_username">
                                    Username Bersama RS
                                </Label>
                                <Input
                                    id="shared_username"
                                    value={data.shared_username}
                                    onChange={(e) =>
                                        setData(
                                            'shared_username',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Username akun instansi..."
                                    className="mt-1"
                                />
                                {errors.shared_username && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.shared_username}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="shared_password">
                                    Password Bersama RS
                                    {isEditing &&
                                        initialData?.has_shared_password && (
                                            <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                                (Tersimpan terenkripsi.
                                                Kosongkan jika tidak diubah)
                                            </span>
                                        )}
                                </Label>
                                <div className="relative mt-1">
                                    <Input
                                        id="shared_password"
                                        type={
                                            showPassword ? 'text' : 'password'
                                        }
                                        value={data.shared_password}
                                        onChange={(e) =>
                                            setData(
                                                'shared_password',
                                                e.target.value,
                                            )
                                        }
                                        placeholder={
                                            isEditing &&
                                            initialData?.has_shared_password
                                                ? '••••••••••••'
                                                : 'Ketik password akun bersama...'
                                        }
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowPassword(!showPassword)
                                        }
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        aria-label={
                                            showPassword
                                                ? 'Sembunyikan password'
                                                : 'Tampilkan password'
                                        }
                                    >
                                        {showPassword ? (
                                            <EyeOff className="size-4" />
                                        ) : (
                                            <Eye className="size-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.shared_password && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.shared_password}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Section 3: Login Form Selector Config */}
            <FormConfigEditor
                value={data.form_config}
                onChange={(newConfig) => setData('form_config', newConfig)}
            />

            {/* Actions */}
            <div className="flex items-center justify-between border-t border-border pt-4">
                <Button asChild variant="outline">
                    <Link href="/admin/portals" className="gap-2">
                        <ArrowLeft className="size-4" /> Batal & Kembali
                    </Link>
                </Button>

                <Button type="submit" disabled={processing} className="gap-2">
                    <Check className="size-4" />
                    {processing ? 'Menyimpan...' : 'Simpan'}
                </Button>
            </div>
        </form>
    );
}
```

- [ ] **Step 2: Run TypeScript check on portal pages**

Run: `npx tsc --noEmit | grep -i "portal" || true`  
Expected: Clean output with 0 errors.

- [ ] **Step 3: Commit changes**

```bash
git add resources/js/pages/admin/portals/portal-form.tsx
git commit -m "feat(portal): implement logo uploader and preview in PortalForm"
```

---

### Task 7: Full Test Suite Regression & End-to-End Verification

**Files:**
- Test: All tests in `tests/Feature/PortalPelaporan`
- Build: Frontend assets via `npm run build`

**Interfaces:**
- Consumes: All completed backend and frontend code.
- Produces: 100% passing test suite across all 13 feature test files and successful frontend asset compilation.

- [ ] **Step 1: Run complete Pest test suite for PortalPelaporan**

Run: `php artisan test tests/Feature/PortalPelaporan`  
Expected: All tests pass (approx. 72+ assertions, 0 failures).

- [ ] **Step 2: Run full project test suite check for any regressions**

Run: `php artisan test --filter="Portal"`  
Expected: All tests pass without regressions.

- [ ] **Step 3: Run frontend build verification**

Run: `npm run build`  
Expected: Vite build succeeds with 0 bundle errors.

- [ ] **Step 4: Commit any cleanup or final integration changes**

```bash
git add -A
git commit -m "chore(portal): verify full test suite and frontend build for portal logo upload"
```
