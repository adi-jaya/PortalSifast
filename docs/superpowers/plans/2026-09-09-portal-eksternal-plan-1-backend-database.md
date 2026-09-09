# Portal Pelaporan Eksternal SIMRS - Plan 1: Fondasi Backend & Database

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Catatan Arsitektur:** Dokumen ini merupakan **Bagian 1 dari 4** rencana implementasi modular yang merujuk pada spesifikasi induk: [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md).
>
> **Daftar Rencana Modular:**
> 1. **Plan 1: Fondasi Backend & Database (Core, Migration, Models, Policies, Seeder 8 Website, API Dispatch)** *(Dokumen ini)*
> 2. **Plan 2: Modul Admin (Master Portal CRUD & Form Builder, Mapping Akses Petugas)**
> 3. **Plan 3: Custom Browser Extension Manifest V3 (rs-extension: Background Worker, Content Bridge, Autofill Injector, Heuristic Scanner, Popup Inspector)**
> 4. **Plan 4: Halaman Pengguna (Portal Pelaporan Agregator, Deteksi Ekstensi, Self-Service Kredensial, ZIP Packaging & E2E Testing)**

**Goal:** Membangun fondasi data backend, model Eloquent berenkripsi, policy otorisasi granular, seeder 8 portal eksternal resmi, service dekripsi kredensial one-time, dan endpoint API dispatch kredensial untuk ekstensi browser autofill.

**Architecture:** Menggunakan dua tabel inti `portals` dan `user_portal_credentials` tanpa mengubah skema tabel `users`. Menyimpan kredensial (`shared_password`, `personal_password`) secara aman di database menggunakan enkripsi simetris Eloquent (`'encrypted'`), dilindungi oleh `PortalPolicy` berbasis peran dan mapping akses, serta menyediakan endpoint dispatch satu kali pakai (*one-time transfer*) dan endpoint self-service update password personal.

**Tech Stack:** PHP 8.2+, Laravel 12, Pest 4.3 (`pestphp/pest`), Eloquent Encrypted Casts, Laravel Gate/Policies.

**Spec:** [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md)

## Global Constraints

- **Tabel `users` bawaan tidak diubah skemanya:** Relasi ke portal didefinisikan secara murni pada model Eloquent via pivot table `user_portal_credentials`.
- **Zero-Plaintext Storage:** Seluruh kolom password (`shared_password` dan `personal_password`) WAJIB menggunakan cast `'encrypted'` Eloquent (`Crypt::encryptString`).
- **Otorisasi Ketat:** Petugas tidak dapat memberikan hak akses sendiri; mapping akses hanya ditentukan oleh Admin/IT. Petugas hanya berhak mengelola password personal miliknya sendiri pada portal bertipe `personal` atau `both` yang telah diizinkan.
- **TDD Mandatory:** Setiap komponen diuji terlebih dahulu menggunakan Pest 4 (`it(...)`, `beforeEach(...)`, `expect(...)`) sebelum implementasi ditulis.
- **Format JSON Konsisten:** Respons API dispatch harus mematuhi struktur payload one-time transfer yang ditentukan pada spesifikasi Bagian 5.3.

---

## File Structure & Responsibilities

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── PortalDispatchController.php            # Endpoint POST dispatch-token
│   │   └── PortalPersonalCredentialController.php   # Endpoint PUT personal-credentials
│   └── Requests/
│       └── UpdatePersonalCredentialRequest.php     # Form request validasi credential personal
├── Models/
│   ├── Portal.php                                  # Model master website eksternal & form config
│   ├── UserPortalCredential.php                    # Model mapping user, kredensial personal/shared
│   └── User.php                                    # Tambahan relasi portalCredentials & portals
├── Policies/
│   └── PortalPolicy.php                            # Policy hak akses admin & dispatch kredensial
└── Services/
    └── PortalDispatchService.php                   # Business logic penentuan kredensial & decrypt
database/
├── factories/
│   ├── PortalFactory.php                           # Factory dummy portal
│   └── UserPortalCredentialFactory.php             # Factory dummy mapping user-portal
├── migrations/
│   ├── 2026_09_09_100000_create_portals_table.php  # Skema tabel portals
│   └── 2026_09_09_100001_create_user_portal_credentials_table.php # Skema mapping user
└── seeders/
    ├── DatabaseSeeder.php                          # Pendaftaran PortalSeeder
    └── PortalSeeder.php                            # Seeder 8 kelompok website eksternal
