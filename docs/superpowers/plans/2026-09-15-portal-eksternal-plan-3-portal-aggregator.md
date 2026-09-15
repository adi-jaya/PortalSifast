# Portal Pelaporan Eksternal SIMRS - Plan 3: Halaman Pengguna (Portal Agregator, Deteksi Ekstensi & Self-Service Kredensial)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Catatan Arsitektur:** Dokumen ini merupakan **Bagian 3 dari 4** rencana implementasi modular yang merujuk pada spesifikasi induk: [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md).
>
> **Daftar Rencana Modular:**
> 1. **Plan 1: Fondasi Backend & Database** *(Selesai - 29 Tests PASS)*
> 2. **Plan 2: Modul Admin (Master Portal & Mapping Akses)** *(Selesai - 33 Tests PASS, Total 62 Tests PASS)*
> 3. **Addendum: Penyimpanan & Pengunggahan Berkas Logo Portal** *(Selesai - 16 Tests PASS, Total 78 Tests PASS)*
> 4. **Plan 3: Halaman Pengguna (Portal Agregator, Deteksi Ekstensi di UI React & Self-Service Kredensial)** *(Dokumen ini)*
> 5. **Plan 4: Custom Browser Extension Manifest V3 (`rs-extension/`), Distribusi ZIP & Verifikasi E2E** *(Tahap Akhir - Dokumen Plan Telah Siap)*

**Goal:** Membangun antarmuka pengguna SIMRS untuk Portal Pelaporan Eksternal (`/portal-pelaporan`) yang responsif dan aman bagi staf rumah sakit, dilengkapi deteksi aktif ekstensi browser via DOM dataset & event handshake, grid kartu portal dengan filter instan dan fallback direct link, modal self-service kredensial personal, serta integrasi navigasi sidebar.

**Architecture:** Menggunakan arsitektur Service Class Layer (`PortalAggregatorService`) di bawah `App\Services\Portal` yang diinjeksi ke `PortalAggregatorController` melalui Constructor Injection. Halaman frontend dibangun menggunakan Inertia.js React 19, TypeScript, Tailwind CSS v4, Radix UI, dan Lucide Icons. Deteksi ekstensi browser menggunakan custom hook `useExtensionDetection` yang memantau DOM dataset `dataset.sifastExtensionInstalled` serta custom events (`SIFAST_EXTENSION_READY`, `SIFAST_PING_EXTENSION`, `SIFAST_PONG_EXTENSION`). Peluncuran portal memicu event `SIFAST_PORTAL_LAUNCH` setelah memanggil API dispatch token jika ekstensi terpasang, atau fallback langsung membuka URL target jika belum terpasang.

**Tech Stack:** Laravel 12, PHP 8.2+, Pest 4 (`pestphp/pest`), Inertia.js React 19, TypeScript, Tailwind CSS v4, Radix UI primitives (`@radix-ui/react-*`), Lucide React.

**Spec:** [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md)

---

## Global Constraints

- **Strict Access Filtering:** Staf rumah sakit non-admin hanya berhak melihat portal yang berstatus aktif (`portals.is_active = true`) DAN memiliki izin aktif pada tabel pivot (`user_portal_credentials.is_active = true`). Administrator (`role === 'admin'` atau `isSuperAdmin()`) berhak melihat seluruh portal aktif (dengan fallback ke akun bersama RS untuk portal bertipe `shared` atau `both`).
- **Zero-Plaintext Leakage:** Plaintext password TIDAK BOLEH dikirimkan dalam props Inertia halaman agregator. Kartu portal hanya memuat metadata publik, `credential_type`, `personal_username` (jika ada), dan flag boolean ketersediaan kredensial. Plaintext kredensial hanya dikirimkan satu kali secara terenkripsi/aman melalui endpoint API dispatch token (`POST /portal-pelaporan/{portal}/dispatch-token`) saat tombol "Buka Portal" ditekan.
- **Resilient Extension Handshake:** UI React tidak boleh bergantung pada reload halaman penuh untuk mendeteksi ekstensi. Deteksi wajib mengombinasikan:
  1. Pemeriksaan atribut dataset pada `document.documentElement.dataset.sifastExtensionInstalled`.
  2. Event listener untuk event `SIFAST_EXTENSION_READY`.
  3. Handshake proaktif dengan mengirim event `SIFAST_PING_EXTENSION` dan menunggu respon `SIFAST_PONG_EXTENSION`.
- **Graceful Degradation (Fallback Launch):** Jika ekstensi browser belum terpasang atau belum aktif, tombol "Buka Portal" tetap dapat digunakan untuk membuka URL website target secara langsung pada tab baru (`window.open(portal.url, '_blank', 'noopener,noreferrer')`), disertai banner informasi panduan instalasi ekstensi.
- **Strict Self-Service Boundary:** Modal "Atur Akun Pribadi" hanya boleh diakses untuk portal bertipe `personal` atau `both` di mana pengguna memiliki hak akses aktif. Perubahan kredensial disimpan melalui endpoint `PUT /portal-pelaporan/{portal}/personal-credentials` yang tidak menimpa password jika input password dikosongkan.
- **Universal Staff Navigation:** Link menu navigasi "Portal Pelaporan" ditambahkan ke menu sidebar utama SIMRS (`resources/js/components/app-sidebar.tsx`) sehingga dapat diakses oleh seluruh staf rumah sakit yang telah login tanpa memerlukan permission admin.
- **TDD Mandatory:** Setiap service class, controller, dan integrasi endpoint diuji secara komprehensif menggunakan Pest 4 sebelum implementasi difinalisasi.

---

## File Structure & Responsibilities

```
app/
├── Http/
│   └── Controllers/
│       └── PortalAggregatorController.php           # Controller halaman agregator portal (Constructor injection: PortalAggregatorService)
└── Services/
    └── Portal/
        └── PortalAggregatorService.php              # Query portal aktif pengguna, resolusi kredensial, filter & format kartu
resources/
├── js/
│   ├── types/
│   │   └── portal.ts                                # Type definitions: PortalCardItem, ExtensionStatus
│   ├── components/
│   │   ├── app-sidebar.tsx                          # Integrasi menu navigasi sidebar SIMRS
│   │   └── portal/
│   │       ├── use-extension-detection.ts           # Custom React hook deteksi ekstensi via dataset & event ping-pong
│   │       ├── extension-status-badge.tsx           # Badge indikator status aktif/belum terpasang
│   │       ├── extension-guide-banner.tsx           # Banner panduan instalasi jika ekstensi belum terpasang
│   │       ├── extension-install-dialog.tsx         # Modal dialog petunjuk instalasi browser extension
│   │       ├── portal-card.tsx                      # Komponen kartu portal individual responsif
│   │       └── personal-credential-dialog.tsx       # Modal form self-service username & password personal
│   └── pages/
│       └── portal-pelaporan/
│           └── index.tsx                            # Halaman utama agregator portal pelaporan eksternal
routes/
└── web.php                                          # Pendaftaran route GET /portal-pelaporan
tests/
└── Feature/
    └── PortalPelaporan/
        ├── PortalAggregatorServiceTest.php          # Pest test query akses, filtering, dan formatting kartu
        └── PortalAggregatorControllerTest.php       # Pest test HTTP endpoint, otorisasi, dan props Inertia
```

---

### Task 1: Backend PortalAggregatorService (Query, Filtering, dan Formatting Kartu Portal)

**Files:**
- Create: `app/Services/Portal/PortalAggregatorService.php`
- Test: `tests/Feature/PortalPelaporan/PortalAggregatorServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\Portal`, `App\Models\User`, `App\Models\UserPortalCredential`.
- Produces:
  - `PortalAggregatorService::getUserPortals(User $user, ?string $category = null, ?string $search = null): array`
  - `PortalAggregatorService::getCategoriesForUser(User $user): array`
  - `PortalAggregatorService::formatPortalCard(Portal $portal, User $user, ?UserPortalCredential $credential = null): array`

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalAggregatorServiceTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\PortalAggregatorService;

beforeEach(function (): void {
    $this->service = new PortalAggregatorService();

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
```

- [ ] **Step 2: Run test to verify it fails**

Jalankan perintah pengujian:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan/PortalAggregatorServiceTest.php
```
Ekspektasi: Gagal (*Class "App\Services\Portal\PortalAggregatorService" not found*).

- [ ] **Step 3: Write minimal implementation**

Buat file `app/Services/Portal/PortalAggregatorService.php`:

```php
<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Database\Eloquent\Builder;

class PortalAggregatorService
{
    /**
     * Mengambil daftar portal pelaporan aktif yang berhak diakses oleh user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserPortals(User $user, ?string $category = null, ?string $search = null): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $query = Portal::query()->where('is_active', true);

        if (! $isAdmin) {
            $query->whereHas('userCredentials', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->where('is_active', true);
            });
        }

        $query->with(['userCredentials' => function ($q) use ($user): void {
            $q->where('user_id', $user->id)
                ->where('is_active', true);
        }]);

        if (filled($category) && $category !== 'all') {
            $query->where('category', trim((string) $category));
        }

        if (filled($search)) {
            $term = trim((string) $search);
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        $portals = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return $portals->map(function (Portal $portal) use ($user): array {
            /** @var UserPortalCredential|null $credential */
            $credential = $portal->userCredentials->first();

            return $this->formatPortalCard($portal, $user, $credential);
        })->values()->all();
    }

    /**
     * Mengambil daftar kategori unik yang tersedia dari portal yang diizinkan untuk user ini.
     *
     * @return list<string>
     */
    public function getCategoriesForUser(User $user): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $query = Portal::query()->where('is_active', true);

        if (! $isAdmin) {
            $query->whereHas('userCredentials', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->where('is_active', true);
            });
        }

        /** @var list<string> $categories */
        $categories = $query->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn (string $cat): string => trim($cat))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $categories;
    }

    /**
     * Memformat objek Portal menjadi payload kartu responsif antarmuka pengguna.
     *
     * @return array<string, mixed>
     */
    public function formatPortalCard(Portal $portal, User $user, ?UserPortalCredential $credential = null): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $credentialType = $credential?->credential_type;

        // Fallback untuk admin: jika belum ada mapping eksplisit dan portal mendukung shared
        if (! $credentialType && $isAdmin && $portal->supportsShared()) {
            $credentialType = 'use_shared';
        }

        $canConfigurePersonal = $portal->supportsPersonal()
            && $credential !== null
            && (bool) $credential->is_active;

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
            'credential_type' => $credentialType,
            'personal_username' => $credential?->personal_username,
            'has_personal_credential' => filled($credential?->personal_username),
            'can_configure_personal' => $canConfigurePersonal,
            'sort_order' => $portal->sort_order,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Jalankan:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan/PortalAggregatorServiceTest.php
```
Ekspektasi: Seluruh 7 tes PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Portal/PortalAggregatorService.php tests/Feature/PortalPelaporan/PortalAggregatorServiceTest.php
git commit -m "feat(portal): add PortalAggregatorService with access filtering and card formatting"
```

---

### Task 2: Backend Controller, Route & Feature Tests

**Files:**
- Create: `app/Http/Controllers/PortalAggregatorController.php`
- Modify: `routes/web.php:124-131`
- Test: `tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php`

**Interfaces:**
- Consumes: `App\Services\Portal\PortalAggregatorService`.
- Produces:
  - Route: `GET /portal-pelaporan` (`portal-pelaporan.index`)
  - Controller: `PortalAggregatorController::index(Request $request): \Inertia\Response`
  - Inertia component: `portal-pelaporan/index` with props: `portals`, `categories`, `filters`.

- [ ] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config()->set('inertia.testing.ensure_pages_exist', false);

    $this->staff = User::factory()->create([
        'role' => 'staff',
        'email' => 'staff.portal.ctrl@rsasf.co.id',
    ]);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin.portal.ctrl@rsasf.co.id',
    ]);
});

it('redirects unauthenticated guests to login', function (): void {
    $this->get(route('portal-pelaporan.index'))
        ->assertRedirect(route('login'));
});

it('allows authenticated staff to render portal-pelaporan index with authorized portals', function (): void {
    $allowedPortal = Portal::factory()->create([
        'name' => 'SIRS Online',
        'category' => 'Kemenkes',
        'is_active' => true,
    ]);

    $disallowedPortal = Portal::factory()->create([
        'name' => 'MPDN Kemenkes',
        'category' => 'Kemenkes',
        'is_active' => true,
    ]);

    UserPortalCredential::factory()->create([
        'user_id' => $this->staff->id,
        'portal_id' => $allowedPortal->id,
        'credential_type' => 'use_shared',
        'is_active' => true,
    ]);

    $this->actingAs($this->staff)
        ->get(route('portal-pelaporan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 1)
            ->where('portals.0.name', 'SIRS Online')
            ->has('categories', 1)
            ->where('categories.0', 'Kemenkes')
            ->has('filters', fn (Assert $filters) => $filters
                ->where('category', 'all')
                ->where('search', '')
            )
        );
});

it('allows admin to see all active portals in portal-pelaporan index', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'is_active' => true]);
    Portal::factory()->create(['name' => 'SIRIKA', 'is_active' => true]);
    Portal::factory()->create(['name' => 'Inactive Portal', 'is_active' => false]);

    $this->actingAs($this->admin)
        ->get(route('portal-pelaporan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 2)
        );
});

it('passes query filters category and search to aggregator service and props', function (): void {
    $p1 = Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes', 'is_active' => true]);
    $p2 = Portal::factory()->create(['name' => 'SIRIKA BKKBN', 'category' => 'BKKBN', 'is_active' => true]);

    foreach ([$p1, $p2] as $p) {
        UserPortalCredential::factory()->create([
            'user_id' => $this->staff->id,
            'portal_id' => $p->id,
            'is_active' => true,
        ]);
    }

    $this->actingAs($this->staff)
        ->get(route('portal-pelaporan.index', ['category' => 'BKKBN', 'search' => 'SIRIKA']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal-pelaporan/index')
            ->has('portals', 1)
            ->where('portals.0.name', 'SIRIKA BKKBN')
            ->where('filters.category', 'BKKBN')
            ->where('filters.search', 'SIRIKA')
        );
});
```

- [ ] **Step 2: Run test to verify it fails**

Jalankan:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php
```
Ekspektasi: Gagal (*Route [portal-pelaporan.index] not defined*).

- [ ] **Step 3: Implement controller and route**

1. Buat file `app/Http/Controllers/PortalAggregatorController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\Portal\PortalAggregatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalAggregatorController extends Controller
{
    public function __construct(
        private PortalAggregatorService $aggregatorService,
    ) {}

    /**
     * Menampilkan antarmuka pengguna agregator portal pelaporan eksternal.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $category = $request->query('category');
        $search = $request->query('search');

        $portals = $this->aggregatorService->getUserPortals(
            $user,
            category: is_string($category) ? $category : null,
            search: is_string($search) ? $search : null,
        );

        $categories = $this->aggregatorService->getCategoriesForUser($user);

        return Inertia::render('portal-pelaporan/index', [
            'portals' => $portals,
            'categories' => $categories,
            'filters' => [
                'category' => is_string($category) && filled($category) ? $category : 'all',
                'search' => is_string($search) ? $search : '',
            ],
        ]);
    }
}
```

2. Tambahkan rute di `routes/web.php` pada grup `Route::middleware(['auth', 'verified'])` tepat di atas rute dispatch token:

```php
    // Portal Pelaporan Eksternal
    Route::get('portal-pelaporan', [PortalAggregatorController::class, 'index'])
        ->name('portal-pelaporan.index');
    Route::post('portal-pelaporan/{portal}/dispatch-token', [PortalDispatchController::class, 'dispatch'])
        ->middleware('throttle:30,1')
        ->name('portal-pelaporan.dispatch-token');
    Route::put('portal-pelaporan/{portal}/personal-credentials', [PortalPersonalCredentialController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('portal-pelaporan.personal-credentials.update');
```

- [ ] **Step 4: Run test to verify it passes**

Jalankan:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php
```
Ekspektasi: Seluruh 4 tes PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PortalAggregatorController.php routes/web.php tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php
git commit -m "feat(portal): add PortalAggregatorController and register GET /portal-pelaporan route"
```

---

### Task 3: TypeScript Types & Extension Detection Hook

**Files:**
- Modify: `resources/js/types/portal.ts`
- Create: `resources/js/components/portal/use-extension-detection.ts`

**Interfaces:**
- Consumes: DOM dataset (`document.documentElement.dataset.sifastExtensionInstalled`), CustomEvents (`SIFAST_EXTENSION_READY`, `SIFAST_PING_EXTENSION`, `SIFAST_PONG_EXTENSION`).
- Produces:
  - `PortalCardItem` interface in `resources/js/types/portal.ts`
  - `ExtensionStatus` interface in `resources/js/types/portal.ts`
  - Hook: `useExtensionDetection(): ExtensionStatus`

- [ ] **Step 1: Update TypeScript types in `resources/js/types/portal.ts`**

Edit file `resources/js/types/portal.ts` dengan menambahkan tipe `PortalCardItem` dan `ExtensionStatus`:

```typescript
export type PortalAuthType = 'shared' | 'personal' | 'both';

export type CredentialType = 'use_shared' | 'personal';

export interface FormConfigSelectorField {
    selectors: string[];
}

export interface FormConfigExtraField {
    key: string;
    selectors: string[];
}

export interface FormConfig {
    is_spa: boolean;
    wait_timeout_ms: number;
    username_field: FormConfigSelectorField;
    password_field: FormConfigSelectorField;
    extra_fields: FormConfigExtraField[];
    auto_submit: boolean;
}

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

export interface UserPortalCredential {
    id: number;
    user_id: number;
    portal_id: number;
    credential_type: CredentialType;
    personal_username: string | null;
    personal_extra_fields: Record<string, unknown> | null;
    is_active: boolean;
    notes: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface PortalMappingSummary {
    id: number;
    name: string;
    category: string;
    auth_type: PortalAuthType;
    is_active: boolean;
    user_credentials_count: number;
}

/**
 * Payload representasi kartu portal pelaporan pada halaman agregator.
 */
export interface PortalCardItem {
    id: number;
    name: string;
    slug: string;
    category: string;
    url: string;
    url_pattern: string | null;
    icon_path: string | null;
    icon_url: string | null;
    description: string | null;
    auth_type: PortalAuthType;
    credential_type: CredentialType | null;
    personal_username: string | null;
    has_personal_credential: boolean;
    can_configure_personal: boolean;
    sort_order: number;
}

/**
 * Status keberadaan ekstensi browser SIMRS Sifast.
 */
export interface ExtensionStatus {
    isInstalled: boolean;
    version: string | null;
    isChecking: boolean;
}
```

- [ ] **Step 2: Create React hook `useExtensionDetection`**

Buat file `resources/js/components/portal/use-extension-detection.ts`:

```typescript
import { useEffect, useState } from 'react';
import type { ExtensionStatus } from '@/types/portal';

/**
 * Hook untuk mendeteksi keberadaan browser extension SIMRS Sifast.
 *
 * Mendeteksi melalui 3 mekanisme:
 * 1. Pengecekan atribut dataset pada document.documentElement (dataset.sifastExtensionInstalled).
 * 2. Mendengarkan CustomEvent 'SIFAST_EXTENSION_READY' saat ekstensi diinisialisasi.
 * 3. Mengirimkan CustomEvent 'SIFAST_PING_EXTENSION' dan mendengarkan respon 'SIFAST_PONG_EXTENSION'.
 */
export function useExtensionDetection(): ExtensionStatus {
    const [status, setStatus] = useState<ExtensionStatus>({
        isInstalled: false,
        version: null,
        isChecking: true,
    });

    useEffect(() => {
        let isMounted = true;

        const checkDataset = (): boolean => {
            if (typeof document === 'undefined') return false;

            const dataset = document.documentElement.dataset;
            if (dataset.sifastExtensionInstalled === 'true') {
                if (isMounted) {
                    setStatus({
                        isInstalled: true,
                        version: dataset.sifastExtensionVersion || '1.0.0',
                        isChecking: false,
                    });
                }
                return true;
            }
            return false;
        };

        // 1. Cek langsung dataset DOM
        if (checkDataset()) {
            return;
        }

        // 2. Handler event 'SIFAST_EXTENSION_READY' & 'SIFAST_PONG_EXTENSION'
        const handleExtensionSignal = (event: Event) => {
            if (!isMounted) return;

            const customEv = event as CustomEvent<{ version?: string; installed?: boolean }>;
            const version = customEv.detail?.version || document.documentElement.dataset.sifastExtensionVersion || '1.0.0';

            setStatus({
                isInstalled: true,
                version,
                isChecking: false,
            });
        };

        window.addEventListener('SIFAST_EXTENSION_READY', handleExtensionSignal);
        window.addEventListener('SIFAST_PONG_EXTENSION', handleExtensionSignal);

        // 3. Ping ekstensi secara aktif
        try {
            window.dispatchEvent(new CustomEvent('SIFAST_PING_EXTENSION'));
        } catch {
            // Abaikan jika dispatch gagal di lingkungan non-browser
        }

        // 4. Fallback timeout jika ekstensi tidak terpasang (berhenti checking setelah 500ms)
        const timer = setTimeout(() => {
            if (isMounted) {
                // Cek sekali lagi sebelum menyerah
                if (!checkDataset()) {
                    setStatus((prev) => ({
                        ...prev,
                        isChecking: false,
                    }));
                }
            }
        }, 500);

        return () => {
            isMounted = false;
            clearTimeout(timer);
            window.removeEventListener('SIFAST_EXTENSION_READY', handleExtensionSignal);
            window.removeEventListener('SIFAST_PONG_EXTENSION', handleExtensionSignal);
        };
    }, []);

    return status;
}
```

- [ ] **Step 3: Run TypeScript compiler check**

Jalankan:
```bash
npx tsc --noEmit
```
Ekspektasi: Tidak ada error TypeScript.

- [ ] **Step 4: Commit**

```bash
git add resources/js/types/portal.ts resources/js/components/portal/use-extension-detection.ts
git commit -m "feat(portal): add PortalCardItem types and useExtensionDetection hook"
```

---

### Task 4: Extension Status UI Components (Badge, Guide Banner & Install Dialog)

**Files:**
- Create: `resources/js/components/portal/extension-status-badge.tsx`
- Create: `resources/js/components/portal/extension-guide-banner.tsx`
- Create: `resources/js/components/portal/extension-install-dialog.tsx`

**Interfaces:**
- Consumes: `ExtensionStatus` dari `useExtensionDetection`.
- Produces:
  - `<ExtensionStatusBadge status={status} onOpenGuide={() => ...} />`
  - `<ExtensionGuideBanner isInstalled={status.isInstalled} isChecking={status.isChecking} onOpenGuide={() => ...} />`
  - `<ExtensionInstallDialog open={isOpen} onOpenChange={setIsOpen} />`

- [ ] **Step 1: Create `extension-status-badge.tsx`**

Buat file `resources/js/components/portal/extension-status-badge.tsx`:

```tsx
import { CheckCircle2, Download, HelpCircle, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { ExtensionStatus } from '@/types/portal';

interface ExtensionStatusBadgeProps {
    status: ExtensionStatus;
    onOpenGuide?: () => void;
    className?: string;
}

export function ExtensionStatusBadge({
    status,
    onOpenGuide,
    className,
}: ExtensionStatusBadgeProps) {
    if (status.isChecking) {
        return (
            <Badge
                variant="outline"
                className={cn(
                    'flex items-center gap-1.5 border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400',
                    className,
                )}
            >
                <Loader2 className="h-3 w-3 animate-spin text-slate-400" />
                <span className="text-xs font-medium">Memeriksa Ekstensi...</span>
            </Badge>
        );
    }

    if (status.isInstalled) {
        return (
            <Badge
                variant="outline"
                className={cn(
                    'flex items-center gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400',
                    className,
                )}
                title="Ekstensi Sifast Autofill aktif dan siap digunakan."
            >
                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                <span className="text-xs font-semibold">
                    Ekstensi Aktif (v{status.version || '1.0.0'})
                </span>
            </Badge>
        );
    }

    return (
        <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={onOpenGuide}
            className={cn(
                'h-7 gap-1.5 rounded-full border-amber-300 bg-amber-50 px-2.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50',
                className,
            )}
        >
            <Download className="h-3 w-3 text-amber-600 dark:text-amber-400" />
            <span>Ekstensi Belum Terpasang</span>
            <HelpCircle className="h-3 w-3 opacity-60" />
        </Button>
    );
}
```

- [ ] **Step 2: Create `extension-install-dialog.tsx`**

Buat file `resources/js/components/portal/extension-install-dialog.tsx`:

```tsx
import { AlertCircle, Chrome, Download, ExternalLink, FolderArchive, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface ExtensionInstallDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function ExtensionInstallDialog({
    open,
    onOpenChange,
}: ExtensionInstallDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100">
                        <Chrome className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        Panduan Pemasangan Ekstensi Browser SIFAST
                    </DialogTitle>
                    <DialogDescription className="text-sm text-slate-600 dark:text-slate-400">
                        Ekstensi browser resmi RS Aisyiyah Siti Fatimah diperlukan agar SIMRS dapat mengisi username & password ke website pelaporan eksternal secara otomatis dan aman.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    {/* Security Notice */}
                    <div className="flex items-start gap-3 rounded-lg border border-teal-200 bg-teal-50/60 p-3 text-xs text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-300">
                        <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" />
                        <div>
                            <p className="font-semibold">Keamanan Zero-Persistence:</p>
                            <p className="mt-0.5 opacity-90">
                                Ekstensi ini tidak menyimpan password di browser Anda. Kredensial hanya dikirim saat Anda mengklik tombol buka portal dan langsung dihapus dari memori RAM setelah form terisi.
                            </p>
                        </div>
                    </div>

                    {/* Step-by-step instructions */}
                    <div className="space-y-3 text-sm">
                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                1
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Unduh dan Ekstrak Berkas Ekstensi
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Dapatkan folder ekstensi dari repositori proyek (<code className="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">rs-extension/</code>) atau hubungi Tim IT SIMRS untuk mendapatkan paket berkas ZIP resmi.
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                2
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Buka Halaman Ekstensi Browser
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Buka browser berbasis Chromium (Google Chrome, Microsoft Edge, atau Brave), lalu buka alamat:
                                    <code className="ml-1 select-all rounded bg-slate-100 px-1.5 py-0.5 font-mono text-emerald-700 dark:bg-slate-800 dark:text-emerald-400">
                                        chrome://extensions
                                    </code>
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                3
                            </span>
                            <div>
                                <p className="font-medium text-slate-900 dark:text-slate-100">
                                    Aktifkan Mode Pengembang & Muat Ekstensi
                                </p>
                                <p className="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    Aktifkan tombol <strong>Developer mode</strong> (Mode pengembang) di pojok kanan atas, lalu klik <strong>Load unpacked</strong> (Muat yang belum dibongkar) dan pilih folder ekstensi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                        <div className="flex items-center gap-2 font-medium text-slate-900 dark:text-slate-200">
                            <AlertCircle className="h-4 w-4 text-slate-500" />
                            <span>Tetap Bisa Mengakses Portal Tanpa Ekstensi:</span>
                        </div>
                        <p className="mt-1">
                            Bila ekstensi belum dipasang, tombol <strong>"Buka Portal"</strong> akan tetap membuka website pelaporan di tab baru, namun Anda perlu mengetikkan username & password secara manual.
                        </p>
                    </div>
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Tutup Panduan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
```

- [ ] **Step 3: Create `extension-guide-banner.tsx`**

Buat file `resources/js/components/portal/extension-guide-banner.tsx`:

```tsx
import { AlertCircle, Chrome, HelpCircle, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ExtensionGuideBannerProps {
    isInstalled: boolean;
    isChecking: boolean;
    onOpenGuide: () => void;
    className?: string;
}

export function ExtensionGuideBanner({
    isInstalled,
    isChecking,
    onOpenGuide,
    className,
}: ExtensionGuideBannerProps) {
    const [dismissed, setDismissed] = useState(false);

    // Jangan tampilkan jika sudah terpasang, sedang checking, atau ditutup sementara
    if (isInstalled || isChecking || dismissed) {
        return null;
    }

    return (
        <div
            className={cn(
                'relative flex flex-col gap-3 rounded-xl border border-amber-200/80 bg-gradient-to-r from-amber-50/90 via-amber-50/50 to-orange-50/60 p-4 text-amber-900 shadow-xs sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:from-amber-950/30 dark:via-amber-950/20 dark:to-orange-950/20 dark:text-amber-200',
                className,
            )}
        >
            <div className="flex items-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                    <Chrome className="h-5 w-5" />
                </div>
                <div>
                    <div className="flex items-center gap-2">
                        <h4 className="text-sm font-semibold">
                            Ekstensi Browser SIFAST Belum Terpasang
                        </h4>
                    </div>
                    <p className="mt-0.5 text-xs text-amber-800/90 dark:text-amber-300/80">
                        Untuk menikmati pengisian username & password secara otomatis ke portal eksternal (Kemenkes & BKKBN), silakan pasang ekstensi browser resmi SIMRS.
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-2 self-end sm:self-center">
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={onOpenGuide}
                    className="h-8 gap-1.5 border-amber-300 bg-white/80 text-xs font-semibold text-amber-900 hover:bg-amber-100 hover:text-amber-950 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200 dark:hover:bg-amber-900"
                >
                    <HelpCircle className="h-3.5 w-3.5" />
                    <span>Panduan Pemasangan</span>
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    onClick={() => setDismissed(true)}
                    className="h-8 w-8 text-amber-600 hover:bg-amber-100/60 hover:text-amber-900 dark:text-amber-400 dark:hover:bg-amber-900/40"
                    title="Tutup pemberitahuan"
                >
                    <X className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}
```

- [ ] **Step 4: Run TypeScript check**

Jalankan:
```bash
npx tsc --noEmit
```
Ekspektasi: Tidak ada error.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/portal/extension-status-badge.tsx resources/js/components/portal/extension-install-dialog.tsx resources/js/components/portal/extension-guide-banner.tsx
git commit -m "feat(portal): add extension status badge, guide banner, and install dialog"
```

---

### Task 5: Portal Card & Self-Service Personal Credential Dialog

**Files:**
- Create: `resources/js/components/portal/personal-credential-dialog.tsx`
- Create: `resources/js/components/portal/portal-card.tsx`

**Interfaces:**
- Consumes:
  - `PortalCardItem`
  - Endpoint `POST /portal-pelaporan/{portal}/dispatch-token`
  - Endpoint `PUT /portal-pelaporan/{portal}/personal-credentials`
- Produces:
  - `<PersonalCredentialDialog portal={portal} open={isOpen} onOpenChange={setIsOpen} onSuccess={handleSuccess} />`
  - `<PortalCard portal={portal} isExtensionInstalled={isInstalled} onOpenPersonalModal={handleOpenModal} />`

- [ ] **Step 1: Create `personal-credential-dialog.tsx`**

Buat file `resources/js/components/portal/personal-credential-dialog.tsx`:

```tsx
import { Eye, EyeOff, KeyRound, Loader2, Save, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PortalCardItem } from '@/types/portal';

interface PersonalCredentialDialogProps {
    portal: PortalCardItem | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSuccess?: () => void;
}

export function PersonalCredentialDialog({
    portal,
    open,
    onOpenChange,
    onSuccess,
}: PersonalCredentialDialogProps) {
    const [username, setUsername] = useState(portal?.personal_username || '');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    // Sinkronisasi form saat portal berubah
    const handleOpenChange = (newOpen: boolean) => {
        if (newOpen && portal) {
            setUsername(portal.personal_username || '');
            setPassword('');
            setErrorMessage(null);
            setSuccessMessage(null);
        }
        onOpenChange(newOpen);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!portal) return;

        if (!username.trim()) {
            setErrorMessage('Username akun pribadi wajib diisi.');
            return;
        }

        setIsLoading(true);
        setErrorMessage(null);
        setSuccessMessage(null);

        try {
            // Ambil CSRF token dari meta tag bawaan Laravel
            const tokenMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
            const csrfToken = tokenMeta ? tokenMeta.content : '';

            const response = await fetch(`/portal-pelaporan/${portal.id}/personal-credentials`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    username: username.trim(),
                    ...(password ? { password } : {}),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                const message = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Gagal menyimpan kredensial.');
                throw new Error(message);
            }

            setSuccessMessage('Kredensial akun pribadi berhasil disimpan.');
            setTimeout(() => {
                onOpenChange(false);
                if (onSuccess) {
                    onSuccess();
                }
            }, 800);
        } catch (err: unknown) {
            const msg = err instanceof Error ? err.message : 'Terjadi kesalahan saat menyimpan data.';
            setErrorMessage(msg);
        } finally {
            setIsLoading(false);
        }
    };

    if (!portal) return null;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100">
                            <KeyRound className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            Atur Akun Pribadi: {portal.name}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-slate-600 dark:text-slate-400">
                            Masukkan username dan password akun pribadi Anda pada portal ini. Data Anda disimpan secara terenkripsi aman di SIMRS.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {errorMessage && (
                            <div className="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-400">
                                {errorMessage}
                            </div>
                        )}

                        {successMessage && (
                            <div className="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400">
                                <ShieldCheck className="h-4 w-4 shrink-0" />
                                <span>{successMessage}</span>
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <Label htmlFor="personal_username" className="text-xs font-semibold">
                                Username / NIK / Email Pribadi <span className="text-rose-500">*</span>
                            </Label>
                            <Input
                                id="personal_username"
                                type="text"
                                placeholder="Contoh: user.kemenkes@gmail.com"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                required
                                disabled={isLoading}
                                className="h-9"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="personal_password" className="text-xs font-semibold">
                                    Password Akun Pribadi
                                </Label>
                                {portal.has_personal_credential && (
                                    <span className="text-[11px] text-slate-500">
                                        (Kosongkan jika tidak ingin mengubah)
                                    </span>
                                )}
                            </div>
                            <div className="relative">
                                <Input
                                    id="personal_password"
                                    type={showPassword ? 'text' : 'password'}
                                    placeholder={portal.has_personal_credential ? '••••••••••••' : 'Masukkan password portal'}
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    disabled={isLoading}
                                    className="h-9 pr-9 font-mono"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    tabIndex={-1}
                                >
                                    {showPassword ? (
                                        <EyeOff className="h-4 w-4" />
                                    ) : (
                                        <Eye className="h-4 w-4" />
                                    )}
                                </button>
                            </div>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                Password ini akan otomatis diisi oleh ekstensi browser saat Anda membuka portal.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={isLoading}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={isLoading}
                            className="gap-1.5 bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                        >
                            {isLoading ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span>Menyimpan...</span>
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" />
                                    <span>Simpan Akun</span>
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
```

- [ ] **Step 2: Create `portal-card.tsx`**

Buat file `resources/js/components/portal/portal-card.tsx`:

```tsx
import {
    ExternalLink,
    Globe,
    KeyRound,
    Loader2,
    Lock,
    ShieldCheck,
    User,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { PortalCardItem } from '@/types/portal';

interface PortalCardProps {
    portal: PortalCardItem;
    isExtensionInstalled: boolean;
    onOpenPersonalModal: (portal: PortalCardItem) => void;
}

export function PortalCard({
    portal,
    isExtensionInstalled,
    onOpenPersonalModal,
}: PortalCardProps) {
    const [isLaunching, setIsLaunching] = useState(false);
    const [imageError, setImageError] = useState(false);

    const handleLaunch = async () => {
        // Fallback: Jika ekstensi belum terpasang, langsung buka tab baru ke URL target
        if (!isExtensionInstalled) {
            window.open(portal.url, '_blank', 'noopener,noreferrer');
            return;
        }

        setIsLaunching(true);
        try {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
            const csrfToken = tokenMeta ? tokenMeta.content : '';

            const response = await fetch(`/portal-pelaporan/${portal.id}/dispatch-token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Gagal memproses kredensial portal.');
            }

            const payload = await response.json();

            // Pancarkan event ke ekstensi browser SIFAST
            window.dispatchEvent(
                new CustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: payload,
                }),
            );
        } catch (err: unknown) {
            // Bila terjadi galat dispatch, fallback tetap membuka portal di tab baru
            console.warn('[SIFAST Portal] Dispatch gagal, fallback ke direct tab:', err);
            window.open(portal.url, '_blank', 'noopener,noreferrer');
        } finally {
            setTimeout(() => {
                setIsLaunching(false);
            }, 600);
        }
    };

    // Inisial untuk fallback logo jika icon_url tidak ada
    const initials = portal.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();

    const isShared = portal.credential_type === 'use_shared';
    const isPersonal = portal.credential_type === 'personal';

    return (
        <Card className="group relative flex flex-col justify-between overflow-hidden border-slate-200/80 bg-white transition-all duration-200 hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-800">
            <CardHeader className="p-4 pb-3">
                <div className="flex items-start justify-between gap-3">
                    {/* Logo Portal atau Fallback Inisial */}
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-2xs dark:border-slate-800 dark:bg-slate-800/80">
                        {portal.icon_url && !imageError ? (
                            <img
                                src={portal.icon_url}
                                alt={`Logo ${portal.name}`}
                                className="h-full w-full object-contain p-1.5"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-emerald-500/10 to-teal-500/20 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                {initials || <Globe className="h-5 w-5 text-emerald-600" />}
                            </div>
                        )}
                    </div>

                    {/* Badge Kategori */}
                    <Badge
                        variant="secondary"
                        className="max-w-[130px] truncate text-[11px] font-medium text-slate-600 dark:text-slate-300"
                        title={portal.category}
                    >
                        {portal.category}
                    </Badge>
                </div>

                <div className="mt-3">
                    <h3 className="line-clamp-1 text-base font-bold text-slate-900 group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-400">
                        {portal.name}
                    </h3>
                    <p className="mt-1 line-clamp-2 min-h-[32px] text-xs text-slate-500 dark:text-slate-400">
                        {portal.description || 'Tidak ada deskripsi portal.'}
                    </p>
                </div>
            </CardHeader>

            <CardContent className="p-4 pt-0">
                {/* Indikator Akun / Kredensial */}
                <div className="mt-1 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5 dark:border-slate-800/80">
                    <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        Tipe Akun:
                    </span>

                    {isShared && (
                        <Badge
                            variant="outline"
                            className="gap-1 border-teal-200 bg-teal-50/70 text-[11px] font-medium text-teal-800 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-300"
                        >
                            <Users className="h-3 w-3 text-teal-600 dark:text-teal-400" />
                            <span>Akun Bersama RS</span>
                        </Badge>
                    )}

                    {isPersonal && (
                        <Badge
                            variant="outline"
                            className={cn(
                                'gap-1 text-[11px] font-medium',
                                portal.has_personal_credential
                                    ? 'border-indigo-200 bg-indigo-50/70 text-indigo-800 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-300'
                                    : 'border-amber-200 bg-amber-50/70 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300',
                            )}
                            title={
                                portal.personal_username
                                    ? `Akun: ${portal.personal_username}`
                                    : 'Kredensial personal belum diatur'
                            }
                        >
                            <User className="h-3 w-3 text-indigo-600 dark:text-indigo-400" />
                            <span>
                                {portal.has_personal_credential
                                    ? portal.personal_username || 'Akun Pribadi'
                                    : 'Pribadi (Belum Diatur)'}
                            </span>
                        </Badge>
                    )}

                    {!isShared && !isPersonal && (
                        <Badge
                            variant="outline"
                            className="border-slate-200 bg-slate-50 text-[11px] text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400"
                        >
                            <span>Belum Dikonfigurasi</span>
                        </Badge>
                    )}
                </div>
            </CardContent>

            <CardFooter className="flex items-center gap-2 border-t border-slate-100 bg-slate-50/50 p-3 dark:border-slate-800/80 dark:bg-slate-900/40">
                {/* Tombol Atur Akun Pribadi (Hanya jika diizinkan) */}
                {portal.can_configure_personal && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onOpenPersonalModal(portal)}
                        className="h-8 w-8 p-0 text-slate-600 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400"
                        title="Atur Username & Password Akun Pribadi"
                    >
                        <KeyRound className="h-4 w-4" />
                    </Button>
                )}

                {/* Tombol Buka Portal Utama */}
                <Button
                    type="button"
                    size="sm"
                    onClick={handleLaunch}
                    disabled={isLaunching}
                    className="h-8 flex-1 gap-1.5 bg-emerald-600 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                >
                    {isLaunching ? (
                        <>
                            <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            <span>Membuka...</span>
                        </>
                    ) : (
                        <>
                            <span>Buka Portal</span>
                            <ExternalLink className="h-3.5 w-3.5" />
                        </>
                    )}
                </Button>
            </CardFooter>
        </Card>
    );
}
```

- [ ] **Step 3: Run TypeScript compiler check**

Jalankan:
```bash
npx tsc --noEmit
```
Ekspektasi: Bebas dari error tipe.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/portal/personal-credential-dialog.tsx resources/js/components/portal/portal-card.tsx
git commit -m "feat(portal): add PortalCard component and PersonalCredentialDialog modal"
```