tests/
└── Feature/
    └── PortalPelaporan/
        ├── PortalDatabaseSchemaTest.php            # Pengujian struktur DB & foreign key
        ├── PortalModelTest.php                     # Pengujian casts, enkripsi, dan scopes
        ├── PortalPolicyTest.php                    # Pengujian hak akses admin vs staf
        ├── PortalSeederTest.php                    # Pengujian kelengkapan 8 website seeder
        ├── PortalDispatchServiceTest.php           # Pengujian resolusi shared vs personal
        ├── PortalDispatchApiTest.php               # Pengujian endpoint dispatch token
        └── PortalPersonalCredentialApiTest.php     # Pengujian self-service update credential
```

---

### Task 1: Database Migrations (`portals` & `user_portal_credentials`)

**Files:**
- Create: `database/migrations/2026_09_09_100000_create_portals_table.php`
- Create: `database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php`
- Test: `tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php`

**Interfaces:**
- Consumes: Database connection SQLite/MySQL bawaan Laravel.
- Produces: Tabel `portals` dengan foreign key references pada tabel `user_portal_credentials`.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('creates portals table with expected columns and indices', function (): void {
    expect(Schema::hasTable('portals'))->toBeTrue();

    $expectedColumns = [
        'id',
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
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('portals', $column))
            ->toBeTrue("Column {$column} is missing on portals table.");
    }
});

it('creates user_portal_credentials table with expected columns and foreign keys', function (): void {
    expect(Schema::hasTable('user_portal_credentials'))->toBeTrue();

    $expectedColumns = [
        'id',
        'user_id',
        'portal_id',
        'credential_type',
        'personal_username',
        'personal_password',
        'personal_extra_fields',
        'is_active',
        'notes',
        'created_at',
        'updated_at',
    ];

    foreach ($expectedColumns as $column) {
        expect(Schema::hasColumn('user_portal_credentials', $column))
            ->toBeTrue("Column {$column} is missing on user_portal_credentials table.");
    }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php`  
Expected: FAIL dengan `Failed asserting that false is true` (tabel belum ada).

- [ ] **Step 3: Write minimal implementation**

Buat migrasi `database/migrations/2026_09_09_100000_create_portals_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('category', 100);
            $table->text('url');
            $table->string('url_pattern', 255)->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('auth_type', ['shared', 'personal', 'both'])->default('both');
            $table->string('shared_username', 255)->nullable();
            $table->text('shared_password')->nullable();
            $table->json('shared_extra_fields')->nullable();
            $table->json('form_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
```

Buat migrasi `database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_portal_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('portal_id')->constrained('portals')->cascadeOnDelete();
            $table->enum('credential_type', ['use_shared', 'personal'])->default('use_shared');
            $table->string('personal_username', 255)->nullable();
            $table->text('personal_password')->nullable();
            $table->json('personal_extra_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'portal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_portal_credentials');
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php`  
Expected: PASS (2 passed)

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_09_100000_create_portals_table.php database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php
git commit -m "feat(portal): add database migrations for portals and user_portal_credentials"
```

---

### Task 2: Eloquent Models & User Model Relationships

**Files:**
- Create: `app/Models/Portal.php`
- Create: `app/Models/UserPortalCredential.php`
- Modify: `app/Models/User.php:360-392`
- Test: `tests/Feature/PortalPelaporan/PortalModelTest.php`

**Interfaces:**
- Consumes: Tabel `portals`, `user_portal_credentials`, `users`.
- Produces:
  - `Portal::supportsShared(): bool`
  - `Portal::supportsPersonal(): bool`
  - `Portal::scopeActive($query)`
  - `Portal::scopeOrdered($query)`
  - `UserPortalCredential::isPersonal(): bool`
  - `UserPortalCredential::isShared(): bool`
  - `User::portalCredentials(): HasMany`
  - `User::portals(): BelongsToMany`

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalModelTest.php`:

```php
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
        ->and($user->portals->first()->name)->toBe('MPDN');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: FAIL dengan `Class "App\Models\Portal" not found`.

- [ ] **Step 3: Write minimal implementation**

Buat `app/Models/Portal.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function userCredentials(): HasMany
    {
        return $this->hasMany(UserPortalCredential::class, 'portal_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_portal_credentials', 'portal_id', 'user_id')
            ->withPivot(['id', 'credential_type', 'personal_username', 'personal_password', 'personal_extra_fields', 'is_active', 'notes'])
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

Buat `app/Models/UserPortalCredential.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPortalCredential extends Model
{
    /** @use HasFactory<\Database\Factories\UserPortalCredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'portal_id',
        'credential_type',
        'personal_username',
        'personal_password',
        'personal_extra_fields',
        'is_active',
        'notes',
    ];

    protected $hidden = [
        'personal_password',
    ];

    protected function casts(): array
    {
        return [
            'personal_password' => 'encrypted',
            'personal_extra_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class, 'portal_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPersonal(): bool
    {
        return $this->credential_type === 'personal';
    }

    public function isShared(): bool
    {
        return $this->credential_type === 'use_shared';
    }
}
```

Tambahkan relasi ke dalam `app/Models/User.php` sebelum kurung tutup kelas terakhir:

```php
    // ==================== PORTAL PELAPORAN RELATIONSHIPS ====================

    /**
     * Kredensial dan hak akses portal eksternal user ini
     */
    public function portalCredentials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\UserPortalCredential::class, 'user_id');
    }

    /**
     * Portal eksternal yang diakses oleh user ini melalui pivot credentials
     */
    public function portals(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Portal::class, 'user_portal_credentials', 'user_id', 'portal_id')
            ->withPivot(['id', 'credential_type', 'personal_username', 'personal_password', 'personal_extra_fields', 'is_active', 'notes'])
            ->withTimestamps();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add app/Models/Portal.php app/Models/UserPortalCredential.php app/Models/User.php tests/Feature/PortalPelaporan/PortalModelTest.php
git commit -m "feat(portal): implement Portal and UserPortalCredential models with encryption and relations"
```

---

### Task 3: Model Factories (`PortalFactory` & `UserPortalCredentialFactory`)

**Files:**
- Create: `database/factories/PortalFactory.php`
- Create: `database/factories/UserPortalCredentialFactory.php`
- Test: `tests/Feature/PortalPelaporan/PortalModelTest.php`

**Interfaces:**
- Consumes: Models `Portal` dan `UserPortalCredential`.
- Produces: Fluent factory states (`shared()`, `personal()`, `inactive()`, `withCredentials()`).

- [ ] **Step 1: Write the failing test**

Tambahkan pengujian factory pada `tests/Feature/PortalPelaporan/PortalModelTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: FAIL dengan `Class "Database\Factories\PortalFactory" not found`.

- [ ] **Step 3: Write minimal implementation**

Buat `database/factories/PortalFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Portal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Portal>
 */
class PortalFactory extends Factory
{
    protected $model = Portal::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(['Kemenkes', 'BKKBN', 'Mutu & Akreditasi']),
            'url' => fake()->url(),
            'url_pattern' => null,
            'icon_path' => null,
            'description' => fake()->sentence(),
            'auth_type' => 'both',
            'shared_username' => 'rs_shared_user',
            'shared_password' => 'secret123',
            'shared_extra_fields' => null,
            'form_config' => [
                'is_spa' => false,
                'username_field' => ['selectors' => ['#username', "input[name='username']"]],
                'password_field' => ['selectors' => ['#password', "input[name='password']"]],
                'auto_submit' => false,
            ],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_type' => 'shared',
            'shared_username' => 'rs_instansi_account',
            'shared_password' => 'SharedPasswordRS!',
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_type' => 'personal',
            'shared_username' => null,
            'shared_password' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

Buat `database/factories/UserPortalCredentialFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPortalCredential>
 */
class UserPortalCredentialFactory extends Factory
{
    protected $model = UserPortalCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'portal_id' => Portal::factory(),
            'credential_type' => 'use_shared',
            'personal_username' => null,
            'personal_password' => null,
            'personal_extra_fields' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'credential_type' => 'personal',
            'personal_username' => fake()->userName(),
            'personal_password' => 'PersonalSecret123!',
        ]);
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'credential_type' => 'use_shared',
            'personal_username' => null,
            'personal_password' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php`  
Expected: PASS (5 passed)

- [ ] **Step 5: Commit**

```bash
git add database/factories/PortalFactory.php database/factories/UserPortalCredentialFactory.php tests/Feature/PortalPelaporan/PortalModelTest.php
git commit -m "feat(portal): add factories for Portal and UserPortalCredential"
```

---

### Task 4: Authorization Policy (`PortalPolicy`)

**Files:**
- Create: `app/Policies/PortalPolicy.php`
- Test: `tests/Feature/PortalPelaporan/PortalPolicyTest.php`

**Interfaces:**
- Consumes: `User`, `Portal`, `UserPortalCredential`.
- Produces:
  - `viewAnyAdmin(User $user): bool`
  - `manage(User $user): bool`
  - `view(User $user, Portal $portal): bool`
  - `dispatchToken(User $user, Portal $portal): bool`
  - `updatePersonalCredential(User $user, Portal $portal): bool`

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalPolicyTest.php`:

```php
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
    $portal = Portal::factory()->create();

    expect(Gate::forUser($this->admin)->allows('manage', Portal::class))->toBeTrue()
        ->and(Gate::forUser($this->staff)->allows('manage', Portal::class))->toBeFalse();
});