---

### Task 6: Portal Pelaporan Main Page (`/portal-pelaporan`) with Live Filter & Search

**Files:**
- Create: `resources/js/pages/portal-pelaporan/index.tsx`

**Interfaces:**
- Consumes:
  - Inertia Props: `{ portals: PortalCardItem[]; categories: string[]; filters: { category: string; search: string } }`
  - Components: `AppLayout`, `ExtensionStatusBadge`, `ExtensionGuideBanner`, `ExtensionInstallDialog`, `PortalCard`, `PersonalCredentialDialog`.
- Produces:
  - Halaman `portal-pelaporan/index` yang siap di-render via Inertia.

- [ ] **Step 1: Create `resources/js/pages/portal-pelaporan/index.tsx`**

Buat file `resources/js/pages/portal-pelaporan/index.tsx`:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Boxes,
    Building2,
    Globe,
    Layers,
    Search,
    ShieldCheck,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { ExtensionGuideBanner } from '@/components/portal/extension-guide-banner';
import { ExtensionInstallDialog } from '@/components/portal/extension-install-dialog';
import { ExtensionStatusBadge } from '@/components/portal/extension-status-badge';
import { PersonalCredentialDialog } from '@/components/portal/personal-credential-dialog';
import { PortalCard } from '@/components/portal/portal-card';
import { useExtensionDetection } from '@/components/portal/use-extension-detection';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { PortalCardItem } from '@/types/portal';

interface Props {
    portals: PortalCardItem[];
    categories: string[];
    filters: {
        category: string;
        search: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Portal Pelaporan Eksternal', href: '/portal-pelaporan' },
];

export default function PortalPelaporanIndex({
    portals,
    categories,
    filters,
}: Props) {
    const { permissions } = usePage<SharedData>().props;
    const extensionStatus = useExtensionDetection();

    const [isInstallGuideOpen, setIsInstallGuideOpen] = useState(false);
    const [selectedPortalForCredentials, setSelectedPortalForCredentials] = useState<PortalCardItem | null>(null);
    const [isCredentialModalOpen, setIsCredentialModalOpen] = useState(false);

    // Live Client-side Filter
    const [selectedCategory, setSelectedCategory] = useState<string>(filters.category || 'all');
    const [searchTerm, setSearchTerm] = useState<string>(filters.search || '');

    // Hitung portal yang memenuhi filter pencarian dan kategori
    const filteredPortals = useMemo(() => {
        return portals.filter((portal) => {
            const matchesCategory =
                selectedCategory === 'all' || portal.category.toLowerCase() === selectedCategory.toLowerCase();

            const query = searchTerm.toLowerCase().trim();
            const matchesSearch =
                !query ||
                portal.name.toLowerCase().includes(query) ||
                (portal.description && portal.description.toLowerCase().includes(query)) ||
                portal.category.toLowerCase().includes(query);

            return matchesCategory && matchesSearch;
        });
    }, [portals, selectedCategory, searchTerm]);

    // Hitung jumlah portal per kategori untuk lencana pill
    const categoryCounts = useMemo(() => {
        const counts: Record<string, number> = { all: portals.length };
        for (const portal of portals) {
            counts[portal.category] = (counts[portal.category] || 0) + 1;
        }
        return counts;
    }, [portals]);

    const handleOpenPersonalCredentialModal = (portal: PortalCardItem) => {
        setSelectedPortalForCredentials(portal);
        setIsCredentialModalOpen(true);
    };

    const handleCredentialSaved = () => {
        // Muat ulang data Inertia agar status kredensial kartu terbarui
        router.reload({ only: ['portals'] });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Portal Pelaporan Eksternal" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header Utama */}
                <div className="flex flex-col justify-between gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-center dark:border-slate-800">
                    <div>
                        <div className="flex items-center gap-2.5">
                            <Heading
                                title="Portal Pelaporan Eksternal"
                                description="Akses terpadu ke portal pelaporan resmi pemerintah (Kemenkes & BKKBN) dengan pengisian login otomatis yang aman."
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {/* Extension Status Badge */}
                        <ExtensionStatusBadge
                            status={extensionStatus}
                            onOpenGuide={() => setIsInstallGuideOpen(true)}
                        />

                        {/* Admin Action Links */}
                        {permissions?.can_manage_portals && (
                            <div className="flex items-center gap-1.5 pl-2 border-l border-slate-200 dark:border-slate-800">
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="h-8 gap-1.5 text-xs"
                                >
                                    <Link href="/admin/portals">
                                        <Globe className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                        <span>Master Portal</span>
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="h-8 gap-1.5 text-xs"
                                >
                                    <Link href="/admin/portals/mapping">
                                        <ShieldCheck className="h-3.5 w-3.5 text-teal-600 dark:text-teal-400" />
                                        <span>Mapping Akses</span>
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Banner Panduan jika Ekstensi belum terpasang */}
                <ExtensionGuideBanner
                    isInstalled={extensionStatus.isInstalled}
                    isChecking={extensionStatus.isChecking}
                    onOpenGuide={() => setIsInstallGuideOpen(true)}
                />

                {/* Filter & Toolbar Area */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    {/* Category Filter Pills */}
                    <div className="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            onClick={() => setSelectedCategory('all')}
                            className={cn(
                                'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                selectedCategory === 'all'
                                    ? 'bg-emerald-600 text-white shadow-2xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                            )}
                        >
                            <span>Semua Portal</span>
                            <span
                                className={cn(
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                                    selectedCategory === 'all'
                                        ? 'bg-emerald-700/80 text-white'
                                        : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                )}
                            >
                                {categoryCounts.all || 0}
                            </span>
                        </button>

                        {categories.map((category) => (
                            <button
                                key={category}
                                type="button"
                                onClick={() => setSelectedCategory(category)}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                    selectedCategory.toLowerCase() === category.toLowerCase()
                                        ? 'bg-emerald-600 text-white shadow-2xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                                )}
                            >
                                <span>{category}</span>
                                <span
                                    className={cn(
                                        'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                                        selectedCategory.toLowerCase() === category.toLowerCase()
                                            ? 'bg-emerald-700/80 text-white'
                                            : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                    )}
                                >
                                    {categoryCounts[category] || 0}
                                </span>
                            </button>
                        ))}
                    </div>

                    {/* Live Search Input */}
                    <div className="relative w-full sm:w-72">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            type="text"
                            placeholder="Cari nama atau deskripsi portal..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="h-9 pl-9 pr-8 text-xs"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                </div>

                {/* Grid Kartu Portal */}
                {filteredPortals.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filteredPortals.map((portal) => (
                            <PortalCard
                                key={portal.id}
                                portal={portal}
                                isExtensionInstalled={extensionStatus.isInstalled}
                                onOpenPersonalModal={handleOpenPersonalCredentialModal}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex min-h-[320px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                        {portals.length === 0 ? (
                            <EmptyState
                                icon={Layers}
                                title="Belum Ada Akses Portal"
                                description="Akun Anda belum memiliki izin akses ke portal pelaporan eksternal. Silakan hubungi Tim IT atau Administrator untuk mendapatkan penugasan portal."
                            />
                        ) : (
                            <EmptyState
                                icon={Search}
                                title="Tidak Ada Portal Ditemukan"
                                description="Tidak ada portal yang cocok dengan kata kunci atau filter kategori yang dipilih."
                                action={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setSelectedCategory('all');
                                            setSearchTerm('');
                                        }}
                                    >
                                        Reset Filter
                                    </Button>
                                }
                            />
                        )}
                    </div>
                )}
            </div>

            {/* Modal Dialog Panduan Instalasi Ekstensi */}
            <ExtensionInstallDialog
                open={isInstallGuideOpen}
                onOpenChange={setIsInstallGuideOpen}
            />

            {/* Modal Dialog Pengaturan Kredensial Pribadi */}
            <PersonalCredentialDialog
                portal={selectedPortalForCredentials}
                open={isCredentialModalOpen}
                onOpenChange={setIsCredentialModalOpen}
                onSuccess={handleCredentialSaved}
            />
        </AppLayout>
    );
}
```

- [ ] **Step 2: Run TypeScript check**

Jalankan:
```bash
npx tsc --noEmit
```
Ekspektasi: Tidak ada error kompilasi TypeScript.

- [ ] **Step 3: Run Pest Feature tests for controller**

Jalankan:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan/PortalAggregatorControllerTest.php
```
Ekspektasi: Semua 4 tes PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/portal-pelaporan/index.tsx
git commit -m "feat(portal): add portal-pelaporan index page with live category and search filter"
```

---

### Task 7: SIMRS Navigation Sidebar Integration

**Files:**
- Modify: `resources/js/components/app-sidebar.tsx:40-70`

**Interfaces:**
- Consumes: Route `/portal-pelaporan` (`portal-pelaporan.index`).
- Produces: Item menu navigasi "Portal Pelaporan" di sidebar SIMRS untuk seluruh staf rumah sakit yang berhak.

- [ ] **Step 1: Check existing navigation items in `app-sidebar.tsx`**

Periksa posisi `mainNavItems` di `resources/js/components/app-sidebar.tsx`:
```tsx
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tiket',
        href: '/tickets',
        icon: Ticket,
    },
    ...
```

- [ ] **Step 2: Add "Portal Pelaporan" into `mainNavItems`**

Tambahkan item menu `Portal Pelaporan` dengan icon `Globe` atau `Layers` pada `mainNavItems` di `resources/js/components/app-sidebar.tsx`:

```tsx
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Portal Pelaporan',
        href: '/portal-pelaporan',
        icon: Globe,
    },
    {
        title: 'Tiket',
        href: '/tickets',
        icon: Ticket,
    },
    // ... item lainnya tetap dipertahankan ...
```

Pastikan icon `Globe` telah di-import dari `lucide-react`.

- [ ] **Step 3: Run TypeScript compiler check**

Jalankan:
```bash
npx tsc --noEmit
```
Ekspektasi: Bebas dari error tipe.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/app-sidebar.tsx
git commit -m "feat(navigation): integrate Portal Pelaporan link into SIMRS app sidebar"
```

---

### Task 8: Comprehensive Verification & Regression Check

**Files:**
- Test: Seluruh unit/feature test di `tests/Feature/PortalPelaporan/`
- Build: Verifikasi TypeScript (`npx tsc --noEmit`) dan Vite build (`npm run build`)

- [ ] **Step 1: Run full PortalPelaporan Pest feature tests**

Jalankan:
```bash
vendor/bin/pest tests/Feature/PortalPelaporan
```
Ekspektasi: Seluruh tes (78 tes awal + 11 tes baru Plan 3 = 89 tes) PASS dengan 0 failure.

- [ ] **Step 2: Run frontend build verification**

Jalankan:
```bash
npm run build
```
Ekspektasi: Build Vite sukses menghasilkan production bundles tanpa chunk error.

- [ ] **Step 3: Commit and update documentation log**

```bash
git add docs/superpowers/plans/2026-09-15-portal-eksternal-plan-3-portal-aggregator.md
git commit -m "docs(plan): finalize Plan 3 implementation plan for portal aggregator UI"
```

---

## Plan Self-Review Checklist
- [x] **Spec coverage:** Seluruh kebutuhan Plan 3 dalam dokumen spesifikasi (`PortalAggregatorService`, `PortalAggregatorController`, `portal-pelaporan/index.tsx`, deteksi ekstensi dataset/ping-pong, fallback direct URL, self-service personal modal, filter live, dan sidebar) terakomodasi dalam task mandiri.
- [x] **Placeholder scan:** Tidak ada TODO, TBD, atau placeholder code. Setiap langkah memuat kode fungsional penuh.
- [x] **Type consistency:** Konsistensi tipe `PortalCardItem`, `ExtensionStatus`, parameter dan return type di seluruh controller, service, dan komponen React.
- [x] **TDD Integrity:** Setiap tugas backend diawali penulisan tes gagal sebelum implementasi dan diakhiri dengan verifikasi tes hijau serta commit atomik.