it('allows user to view portal only if active mapping exists', function (): void {
    $portal = Portal::factory()->create(['is_active' => true]);

    expect(Gate::forUser($this->staff)->allows('view', $portal))->toBeFalse();

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $portal->id,
        'is_active' => true,
    ]);

    expect(Gate::forUser($this->staff)->allows('view', $portal))->toBeTrue()
        ->and(Gate::forUser($this->otherStaff)->allows('view', $portal))->toBeFalse();
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalPolicyTest.php`  
Expected: FAIL dengan `This action is unauthorized` / policy not found.

- [ ] **Step 3: Write minimal implementation**

Buat `app/Policies/PortalPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Portal;
use App\Models\User;

class PortalPolicy
{
    /**
     * Menentukan apakah user berhak mengelola master portal (Admin/SuperAdmin).
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function viewAny(User $user): bool
    {
        return true; // Setiap user login dapat membuka halaman portal agregator
    }

    /**
     * Menentukan apakah user berhak melihat detail portal tertentu.
     */
    public function view(User $user, Portal $portal): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if (! $portal->is_active) {
            return false;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Portal $portal): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Portal $portal): bool
    {
        return $this->manage($user);
    }

    /**
     * Menentukan apakah user berhak menerima dispatch token & kredensial ke ekstensi.
     */
    public function dispatchToken(User $user, Portal $portal): bool
    {
        if (! $portal->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Menentukan apakah user berhak memperbarui kredensial personal untuk portal ini.
     */
    public function updatePersonalCredential(User $user, Portal $portal): bool
    {
        if (! $portal->is_active || ! $portal->supportsPersonal()) {
            return false;
        }

        return $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalPolicyTest.php`  
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add app/Policies/PortalPolicy.php tests/Feature/PortalPelaporan/PortalPolicyTest.php
git commit -m "feat(portal): implement PortalPolicy for admin management and granular credential dispatch authorization"
```

---

### Task 5: Database Seeder for 8 External Portals (`PortalSeeder`)

**Files:**
- Create: `database/seeders/PortalSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/PortalPelaporan/PortalSeederTest.php`

**Interfaces:**
- Consumes: Model `Portal`.
- Produces: Data portal awal untuk 8 kelompok website eksternal yang terdaftar pada tabel `portals`.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalSeederTest.php`:

```php
<?php

use App\Models\Portal;
use Database\Seeders\PortalSeeder;

it('seeds all 8 portal groups idempotently with proper configurations', function (): void {
    $this->seed(PortalSeeder::class);

    $expectedSlugs = [
        'sirika-bkkbn',
        'siga-kemendukbangga',
        'siha-kemenkes',
        'mpdn-kemenkes',
        'sitb-kemenkes',
        'sigizi-kemenkes',
        'satusehat-kemenkes',
        'mutufasyankes-ikp',
        'mutufasyankes-ppra',
        'mutufasyankes-simar',
        'sirs-online',
    ];

    foreach ($expectedSlugs as $slug) {
        expect(Portal::where('slug', $slug)->exists())
            ->toBeTrue("Portal with slug {$slug} was not seeded.");
    }

    // Verifikasi konfigurasi SIRS Online (SPA tanpa attribute name/id)
    $sirs = Portal::where('slug', 'sirs-online')->first();
    expect($sirs->form_config['is_spa'])->toBeTrue()
        ->and($sirs->form_config['username_field']['selectors'])->toContain("input[type='email']")
        ->and($sirs->form_config['password_field']['selectors'])->toContain("input[type='password']");

    // Menjalankan seeder kedua kali tidak boleh menimbulkan error duplikasi
    $this->seed(PortalSeeder::class);
    expect(Portal::where('slug', 'sirs-online')->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalSeederTest.php`  
Expected: FAIL dengan `Target class [Database\Seeders\PortalSeeder] does not exist`.

- [ ] **Step 3: Write minimal implementation**

Buat `database/seeders/PortalSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Portal;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $portals = [
            [
                'name' => 'SIRIKA (BKKBN)',
                'slug' => 'sirika-bkkbn',
                'category' => 'BKKBN',
                'url' => 'https://siga-sirika.bkkbn.go.id/login',
                'url_pattern' => '*://siga-sirika.bkkbn.go.id/*',
                'description' => 'Sistem Informasi Rekonsiliasi Intervensi Penurunan Stunting BKKBN',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_sirika',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#c', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'New SIGA (Kemendukbangga)',
                'slug' => 'siga-kemendukbangga',
                'category' => 'BKKBN',
                'url' => 'https://newsiga-siga.kemendukbangga.go.id/#/login',
                'url_pattern' => '*://newsiga-siga.kemendukbangga.go.id/*',
                'description' => 'Sistem Informasi Keluarga Kementerian Kependudukan dan Pembangunan Keluarga',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_siga',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#email', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'SIHA 2.1 (Kemenkes)',
                'slug' => 'siha-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://sihapims2.kemkes.go.id/login',
                'url_pattern' => '*://sihapims2.kemkes.go.id/*',
                'description' => 'Sistem Informasi HIV/AIDS dan IMS Kementerian Kesehatan RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_siha',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']", "input[name='user']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'MPDN (Kemenkes)',
                'slug' => 'mpdn-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://mpdn.kemkes.go.id/masuk',
                'url_pattern' => '*://mpdn.kemkes.go.id/*',
                'description' => 'Maternal Perinatal Death Notification Kementerian Kesehatan RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_mpdn',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']", "input[name='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 4,
            ],
            [
                'name' => 'SITB Jatim (Kemenkes)',
                'slug' => 'sitb-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://jatim.sitb.id/sitb2024/app',
                'url_pattern' => '*://jatim.sitb.id/*',
                'description' => 'Sistem Informasi Tuberkulosis Kementerian Kesehatan Jawa Timur',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_sitb',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[name='username']", '#username'],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 5,
            ],
            [
                'name' => 'SIGIZI Terpadu (Kemenkes)',
                'slug' => 'sigizi-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://sigizikesga-stg.kemkes.go.id',
                'url_pattern' => '*://sigizikesga-stg.kemkes.go.id/*',
                'description' => 'Sistem Informasi Gizi Terpadu Kesehatan Keluarga Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_sigizi',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#username', "input[name='username']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 6,
            ],
            [
                'name' => 'SATU SEHAT Platform (Kemenkes)',
                'slug' => 'satusehat-kemenkes',
                'category' => 'Kemenkes',
                'url' => 'https://satusehat.kemkes.go.id/platform/login',
                'url_pattern' => '*://satusehat.kemkes.go.id/*',
                'description' => 'Platform Integrasi Data Kesehatan SATU SEHAT Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_satusehat',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#email', "input[name='email']", "input[type='email']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#password', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 7,
            ],
            [
                'name' => 'MutuFasyankes - IKP (Kemenkes)',
                'slug' => 'mutufasyankes-ikp',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/halaman/dashboard',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Pelaporan Insiden Keselamatan Pasien (IKP) Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_ikp',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#user', "input[name='username']", "input[name='user']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#pass', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 8,
            ],
            [
                'name' => 'MutuFasyankes - PPRA (Kemenkes)',
                'slug' => 'mutufasyankes-ppra',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/ppra/',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Program Pengendalian Resistensi Antimikroba (PPRA) Kemenkes RI',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_ppra',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[name='username']", '#username'],
                    ],
                    'password_field' => [
                        'selectors' => ["input[name='password']", '#password', "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 9,
            ],
            [
                'name' => 'MutuFasyankes - SIMAR (Kemenkes)',
                'slug' => 'mutufasyankes-simar',
                'category' => 'Mutu & Akreditasi',
                'url' => 'https://mutufasyankes.kemkes.go.id/simar/',
                'url_pattern' => '*://mutufasyankes.kemkes.go.id/*',
                'description' => 'Sistem Informasi Manajemen Akreditasi Rumah Sakit (SIMAR)',
                'auth_type' => 'both',
                'shared_username' => 'rs_sifast_simar',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => false,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ['#uname', "input[name='username']", "input[name='uname']"],
                    ],
                    'password_field' => [
                        'selectors' => ['#pwd', "input[name='password']", "input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 10,
            ],
            [
                'name' => 'SIRS Online (Yankes Kemenkes)',
                'slug' => 'sirs-online',
                'category' => 'Kemenkes',
                'url' => 'https://akun-yankes.kemkes.go.id/',
                'url_pattern' => '*://akun-yankes.kemkes.go.id/*',
                'description' => 'Sistem Informasi Rumah Sakit Online Kementerian Kesehatan RI',
                'auth_type' => 'shared',
                'shared_username' => 'rs_sifast_sirs',
                'shared_password' => 'GantiPasswordSegera!',
                'form_config' => [
                    'is_spa' => true,
                    'wait_timeout_ms' => 10000,
                    'username_field' => [
                        'selectors' => ["input[type='email']", "input[type='text']", "input[placeholder*='email' i]"],
                    ],
                    'password_field' => [
                        'selectors' => ["input[type='password']"],
                    ],
                    'auto_submit' => false,
                ],
                'sort_order' => 11,
            ],
        ];

        foreach ($portals as $portal) {
            Portal::updateOrCreate(
                ['slug' => $portal['slug']],
                $portal
            );
        }
    }
}
```

Daftarkan `PortalSeeder` ke dalam `database/seeders/DatabaseSeeder.php`:

```php
$this->call([
    PortalSeeder::class,
]);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalSeederTest.php`  
Expected: PASS (1 passed)

- [ ] **Step 5: Commit**

```bash
git add database/seeders/PortalSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/PortalPelaporan/PortalSeederTest.php
git commit -m "feat(portal): seed 8 official reporting portals with robust selector configurations"
```

---

### Task 6: Portal Credential Dispatch Service (`PortalDispatchService`)

**Files:**
- Create: `app/Services/PortalDispatchService.php`
- Test: `tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php`

**Interfaces:**
- Consumes: `User`, `Portal`, `UserPortalCredential`.
- Produces: `PortalDispatchService::dispatch(User $user, Portal $portal): array` yang mendekripsi password dan mengemas payload standar ekstensi.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\PortalDispatchService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function (): void {
    $this->service = new PortalDispatchService();
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php`  
Expected: FAIL dengan `Class "App\Services\PortalDispatchService" not found`.

- [ ] **Step 3: Write minimal implementation**

Buat `app/Services/PortalDispatchService.php`:

```php
<?php

namespace App\Services;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PortalDispatchService
{
    /**
     * Membangun payload aman one-time transfer kredensial untuk browser extension.
     *
     * @return array<string, mixed>
     */
    public function dispatch(User $user, Portal $portal): array
    {
        if (! $portal->is_active) {
            throw new AccessDeniedHttpException('Portal pelaporan ini sedang nonaktif.');
        }

        /** @var UserPortalCredential|null $credential */
        $credential = $portal->userCredentials()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // Admin diizinkan menggunakan shared credential jika belum memiliki mapping eksplisit
        if (! $credential && ($user->isAdmin() || $user->isSuperAdmin())) {
            return $this->buildSharedPayload($portal);
        }

        if (! $credential) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin akses aktif ke portal ini.');
        }

        if ($credential->isPersonal() && $portal->supportsPersonal()) {
            return [
                'success' => true,
                'portal' => $this->formatPortalMeta($portal),
                'credentials' => [
                    'type' => 'personal',
                    'username' => (string) $credential->personal_username,
                    'password' => (string) $credential->personal_password,
                    'extra_fields' => $credential->personal_extra_fields ?? [],
                ],
                'dispatched_at' => now()->toIso8601String(),
            ];
        }

        return $this->buildSharedPayload($portal);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSharedPayload(Portal $portal): array
    {
        return [
            'success' => true,
            'portal' => $this->formatPortalMeta($portal),
            'credentials' => [
                'type' => 'shared',
                'username' => (string) $portal->shared_username,
                'password' => (string) $portal->shared_password,
                'extra_fields' => $portal->shared_extra_fields ?? [],
            ],
            'dispatched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPortalMeta(Portal $portal): array
    {
        return [
            'id' => $portal->id,
            'name' => $portal->name,
            'slug' => $portal->slug,
            'category' => $portal->category,
            'url' => $portal->url,
            'url_pattern' => $portal->url_pattern,
            'form_config' => $portal->form_config ?? [],
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php`  
Expected: PASS (3 passed)

- [ ] **Step 5: Commit**

```bash
git add app/Services/PortalDispatchService.php tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php
git commit -m "feat(portal): implement PortalDispatchService for secure one-time credential payload generation"
```

---

### Task 7: Dispatch API Controller & Route

**Files:**
- Create: `app/Http/Controllers/PortalDispatchController.php`
- Modify: `routes/web.php:140-165`
- Test: `tests/Feature/PortalPelaporan/PortalDispatchApiTest.php`

**Interfaces:**
- Consumes: `POST /portal-pelaporan/{portal}/dispatch-token` (Auth middleware).
- Produces: Response JSON dengan status 200 OK atau 403 Forbidden.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalDispatchApiTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;

beforeEach(function (): void {
    $this->user = User::factory()->staff()->create();
    $this->portal = Portal::factory()->create([
        'name' => 'SIRIKA BKKBN',
        'slug' => 'sirika-bkkbn',
        'is_active' => true,
        'shared_username' => 'bkkbn_user',
        'shared_password' => 'SecretBkkbn#1',
    ]);
});

it('rejects unauthenticated requests with redirect/unauthorized', function (): void {
    $this->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertUnauthorized();
});

it('rejects user without mapping with 403 forbidden', function (): void {
    $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertForbidden();
});

it('successfully dispatches credentials to authorized user', function (): void {
    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('portal.slug', 'sirika-bkkbn')
        ->assertJsonPath('credentials.username', 'bkkbn_user')
        ->assertJsonPath('credentials.password', 'SecretBkkbn#1');
});

it('rejects dispatch token if portal is inactive with 403', function (): void {
    $this->portal->update(['is_active' => false]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->postJson("/portal-pelaporan/{$this->portal->id}/dispatch-token")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDispatchApiTest.php`  
Expected: FAIL dengan `404 Not Found`.

- [ ] **Step 3: Write minimal implementation**

Buat `app/Http/Controllers/PortalDispatchController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Portal;
use App\Services\PortalDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortalDispatchController extends Controller
{
    public function dispatch(Request $request, Portal $portal, PortalDispatchService $service): JsonResponse
    {
        Gate::authorize('dispatchToken', $portal);

        $payload = $service->dispatch($request->user(), $portal);

        return response()->json($payload);
    }
}
```

Tambahkan route pada `routes/web.php` di dalam grup `Route::middleware(['auth', 'verified'])->group(...)`:

```php
    // Portal Pelaporan Eksternal
    Route::post('portal-pelaporan/{portal}/dispatch-token', [App\Http\Controllers\PortalDispatchController::class, 'dispatch'])
        ->name('portal-pelaporan.dispatch-token');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalDispatchApiTest.php`  
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PortalDispatchController.php routes/web.php tests/Feature/PortalPelaporan/PortalDispatchApiTest.php
git commit -m "feat(portal): add dispatch-token endpoint for browser extension autofill payload"
```

---

### Task 8: Self-Service Personal Credential Update Endpoint

**Files:**
- Create: `app/Http/Requests/UpdatePersonalCredentialRequest.php`
- Create: `app/Http/Controllers/PortalPersonalCredentialController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php`

**Interfaces:**
- Consumes: `PUT /portal-pelaporan/{portal}/personal-credentials` (Auth middleware).
- Produces: Pembaruan data `personal_username` dan `personal_password` terenkripsi pada `user_portal_credentials`.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;

beforeEach(function (): void {
    $this->user = User::factory()->staff()->create();
    $this->portal = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'slug' => 'mpdn-kemenkes',
        'auth_type' => 'both',
        'is_active' => true,
    ]);
});

it('rejects unauthenticated user', function (): void {
    $this->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
        'username' => 'dr.fatimah',
        'password' => 'DokterPass123',
    ])->assertUnauthorized();
});

it('rejects user without mapping with 403 forbidden', function (): void {
    $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.fatimah',
            'password' => 'DokterPass123',
        ])->assertForbidden();
});

it('allows authorized user to update personal username and password', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.fatimah@rsasf.co.id',
            'password' => 'PasswordBaruDokter#2026',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $mapping->refresh();
    expect($mapping->credential_type)->toBe('personal')
        ->and($mapping->personal_username)->toBe('dr.fatimah@rsasf.co.id')
        ->and($mapping->personal_password)->toBe('PasswordBaruDokter#2026');
});

it('preserves existing password when updating username with null password', function (): void {
    $mapping = UserPortalCredential::factory()->create([
        'user_id' => $this->user->id,
        'portal_id' => $this->portal->id,
        'credential_type' => 'personal',
        'personal_username' => 'dr.lama',
        'personal_password' => 'PasswordTetapAda',
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->putJson("/portal-pelaporan/{$this->portal->id}/personal-credentials", [
            'username' => 'dr.baru@rsasf.co.id',
            'password' => null,
        ])->assertOk();

    $mapping->refresh();
    expect($mapping->personal_username)->toBe('dr.baru@rsasf.co.id')
        ->and($mapping->personal_password)->toBe('PasswordTetapAda');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php`  
Expected: FAIL dengan `404 Not Found`.

- [ ] **Step 3: Write minimal implementation**

Buat `app/Http/Requests/UpdatePersonalCredentialRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi dikendalikan oleh Gate di Controller
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'extra_fields' => ['nullable', 'array'],
        ];
    }
}
```

Buat `app/Http/Controllers/PortalPersonalCredentialController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePersonalCredentialRequest;
use App\Models\Portal;
use App\Models\UserPortalCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PortalPersonalCredentialController extends Controller
{
    public function update(UpdatePersonalCredentialRequest $request, Portal $portal): JsonResponse
    {
        Gate::authorize('updatePersonalCredential', $portal);

        /** @var UserPortalCredential $credential */
        $credential = UserPortalCredential::where('user_id', $request->user()->id)
            ->where('portal_id', $portal->id)
            ->firstOrFail();

        $updateData = [
            'credential_type' => 'personal',
            'personal_username' => $request->validated('username'),
        ];

        if ($request->filled('password')) {
            $updateData['personal_password'] = $request->validated('password');
        }

        if ($request->has('extra_fields')) {
            $updateData['personal_extra_fields'] = $request->validated('extra_fields');
        }

        $credential->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Kredensial personal portal berhasil disimpan.',
        ]);
    }
}
```

Tambahkan route pada `routes/web.php`:

```php
    Route::put('portal-pelaporan/{portal}/personal-credentials', [App\Http\Controllers\PortalPersonalCredentialController::class, 'update'])
        ->name('portal-pelaporan.personal-credentials.update');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php`  
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/UpdatePersonalCredentialRequest.php app/Http/Controllers/PortalPersonalCredentialController.php routes/web.php tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php
git commit -m "feat(portal): add self-service personal credential update endpoint"
```

---

### Task 9: Code Formatting, Linting & Full Regression Verification

**Files:**
- Modify: (Semua berkas yang baru dibuat/diubah)

- [ ] **Step 1: Run Pint code linter**

Run: `vendor/bin/pint`  
Expected: All files formatted according to Laravel Pint standard without errors.

- [ ] **Step 2: Run complete Portal Pelaporan test suite**

Run: `php artisan test --filter=Portal`  
Expected: Semua pengujian pada `tests/Feature/PortalPelaporan/` lolos (PASS).

- [ ] **Step 3: Run existing regression tests**

Run: `php artisan test tests/Feature/Api/WebOfficialApiTest.php`  
Expected: 17 passed.

- [ ] **Step 4: Commit cleanup if Pint made formatting changes**

```bash
git add -u
git commit -m "style: apply pint formatting on portal pelaporan backend files" || echo "No changes to commit"
```

---

## Self-Review Checklist

1. **Spec Coverage:**
   - Tabel `portals` sesuai Section 2.1: Diliput di Task 1.
   - Tabel `user_portal_credentials` sesuai Section 2.2: Diliput di Task 1.
   - Tabel `users` tidak diubah skemanya: Diliput di Task 1 & Task 2.
   - Enkripsi `shared_password` & `personal_password` (`Crypt::encryptString` / Eloquent encrypted): Diliput di Task 2.
   - Seeder 8 kelompok website eksternal sesuai Section 3: Diliput di Task 5.
   - Kebijakan otorisasi admin vs self-service sesuai Section 2.3: Diliput di Task 4 & Task 8.
   - Endpoint `POST /portal-pelaporan/{portal}/dispatch-token` sesuai Section 5.3: Diliput di Task 6 & Task 7.
   - Endpoint `PUT /portal-pelaporan/{portal}/personal-credentials` sesuai Section 2.3: Diliput di Task 8.
2. **Placeholder Scan:** Tidak ada kata kunci "TODO", "TBD", "sesuaikan", atau fungsi tanpa implementasi nyata. Semua skrip dan tes tertulis penuh.
3. **Type Consistency:** Method signatures (`supportsShared()`, `supportsPersonal()`, `dispatch()`) dan nama kolom konsisten di seluruh model, policy, controller, dan test.
