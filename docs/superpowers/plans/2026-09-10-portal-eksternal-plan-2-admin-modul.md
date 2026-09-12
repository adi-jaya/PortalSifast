# Portal Pelaporan Eksternal SIMRS - Plan 2: Modul Admin (Master Portal & Mapping Akses)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Catatan Arsitektur:** Dokumen ini merupakan **Bagian 2 dari 4** rencana implementasi modular yang merujuk pada spesifikasi induk: [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md).
>
> **Daftar Rencana Modular:**
> 1. **Plan 1: Fondasi Backend & Database** *(Selesai - 29 Tests PASS)*
> 2. **Plan 2: Modul Admin (Master Portal CRUD & Form Builder, Mapping Akses Petugas)** *(Dokumen ini)*
> 3. **Plan 3: Custom Browser Extension Manifest V3 (rs-extension: Background Worker, Content Bridge, Autofill Injector, Heuristic Scanner, Popup Inspector)**
> 4. **Plan 4: Halaman Pengguna (Portal Pelaporan Agregator, Deteksi Ekstensi, Self-Service Kredensial, ZIP Packaging & E2E Testing)**

**Goal:** Membangun modul manajemen administratif lengkap untuk Portal Pelaporan Eksternal: CRUD master website target berserta akun bersama RS & editor interaktif form selector login (`/admin/portals`), serta matriks manajemen hak akses petugas per-portal dan per-user (`/admin/portals/mapping`) dengan UI Inertia React modern (TypeScript, Tailwind CSS v4, Radix UI).

**Architecture:** Modul admin dilindungi oleh `PortalPolicy::manage` (hanya role `admin` atau `isSuperAdmin()`). Menggunakan arsitektur Service Class Layer (`AdminPortalService` dan `AdminPortalMappingService`) di bawah namespace `App\Services\Portal` yang diinjeksi ke controller resource terpisah (`AdminPortalController` dan `AdminPortalMappingController`) melalui **Constructor Injection**. Dilengkapi form request dengan sanitasi slug dan proteksi mutasi password kosong, transaksi DB pada operasi sinkronisasi massal, serta komponen visual builder selector login dengan live sync ke raw JSON editor.

**Tech Stack:** Laravel 12, PHP 8.2+, Pest 4 (`pestphp/pest`), Inertia.js React 19, TypeScript, Tailwind CSS v4, Radix UI (`@radix-ui/react-*`), Lucide Icons.

**Spec:** [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md)

---

## Global Constraints

- **Zero-Plaintext Leakage:** Password bersama (`shared_password`) TIDAK BOLEH dikirimkan dalam bentuk plaintext pada props halaman edit Inertia. Frontend hanya menerima flag boolean `has_shared_password`. Pada update, jika bidang password dikosongkan, password lama di database tidak boleh terhapus.
- **Otorisasi Ketat & Defense-in-Depth:** Hanya pengguna dengan `role === 'admin'` atau yang memenuhi `isSuperAdmin()` yang diizinkan mengakses route `/admin/portals*` dan `/admin/portals/mapping*`. Diproteksi langsung pada perimeter rute `routes/web.php` dengan `middleware('can:manage,App\Models\Portal')` serta otorisasi controller & FormRequest.
- **Service Class Layer & Constructor Injection:** Seluruh query Eloquent, pemfilteran, sanitasi data, transaksi DB, dan mutasi model wajib dienkapsulasi di dalam `AdminPortalService` dan `AdminPortalMappingService`. Controller hanya memanggil method service melalui constructor injection.
- **Explicit Note-Clearing Auto-Save:** Method `saveSingleAssignment` dan endpoint `saveRow` mendukung flag eksplisit `updateNotes` (`$request->has('notes')`). Toggle akses switch menjaga catatan lama tanpa menimpanya, sedangkan pengosongan input catatan secara sadar akan menghapus (*clear*) catatan di database menjadi `null`.
- **Full Staff Reachability & Dynamic Pagination:** Matriks portal menyediakan navigasi `DataTablePagination`, dan `MappingUserView` menyediakan dataset `all_users` dengan input pencarian cepat berbasis klien (Nama/NIK/Unit) agar seluruh staf rumah sakit (100–300+ petugas) tetap dapat dipilih dan dikonfigurasi.
- **Resilient Form Selector JSON:** Struktur `form_config` harus mematuhi format spesifikasi Bagian 4.1 (`is_spa`, `wait_timeout_ms`, `username_field`, `password_field`, `extra_fields`, `auto_submit`). Editor selector harus mendukung visual tag input dan raw JSON.
- **Integrasi Bersih Tanpa Mengubah Skema `users`:** Seluruh pemetaan relasi hak akses petugas murni memanfaatkan tabel pivot `user_portal_credentials` dengan constraint `UNIQUE(user_id, portal_id)`.
- **TDD Mandatory:** Setiap service class, controller, dan endpoint diuji terlebih dahulu menggunakan Pest 4 (`it(...)`, `expect(...)`, `assertForbidden()`, `assertInertia()`) sebelum implementasi diselesaikan.

---

## File Structure & Responsibilities

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── AdminPortalController.php            # Controller CRUD master portal (Constructor injection: AdminPortalService)
│   │       └── AdminPortalMappingController.php     # Controller matriks mapping akses (Constructor injection: AdminPortalMappingService)
│   ├── Middleware/
│   │   └── HandleInertiaRequests.php               # Prop sharing can_manage_portals
│   └── Requests/
│       └── Admin/
│           ├── PortalRequest.php                   # Validasi create & update portal (auto-slug, unique ignore)
│           ├── SyncPortalUsersRequest.php          # Validasi massal mapping per-portal
│           ├── SyncUserPortalsRequest.php          # Validasi massal mapping per-user
│           └── UpdateMappingCredentialRequest.php  # Validasi update mapping credential tunggal
├── Services/
│   └── Portal/
│       ├── AdminPortalService.php                  # Service logic CRUD portal, pagination, auto-slug, toggle aktif
│       └── AdminPortalMappingService.php           # Service logic matriks mapping, transactional sync massal, update/delete
resources/
├── js/
│   ├── types/
│   │   ├── portal.ts                               # Type definition Portal, Credential, FormConfig
│   │   └── index.ts                                # Re-export types
│   ├── components/
│   │   ├── app-sidebar.tsx                         # Menu navigasi Admin Master & Mapping Portal
│   │   └── portal/
│   │       ├── selector-tag-input.tsx              # Input tag selector dengan preset cepat
│   │       ├── form-config-editor.tsx              # Visual + Raw JSON form selector editor
│   │       ├── mapping-portal-view.tsx             # Matriks mapping per-portal
│   │       └── mapping-user-view.tsx               # Matriks mapping per-petugas
│   └── pages/
│       └── admin/
│           └── portals/
│               ├── index.tsx                       # Halaman daftar master portal
│               ├── create.tsx                      # Halaman tambah master portal
│               ├── edit.tsx                        # Halaman edit master portal
│               ├── portal-form.tsx                 # Form bersama portal & akun bersama RS
│               └── mapping.tsx                     # Halaman utama matriks mapping akses
routes/
└── web.php                                         # Pendaftaran route admin.portals.*
tests/
└── Feature/
    └── PortalPelaporan/
        ├── AdminPortalServiceTest.php              # Pest test service CRUD master portal & formatting
        ├── AdminPortalControllerTest.php           # Pest test HTTP endpoints CRUD master portal
        ├── AdminPortalMappingServiceTest.php       # Pest test service sinkronisasi mapping akses transactional
        ├── AdminPortalMappingControllerTest.php    # Pest test HTTP endpoints sinkronisasi mapping akses
        └── PortalInertiaPropsTest.php              # Pest test shared props & authorization
```

---

### Task 1: Backend Master Portal Service, Controller, Form Request & Routes

**Files:**
- Create: `app/Services/Portal/AdminPortalService.php`
- Create: `app/Http/Requests/Admin/PortalRequest.php`
- Create: `app/Http/Controllers/Admin/AdminPortalController.php`
- Modify: `routes/web.php:120-135`
- Test: `tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`

**Interfaces:**
- Consumes: `App\Models\Portal`, `App\Policies\PortalPolicy`.
- Produces: `AdminPortalService` (business logic domain) & Route name `admin.portals.*` (`index`, `create`, `store`, `edit`, `update`, `destroy`, `toggle-active`).

- [x] **Step 1: Write the failing tests**

Buat file `tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`:

```php
<?php

use App\Models\Portal;
use App\Services\Portal\AdminPortalService;

beforeEach(function (): void {
    $this->service = new AdminPortalService();
});

it('paginates and filters portals with category and search', function (): void {
    Portal::factory()->create(['name' => 'SIRS Online', 'category' => 'Kemenkes']);
    Portal::factory()->create(['name' => 'SIRIKA', 'category' => 'BKKBN']);

    $result = $this->service->paginatePortals(['search' => 'SIRS']);
    expect($result['portals']->total())->toBe(1)
        ->and($result['portals']->first()['name'])->toBe('SIRS Online')
        ->and($result['categories'])->toContain('Kemenkes', 'BKKBN');
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
```

Buat file `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
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
    expect($portal->slug)->toBe('mpdn-kemkes-ri');
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
```

- [x] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalServiceTest.php tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`  
Expected: FAIL dengan `Class "App\Services\Portal\AdminPortalService" not found` / `Route [admin.portals.index] not defined.`

- [x] **Step 3: Write minimal implementation**

Buat file `app/Http/Requests/Admin/PortalRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Portal|string|int|null $portal */
        $portal = $this->route('portal');
        $portalId = $portal instanceof Portal ? $portal->id : $portal;

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:150',
                Rule::unique('portals', 'slug')->ignore($portalId),
            ],
            'category' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url'],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'auth_type' => ['required', 'in:shared,personal,both'],
            'shared_username' => ['nullable', 'string', 'max:255'],
            'shared_password' => ['nullable', 'string'],
            'shared_extra_fields' => ['nullable', 'array'],
            'form_config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
```

Buat file `app/Services/Portal/AdminPortalService.php`:

```php
<?php

namespace App\Services\Portal;

use App\Models\Portal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
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
                'description' => $portal->description,
                'auth_type' => $portal->auth_type,
                'shared_username' => $portal->shared_username,
                'has_shared_password' => filled($portal->shared_password),
                'is_active' => $portal->is_active,
                'sort_order' => $portal->sort_order,
                'user_credentials_count' => $portal->user_credentials_count ?? 0,
                'updated_at' => $portal->updated_at?->format('Y-m-d H:i'),
            ]);

        $categories = Portal::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

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

        $categories = Portal::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        return [
            'default_form_config' => $defaultFormConfig,
            'categories' => $categories,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storePortal(array $data): Portal
    {
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
        if (! filled($data['shared_password'] ?? null)) {
            unset($data['shared_password']);
        }

        $portal->update($data);

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
        return (bool) $portal->delete();
    }
}
```

Buat file `app/Http/Controllers/Admin/AdminPortalController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortalRequest;
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
}
```

Daftarkan route di `routes/web.php` dalam grup `Route::middleware(['auth', 'verified'])->group(...)`:

```php
    // Master Portal Pelaporan Eksternal (Admin)
    Route::prefix('admin/portals')
        ->name('admin.portals.')
        ->middleware('can:manage,App\Models\Portal')
        ->group(function (): void {
        Route::get('/', [AdminPortalController::class, 'index'])->name('index');
        Route::get('/create', [AdminPortalController::class, 'create'])->name('create');
        Route::post('/', [AdminPortalController::class, 'store'])->name('store');
        Route::get('/{portal}/edit', [AdminPortalController::class, 'edit'])->name('edit');
        Route::put('/{portal}', [AdminPortalController::class, 'update'])->name('update');
        Route::delete('/{portal}', [AdminPortalController::class, 'destroy'])->name('destroy');
        Route::patch('/{portal}/toggle-active', [AdminPortalController::class, 'toggleActive'])->name('toggle-active');
    });
```

- [x] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`  
Expected: PASS (7 passed)

- [x] **Step 5: Commit**

```bash
git add app/Http/Requests/Admin/PortalRequest.php app/Http/Controllers/Admin/AdminPortalController.php routes/web.php tests/Feature/PortalPelaporan/AdminPortalControllerTest.php
git commit -m "feat(portal): add admin portal controller, unified form request and routes"
```

---

### Task 2: Backend Mapping Akses Petugas Service, Controller, Form Requests & Routes

**Files:**
- Create: `app/Services/Portal/AdminPortalMappingService.php`
- Create: `app/Http/Requests/Admin/SaveMappingRowRequest.php`
- Create: `app/Http/Requests/Admin/SyncPortalUsersRequest.php`
- Create: `app/Http/Requests/Admin/SyncUserPortalsRequest.php`
- Create: `app/Http/Requests/Admin/UpdateMappingCredentialRequest.php`
- Create: `app/Http/Controllers/Admin/AdminPortalMappingController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalMappingServiceTest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php`

**Interfaces:**
- Consumes: `App\Models\Portal`, `App\Models\User`, `App\Models\UserPortalCredential`.
- Produces: `AdminPortalMappingService` (business logic domain) & Route name `admin.portals.mapping.*` (`index`, `save-row`, `sync-portal`, `sync-user`, `update-credential`, `destroy-credential`).

- [x] **Step 1: Write the failing tests**

Buat file `tests/Feature/PortalPelaporan/AdminPortalMappingServiceTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\AdminPortalMappingService;

beforeEach(function (): void {
    $this->service = new AdminPortalMappingService();
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

it('clears existing notes when saveSingleAssignment is called with updateNotes true and null or empty notes', function (): void {
    $user = User::factory()->create();

    $cred = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'use_shared', 'Catatan Awal');
    expect($cred->notes)->toBe('Catatan Awal');

    // Explicitly update notes to empty string -> notes cleared
    $updated = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'use_shared', '', true);
    expect($updated->notes)->toBeNull();

    // Explicitly update notes to null -> notes cleared
    $updated2 = $this->service->saveSingleAssignment($this->portal1->id, $user->id, true, 'use_shared', null, true);
    expect($updated2->notes)->toBeNull();
});

it('filters users by search in getMappingData', function (): void {
    User::factory()->create(['name' => 'Dr. Specialist Alpha', 'email' => 'alpha@hospital.org']);
    User::factory()->create(['name' => 'Dr. Normal Beta', 'email' => 'beta@hospital.org']);

    $data = $this->service->getMappingData($this->portal1->id, null, 'portal', ['search' => 'Specialist Alpha']);

    expect($data['users']->total())->toBe(1)
        ->and($data['users']->first()->name)->toBe('Dr. Specialist Alpha');
});
```

Buat file `tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php`:

```php
<?php

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
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
```

- [x] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalMappingServiceTest.php tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php`  
Expected: FAIL dengan `Class "App\Services\Portal\AdminPortalMappingService" not found` / `Route [admin.portals.mapping.index] not defined.`

- [x] **Step 3: Write minimal implementation**

Buat file `app/Http/Requests/Admin/SaveMappingRowRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class SaveMappingRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'portal_id' => ['required', 'integer', 'exists:portals,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'has_access' => ['required', 'boolean'],
            'credential_type' => ['nullable', 'in:use_shared,personal'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

Buat file `app/Http/Requests/Admin/SyncPortalUsersRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class SyncPortalUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'portal_id' => ['required', 'integer', 'exists:portals,id'],
            'assignments' => ['required', 'array'],
            'assignments.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'assignments.*.has_access' => ['required', 'boolean'],
            'assignments.*.credential_type' => ['nullable', 'in:use_shared,personal'],
            'assignments.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

Buat file `app/Http/Requests/Admin/SyncUserPortalsRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class SyncUserPortalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'assignments' => ['required', 'array'],
            'assignments.*.portal_id' => ['required', 'integer', 'exists:portals,id'],
            'assignments.*.has_access' => ['required', 'boolean'],
            'assignments.*.credential_type' => ['nullable', 'in:use_shared,personal'],
            'assignments.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

Buat file `app/Http/Requests/Admin/UpdateMappingCredentialRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Portal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMappingCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Portal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'credential_type' => ['required', 'in:use_shared,personal'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

Buat file `app/Services/Portal/AdminPortalMappingService.php`:

```php
<?php

namespace App\Services\Portal;

use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use Illuminate\Support\Facades\DB;

class AdminPortalMappingService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getMappingData(?int $selectedPortalId, ?int $selectedUserId, string $viewMode = 'portal', array $filters = []): array
    {
        $portals = Portal::ordered()->withCount('userCredentials')->get();

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'simrs_nik', 'role', 'dep_id'])
            ->orderBy('name');

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            $escapedSearch = addcslashes($search, '%_\\');
            $usersQuery->where(function ($q) use ($escapedSearch) {
                $q->where('name', 'like', "%{$escapedSearch}%")
                    ->orWhere('email', 'like', "%{$escapedSearch}%")
                    ->orWhere('simrs_nik', 'like', "%{$escapedSearch}%");
            });
        }

        $department = (string) ($filters['department'] ?? '');
        if ($department !== '' && $department !== '_all') {
            $usersQuery->where('dep_id', $department);
        }

        $role = (string) ($filters['role'] ?? '');
        if ($role !== '' && $role !== '_all') {
            $usersQuery->where('role', $role);
        }

        $departments = User::query()
            ->whereNotNull('dep_id')
            ->where('dep_id', '!=', '')
            ->distinct()
            ->orderBy('dep_id')
            ->pluck('dep_id');

        $targetPortalId = $selectedPortalId ?? $portals->first()?->id ?? 0;
        $selectedPortal = $portals->firstWhere('id', $targetPortalId) ?? $portals->first();

        $selectedUser = ($selectedUserId && $selectedUserId > 0) ? User::find($selectedUserId) : null;

        $portalCredentials = [];
        if ($selectedPortal) {
            $portalCredentials = UserPortalCredential::where('portal_id', $selectedPortal->id)
                ->get()
                ->keyBy('user_id');
        }

        $userCredentials = [];
        if ($selectedUser) {
            $userCredentials = UserPortalCredential::where('user_id', $selectedUser->id)
                ->get()
                ->keyBy('portal_id');
        }

        return [
            'view_mode' => $viewMode,
            'portals' => $portals->map(fn (Portal $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'auth_type' => $p->auth_type,
                'is_active' => $p->is_active,
                'user_credentials_count' => $p->user_credentials_count ?? 0,
            ]),
            'selected_portal' => $selectedPortal ? [
                'id' => $selectedPortal->id,
                'name' => $selectedPortal->name,
                'category' => $selectedPortal->category,
                'auth_type' => $selectedPortal->auth_type,
                'is_active' => $selectedPortal->is_active,
            ] : null,
            'selected_user' => $selectedUser ? [
                'id' => $selectedUser->id,
                'name' => $selectedUser->name,
                'email' => $selectedUser->email,
                'simrs_nik' => $selectedUser->simrs_nik,
                'role' => $selectedUser->role,
                'dep_id' => $selectedUser->dep_id,
            ] : null,
            'users' => $usersQuery->paginate(50)->withQueryString(),
            'all_users' => User::query()
                ->select(['id', 'name', 'email', 'simrs_nik', 'role', 'dep_id'])
                ->orderBy('name')
                ->get(),
            'portal_credentials' => $portalCredentials,
            'user_credentials' => $userCredentials,
            'departments' => $departments,
            'filters' => [
                'portal_id' => $targetPortalId,
                'user_id' => $selectedUserId ?? 0,
                'search' => $search,
                'department' => $department,
                'role' => $role,
            ],
        ];
    }

    /**
     * @param  array<int, array{user_id: int, has_access: bool, credential_type?: string, notes?: string|null}>  $assignments
     */
    public function syncPortalUsers(Portal $portal, array $assignments): void
    {
        DB::transaction(function () use ($portal, $assignments): void {
            foreach ($assignments as $item) {
                $userId = (int) $item['user_id'];
                $hasAccess = (bool) $item['has_access'];

                if ($hasAccess) {
                    UserPortalCredential::updateOrCreate(
                        [
                            'portal_id' => $portal->id,
                            'user_id' => $userId,
                        ],
                        [
                            'credential_type' => $item['credential_type'] ?? 'use_shared',
                            'is_active' => true,
                            'notes' => $item['notes'] ?? null,
                        ]
                    );
                } else {
                    UserPortalCredential::where('portal_id', $portal->id)
                        ->where('user_id', $userId)
                        ->delete();
                }
            }
        });
    }

    /**
     * @param  array<int, array{portal_id: int, has_access: bool, credential_type?: string, notes?: string|null}>  $assignments
     */
    public function syncUserPortals(User $user, array $assignments): void
    {
        DB::transaction(function () use ($user, $assignments): void {
            foreach ($assignments as $item) {
                $portalId = (int) $item['portal_id'];
                $hasAccess = (bool) $item['has_access'];

                if ($hasAccess) {
                    UserPortalCredential::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'portal_id' => $portalId,
                        ],
                        [
                            'credential_type' => $item['credential_type'] ?? 'use_shared',
                            'is_active' => true,
                            'notes' => $item['notes'] ?? null,
                        ]
                    );
                } else {
                    UserPortalCredential::where('user_id', $user->id)
                        ->where('portal_id', $portalId)
                        ->delete();
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCredential(UserPortalCredential $credential, array $data): UserPortalCredential
    {
        $credential->update($data);

        return $credential;
    }

    public function saveSingleAssignment(
        int $portalId,
        int $userId,
        bool $hasAccess,
        string $credentialType = 'use_shared',
        ?string $notes = null,
        ?bool $updateNotes = null
    ): ?UserPortalCredential {
        if ($hasAccess) {
            $attributes = [
                'credential_type' => $credentialType,
                'is_active' => true,
            ];

            $shouldUpdateNotes = $updateNotes !== null ? $updateNotes : ($notes !== null);
            if ($shouldUpdateNotes) {
                $attributes['notes'] = ($notes !== null && trim($notes) !== '') ? trim($notes) : null;
            }

            return UserPortalCredential::updateOrCreate(
                [
                    'portal_id' => $portalId,
                    'user_id' => $userId,
                ],
                $attributes
            );
        }

        UserPortalCredential::where('portal_id', $portalId)
            ->where('user_id', $userId)
            ->delete();

        return null;
    }

    public function destroyCredential(UserPortalCredential $credential): bool
    {
        return (bool) $credential->delete();
    }
}
```

Buat file `app/Http/Controllers/Admin/AdminPortalMappingController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMappingRowRequest;
use App\Http\Requests\Admin\SyncPortalUsersRequest;
use App\Http\Requests\Admin\SyncUserPortalsRequest;
use App\Http\Requests\Admin\UpdateMappingCredentialRequest;
use App\Models\Portal;
use App\Models\User;
use App\Models\UserPortalCredential;
use App\Services\Portal\AdminPortalMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminPortalMappingController extends Controller
{
    public function __construct(
        private AdminPortalMappingService $mappingService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage', Portal::class);

        $portalId = $request->filled('portal_id') ? (int) $request->input('portal_id') : null;
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $viewMode = $request->input('view_mode', 'portal');

        $data = $this->mappingService->getMappingData($portalId, $userId, $viewMode, $request->all());

        return Inertia::render('admin/portals/mapping', $data);
    }

    public function saveRow(SaveMappingRowRequest $request): JsonResponse|RedirectResponse
    {
        $credential = $this->mappingService->saveSingleAssignment(
            (int) $request->validated('portal_id'),
            (int) $request->validated('user_id'),
            (bool) $request->validated('has_access'),
            (string) ($request->validated('credential_type') ?? 'use_shared'),
            $request->validated('notes'),
            $request->has('notes')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'credential' => $credential ? [
                    'id' => $credential->id,
                    'portal_id' => $credential->portal_id,
                    'user_id' => $credential->user_id,
                    'credential_type' => $credential->credential_type,
                    'is_active' => $credential->is_active,
                    'notes' => $credential->notes,
                ] : null,
            ]);
        }

        return back()->with('success', 'Mapping hak akses berhasil diperbarui.');
    }

    public function syncPortal(SyncPortalUsersRequest $request): RedirectResponse
    {
        $portal = Portal::findOrFail($request->validated('portal_id'));
        $this->mappingService->syncPortalUsers($portal, $request->validated('assignments'));

        return back()->with('success', 'Mapping hak akses portal berhasil disimpan.');
    }

    public function syncUser(SyncUserPortalsRequest $request): RedirectResponse
    {
        $user = User::findOrFail($request->validated('user_id'));
        $this->mappingService->syncUserPortals($user, $request->validated('assignments'));

        return back()->with('success', 'Mapping hak akses petugas berhasil disimpan.');
    }

    public function updateCredential(UpdateMappingCredentialRequest $request, UserPortalCredential $credential): RedirectResponse
    {
        $this->mappingService->updateCredential($credential, $request->validated());

        return back()->with('success', 'Kredensial mapping berhasil diperbarui.');
    }

    public function destroyCredential(UserPortalCredential $credential): RedirectResponse
    {
        Gate::authorize('manage', Portal::class);

        $this->mappingService->destroyCredential($credential);

        return back()->with('success', 'Hak akses portal berhasil dicabut.');
    }
}
```

Tambahkan rute pada `routes/web.php` di dalam grup `admin/portals`:

```php
    // Master Portal Pelaporan Eksternal (Admin)
    Route::prefix('admin/portals')
        ->name('admin.portals.')
        ->middleware('can:manage,App\Models\Portal')
        ->group(function (): void {
        Route::get('/', [AdminPortalController::class, 'index'])->name('index');
        Route::get('/create', [AdminPortalController::class, 'create'])->name('create');
        Route::post('/', [AdminPortalController::class, 'store'])->name('store');
        Route::get('/{portal}/edit', [AdminPortalController::class, 'edit'])->name('edit');
        Route::put('/{portal}', [AdminPortalController::class, 'update'])->name('update');
        Route::delete('/{portal}', [AdminPortalController::class, 'destroy'])->name('destroy');
        Route::patch('/{portal}/toggle-active', [AdminPortalController::class, 'toggleActive'])->name('toggle-active');

        // Mapping Akses Petugas
        Route::get('/mapping', [AdminPortalMappingController::class, 'index'])->name('mapping.index');
        Route::post('/mapping/save-row', [AdminPortalMappingController::class, 'saveRow'])->name('mapping.save-row');
        Route::post('/mapping/sync-portal', [AdminPortalMappingController::class, 'syncPortal'])->name('mapping.sync-portal');
        Route::post('/mapping/sync-user', [AdminPortalMappingController::class, 'syncUser'])->name('mapping.sync-user');
        Route::patch('/mapping/{credential}', [AdminPortalMappingController::class, 'updateCredential'])->name('mapping.update-credential');
        Route::delete('/mapping/{credential}', [AdminPortalMappingController::class, 'destroyCredential'])->name('mapping.destroy-credential');
    });
```

- [x] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php`  
Expected: PASS (7 passed)

- [x] **Step 5: Commit**

```bash
git add app/Http/Requests/Admin/SaveMappingRowRequest.php app/Http/Requests/Admin/SyncPortalUsersRequest.php app/Http/Requests/Admin/SyncUserPortalsRequest.php app/Http/Requests/Admin/UpdateMappingCredentialRequest.php app/Http/Controllers/Admin/AdminPortalMappingController.php routes/web.php tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php
git commit -m "feat(portal): add admin portal mapping controller and access matrix sync routes"
```

---

### Task 3: Shared Inertia Props, Global TypeScript Definitions & App Navigation

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php:44-67`
- Create: `resources/js/types/portal.ts`
- Modify: `resources/js/types/index.ts:1-10`
- Modify: `resources/js/components/app-sidebar.tsx:130-165`
- Test: `tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php`

**Interfaces:**
- Consumes: `App\Models\Portal`, `App\Policies\PortalPolicy`.
- Produces:
  - Shared Inertia Prop: `permissions.can_manage_portals`.
  - TypeScript types: `Portal`, `UserPortalCredential`, `FormConfig`, `FormConfigSelectorField`, `FormConfigExtraField`, `PortalAuthType`, `CredentialType`.
  - Sidebar menu items for Master Portal & Mapping Akses.

- [x] **Step 1: Write the failing test**

Buat file `tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php`:

```php
<?php

use App\Models\User;

it('shares can_manage_portals permission as true for admin and false for staff', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('permissions.can_manage_portals', true)
        );

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('permissions.can_manage_portals', false)
        );
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php`  
Expected: FAIL dengan `Failed asserting that null is true.`

- [x] **Step 3: Write minimal implementation**

Edit `app/Http/Middleware/HandleInertiaRequests.php` pada method `share()` bagian `permissions`:

```php
            'permissions' => [
                'can_manage_portals' => $request->user()?->can('manage', \App\Models\Portal::class) ?? false,
                'can_access_payroll' => $request->user()?->canAccessPayroll() ?? false,
                'can_manage_payroll_access' => $request->user()?->canManagePayrollAccess() ?? false,
                // ... props lainnya
```

Buat file `resources/js/types/portal.ts`:

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
```

Edit `resources/js/types/index.ts` untuk mengekspor `portal.ts`:

```typescript
export type * from './auth';
export type * from './navigation';
export type * from './ui';
export type * from './ticket';
export type * from './portal';

import type { Auth } from './auth';

export type SharedData = {
    name: string;
    auth: Auth;
    permissions: {
        can_manage_portals?: boolean;
        [key: string]: unknown;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
};
```

Edit `resources/js/components/app-sidebar.tsx`:
Tambahkan menu `Master Portal` dan `Mapping Akses` ke navigasi dengan ikon `Globe` dan `ShieldCheck`.

```typescript
import {
    // ... ikon yang sudah ada
    Globe,
    ShieldCheck,
} from 'lucide-react';
```

Tambahkan navigasi grup portal:
```typescript
const portalNavItems: NavItem[] = [
    {
        title: 'Master Portal',
        href: '/admin/portals',
        icon: Globe,
    },
    {
        title: 'Mapping Akses',
        href: '/admin/portals/mapping',
        icon: ShieldCheck,
    },
];
```

Dan render di `SidebarContent` dengan pengecekan `permissions.can_manage_portals` dari hook `usePage()`:
```typescript
    const { permissions } = usePage<SharedData>().props;

    // ...
    <SidebarContent>
        <NavMain items={mainNavItems} />
        {permissions?.can_manage_portals && (
            <NavMain items={portalNavItems} label="Portal Eksternal" />
        )}
        <NavMain items={monitoringNavItems} label="Monitoring" />
        <NavMain items={settingsNavItems} label="Pengaturan" />
    </SidebarContent>
```

- [x] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php`  
Expected: PASS (1 passed)

- [x] **Step 5: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php resources/js/types/portal.ts resources/js/types/index.ts resources/js/components/app-sidebar.tsx tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php
git commit -m "feat(portal): add shared inertia permission, typescript types and sidebar navigation"
```

---

### Task 4: Interactive Form Selector Config Editor Component

**Files:**
- Create: `resources/js/components/portal/selector-tag-input.tsx`
- Create: `resources/js/components/portal/form-config-editor.tsx`

**Interfaces:**
- Consumes: Radix UI Tabs, Input, Button, Badge, Switch, Textarea.
- Produces: `<FormConfigEditor value={formConfig} onChange={setFormConfig} />` dengan dua mode: Visual Builder & Raw JSON Editor, lengkap dengan validasi sintaks.

- [x] **Step 1: Write component `selector-tag-input.tsx`**

Buat file `resources/js/components/portal/selector-tag-input.tsx`:

```tsx
import { Plus, X } from 'lucide-react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface SelectorTagInputProps {
    selectors: string[];
    onChange: (selectors: string[]) => void;
    presets?: string[];
    placeholder?: string;
    label?: string;
}

export function SelectorTagInput({
    selectors,
    onChange,
    presets = [],
    placeholder = 'Tambah selector CSS / XPath...',
    label,
}: SelectorTagInputProps) {
    const [inputValue, setInputValue] = useState('');

    const handleAdd = (valueToAdd?: string) => {
        const target = (valueToAdd ?? inputValue).trim();
        if (!target) return;
        if (!selectors.includes(target)) {
            onChange([...selectors, target]);
        }
        if (!valueToAdd) {
            setInputValue('');
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAdd();
        }
    };

    const handleRemove = (index: number) => {
        onChange(selectors.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-2">
            {label && <label className="text-sm font-medium text-foreground">{label}</label>}

            <div className="flex flex-wrap gap-1.5 min-h-[38px] p-2 bg-muted/40 rounded-lg border border-border">
                {selectors.length === 0 ? (
                    <span className="text-xs text-muted-foreground italic self-center">
                        Belum ada selector dikonfigurasi
                    </span>
                ) : (
                    selectors.map((sel, idx) => (
                        <Badge
                            key={idx}
                            variant="secondary"
                            className="font-mono text-xs px-2 py-0.5 gap-1.5 items-center bg-background border border-border"
                        >
                            <span>{sel}</span>
                            <button
                                type="button"
                                onClick={() => handleRemove(idx)}
                                className="text-muted-foreground hover:text-destructive focus:outline-none"
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))
                )}
            </div>

            <div className="flex gap-2">
                <Input
                    type="text"
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    className="font-mono text-xs h-9"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => handleAdd()}
                    className="h-9 px-3 shrink-0"
                >
                    <Plus className="size-4 mr-1" /> Tambah
                </Button>
            </div>

            {presets.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5 pt-1">
                    <span className="text-[11px] text-muted-foreground">Preset cepat:</span>
                    {presets.map((preset) => (
                        <button
                            key={preset}
                            type="button"
                            onClick={() => handleAdd(preset)}
                            disabled={selectors.includes(preset)}
                            className="text-[11px] font-mono px-1.5 py-0.5 rounded bg-muted hover:bg-accent text-foreground disabled:opacity-40 disabled:cursor-not-allowed border border-border/50"
                        >
                            + {preset}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
```

- [x] **Step 2: Write component `form-config-editor.tsx`**

Buat file `resources/js/components/portal/form-config-editor.tsx`:

```tsx
import { AlertCircle, Code, Eye, Plus, Trash2 } from 'lucide-react';
import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { FormConfig } from '@/types';
import { SelectorTagInput } from './selector-tag-input';

interface FormConfigEditorProps {
    value: FormConfig;
    onChange: (config: FormConfig) => void;
}

const DEFAULT_CONFIG: FormConfig = {
    is_spa: false,
    wait_timeout_ms: 10000,
    username_field: { selectors: ["input[name='username']", '#username'] },
    password_field: { selectors: ["input[name='password']", '#password'] },
    extra_fields: [],
    auto_submit: false,
};

export function FormConfigEditor({ value, onChange }: FormConfigEditorProps) {
    const config = value ?? DEFAULT_CONFIG;
    const [rawJson, setRawJson] = useState<string>(() => JSON.stringify(config, null, 2));
    const [jsonError, setJsonError] = useState<string | null>(null);
    const [tab, setTab] = useState<'visual' | 'raw'>('visual');

    const handleVisualUpdate = (partial: Partial<FormConfig>) => {
        const updated = { ...config, ...partial };
        onChange(updated);
        setRawJson(JSON.stringify(updated, null, 2));
        setJsonError(null);
    };

    const handleRawJsonChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const text = e.target.value;
        setRawJson(text);
        try {
            const parsed = JSON.parse(text) as FormConfig;
            setJsonError(null);
            onChange(parsed);
        } catch (err: unknown) {
            setJsonError((err as Error).message);
        }
    };

    const handleAddExtraField = () => {
        const newExtra = [
            ...(config.extra_fields || []),
            { key: `field_${(config.extra_fields || []).length + 1}`, selectors: [] },
        ];
        handleVisualUpdate({ extra_fields: newExtra });
    };

    const handleRemoveExtraField = (index: number) => {
        const updated = (config.extra_fields || []).filter((_, i) => i !== index);
        handleVisualUpdate({ extra_fields: updated });
    };

    const handleExtraFieldKeyChange = (index: number, newKey: string) => {
        const updated = [...(config.extra_fields || [])];
        updated[index] = { ...updated[index], key: newKey };
        handleVisualUpdate({ extra_fields: updated });
    };

    const handleExtraFieldSelectorsChange = (index: number, selectors: string[]) => {
        const updated = [...(config.extra_fields || [])];
        updated[index] = { ...updated[index], selectors };
        handleVisualUpdate({ extra_fields: updated });
    };

    return (
        <div className="rounded-xl border border-border bg-card p-4 space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border pb-3">
                <div>
                    <h3 className="text-sm font-semibold text-foreground">Konfigurasi Form Selector Login</h3>
                    <p className="text-xs text-muted-foreground">
                        Pengaturan selector DOM untuk injeksi ekstensi browser autofill
                    </p>
                </div>
                <Tabs value={tab} onValueChange={(v) => setTab(v as 'visual' | 'raw')}>
                    <TabsList className="h-8">
                        <TabsTrigger value="visual" className="text-xs gap-1.5">
                            <Eye className="size-3.5" /> Visual Builder
                        </TabsTrigger>
                        <TabsTrigger value="raw" className="text-xs gap-1.5">
                            <Code className="size-3.5" /> Raw JSON
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            {tab === 'visual' ? (
                <div className="space-y-5">
                    {/* General Form Toggles */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-3 bg-muted/20 rounded-lg border border-border/60">
                        <div className="flex items-center gap-3">
                            <input
                                id="is_spa"
                                type="checkbox"
                                checked={config.is_spa}
                                onChange={(e) => handleVisualUpdate({ is_spa: e.target.checked })}
                                className="rounded border-input text-primary focus:ring-primary size-4"
                            />
                            <div>
                                <Label htmlFor="is_spa" className="text-xs font-semibold cursor-pointer">
                                    SPA Mode (React / Vue)
                                </Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Tunggu elemen dirender dinamis di browser
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="wait_timeout" className="text-xs font-semibold">
                                Timeout Menunggu (ms)
                            </Label>
                            <Input
                                id="wait_timeout"
                                type="number"
                                min={1000}
                                max={60000}
                                step={500}
                                value={config.wait_timeout_ms || 10000}
                                onChange={(e) =>
                                    handleVisualUpdate({ wait_timeout_ms: parseInt(e.target.value, 10) || 10000 })
                                }
                                className="h-8 text-xs mt-1"
                            />
                        </div>

                        <div className="flex items-center gap-3">
                            <input
                                id="auto_submit"
                                type="checkbox"
                                checked={config.auto_submit}
                                onChange={(e) => handleVisualUpdate({ auto_submit: e.target.checked })}
                                className="rounded border-input text-primary focus:ring-primary size-4"
                            />
                            <div>
                                <Label htmlFor="auto_submit" className="text-xs font-semibold cursor-pointer">
                                    Auto Submit Form
                                </Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Nonaktifkan jika website memiliki CAPTCHA
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Username Selector */}
                    <SelectorTagInput
                        label="Selector Bidang Username / Email"
                        selectors={config.username_field?.selectors || []}
                        onChange={(selectors) =>
                            handleVisualUpdate({
                                username_field: { selectors },
                            })
                        }
                        presets={[
                            '#username',
                            '#email',
                            '#c',
                            "input[name='username']",
                            "input[name='email']",
                            "input[type='email']",
                        ]}
                        placeholder="Contoh: #username atau input[name='email']"
                    />

                    {/* Password Selector */}
                    <SelectorTagInput
                        label="Selector Bidang Password"
                        selectors={config.password_field?.selectors || []}
                        onChange={(selectors) =>
                            handleVisualUpdate({
                                password_field: { selectors },
                            })
                        }
                        presets={[
                            '#password',
                            '#pass',
                            "input[name='password']",
                            "input[name='pwd']",
                            "input[type='password']",
                        ]}
                        placeholder="Contoh: #password atau input[name='password']"
                    />

                    {/* Extra Fields */}
                    <div className="space-y-3 pt-2 border-t border-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label className="text-xs font-semibold">Field Tambahan (Kode Satker / Fasyankes / Captcha Info)</Label>
                                <p className="text-[11px] text-muted-foreground">
                                    Tambahkan field jika website membutuhkan input identitas selain username dan password
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleAddExtraField}
                                className="h-7 text-xs"
                            >
                                <Plus className="size-3 mr-1" /> Tambah Field
                            </Button>
                        </div>

                        {(config.extra_fields || []).map((field, fIdx) => (
                            <div
                                key={fIdx}
                                className="p-3 bg-muted/20 border border-border rounded-lg space-y-2 relative"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <div className="flex-1">
                                        <Label className="text-[11px] text-muted-foreground">Nama Kunci (Key)</Label>
                                        <Input
                                            type="text"
                                            value={field.key}
                                            onChange={(e) => handleExtraFieldKeyChange(fIdx, e.target.value)}
                                            placeholder="misal: kode_satker"
                                            className="h-8 font-mono text-xs mt-0.5"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRemoveExtraField(fIdx)}
                                        className="size-8 text-destructive hover:bg-destructive/10 shrink-0 self-end"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                                <SelectorTagInput
                                    selectors={field.selectors || []}
                                    onChange={(selectors) => handleExtraFieldSelectorsChange(fIdx, selectors)}
                                    placeholder={`Selector untuk field ${field.key}...`}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <div className="space-y-2">
                    <Textarea
                        value={rawJson}
                        onChange={handleRawJsonChange}
                        rows={14}
                        className="font-mono text-xs leading-relaxed"
                        placeholder="Masukkan JSON form_config..."
                    />
                    {jsonError && (
                        <div className="flex items-center gap-2 text-xs text-destructive bg-destructive/10 p-2 rounded-lg border border-destructive/20">
                            <AlertCircle className="size-4 shrink-0" />
                            <span>Syntax JSON Tidak Valid: {jsonError}</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
```

- [x] **Step 3: Verify TypeScript Types for Editor Components**

Run: `npm run types`  
Expected: 0 errors.

- [x] **Step 4: Commit**

```bash
git add resources/js/components/portal/selector-tag-input.tsx resources/js/components/portal/form-config-editor.tsx
git commit -m "feat(portal): add interactive FormConfigEditor and SelectorTagInput components"
```

---

### Task 5: Master Portal CRUD UI Pages (`index.tsx`, `create.tsx`, `edit.tsx`, `portal-form.tsx`)

**Files:**
- Create: `resources/js/pages/admin/portals/portal-form.tsx`
- Create: `resources/js/pages/admin/portals/index.tsx`
- Create: `resources/js/pages/admin/portals/create.tsx`
- Create: `resources/js/pages/admin/portals/edit.tsx`

**Interfaces:**
- Consumes: `AppLayout`, `DataTableToolbar`, `DataTablePagination`, `FormConfigEditor`, `Portal` type.
- Produces: Halaman CRUD master portal lengkap dengan filter kategori, toggle active, sort order, and form akun bersama RS.

- [x] **Step 1: Create `portal-form.tsx`**

Buat file `resources/js/pages/admin/portals/portal-form.tsx`:

```tsx
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Eye, EyeOff, Globe, Lock, Shield, Sparkles } from 'lucide-react';
import React, { useState } from 'react';
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

export function PortalForm({ initialData, categories, isEditing = false }: PortalFormProps) {
    const [showPassword, setShowPassword] = useState(false);

    const defaultFormConfig: FormConfig = {
        is_spa: false,
        wait_timeout_ms: 10000,
        username_field: {
            selectors: ["input[name='username']", "input[name='email']", '#username', '#email'],
        },
        password_field: {
            selectors: ["input[name='password']", '#password'],
        },
        extra_fields: [],
        auto_submit: false,
    };

    const { data, setData, post, put, processing, errors } = useForm({
        name: initialData?.name ?? '',
        slug: initialData?.slug ?? '',
        category: initialData?.category ?? (categories[0] || 'Kemenkes'),
        url: initialData?.url ?? '',
        url_pattern: initialData?.url_pattern ?? '',
        icon_path: initialData?.icon_path ?? '',
        description: initialData?.description ?? '',
        auth_type: (initialData?.auth_type ?? 'both') as PortalAuthType,
        shared_username: initialData?.shared_username ?? '',
        shared_password: '',
        form_config: (initialData?.form_config ?? defaultFormConfig) as FormConfig,
        is_active: initialData?.is_active ?? true,
        sort_order: initialData?.sort_order ?? 0,
    });

    const handleAutoSlug = () => {
        if (!data.name) return;
        const slugified = data.name
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-');
        setData('slug', slugified);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing && initialData?.id) {
            put(`/admin/portals/${initialData.id}`);
        } else {
            post('/admin/portals');
        }
    };

    const showSharedSection = data.auth_type === 'shared' || data.auth_type === 'both';

    return (
        <form onSubmit={handleSubmit} className="space-y-6 max-w-5xl">
            {/* Section 1: Target Website Information */}
            <div className="rounded-2xl border border-border bg-card p-6 shadow-sm space-y-4">
                <div className="flex items-center gap-2.5 pb-2 border-b border-border">
                    <Globe className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">Informasi Target Website</h2>
                        <p className="text-xs text-muted-foreground">Detail platform eksternal dan alamat login resmi</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="name">Nama Resmi Portal <span className="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            onBlur={handleAutoSlug}
                            placeholder="Contoh: SIRS Online Kemkes"
                            className="mt-1"
                            required
                        />
                        {errors.name && <p className="text-xs text-destructive mt-1">{errors.name}</p>}
                    </div>

                    <div>
                        <div className="flex justify-between items-center">
                            <Label htmlFor="slug">Slug URL <span className="text-destructive">*</span></Label>
                            <button
                                type="button"
                                onClick={handleAutoSlug}
                                className="text-xs text-primary hover:underline flex items-center gap-1"
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
                        {errors.slug && <p className="text-xs text-destructive mt-1">{errors.slug}</p>}
                    </div>

                    <div>
                        <Label htmlFor="category">Kategori Portal <span className="text-destructive">*</span></Label>
                        <Input
                            id="category"
                            list="category-suggestions"
                            value={data.category}
                            onChange={(e) => setData('category', e.target.value)}
                            placeholder="Pilih atau ketik kategori..."
                            className="mt-1"
                            required
                        />
                        <datalist id="category-suggestions">
                            {categories.map((cat) => (
                                <option key={cat} value={cat} />
                            ))}
                            <option value="Kemenkes" />
                            <option value="BKKBN" />
                            <option value="Kemendukbangga" />
                            <option value="Mutu & Akreditasi" />
                        </datalist>
                        {errors.category && <p className="text-xs text-destructive mt-1">{errors.category}</p>}
                    </div>

                    <div>
                        <Label htmlFor="url">URL Form Login Target <span className="text-destructive">*</span></Label>
                        <Input
                            id="url"
                            type="url"
                            value={data.url}
                            onChange={(e) => setData('url', e.target.value)}
                            placeholder="https://akun-yankes.kemkes.go.id"
                            className="mt-1 font-mono text-xs"
                            required
                        />
                        {errors.url && <p className="text-xs text-destructive mt-1">{errors.url}</p>}
                    </div>

                    <div>
                        <Label htmlFor="url_pattern">URL Match Pattern (Wildcard)</Label>
                        <Input
                            id="url_pattern"
                            value={data.url_pattern}
                            onChange={(e) => setData('url_pattern', e.target.value)}
                            placeholder="*://*.kemkes.go.id/*"
                            className="mt-1 font-mono text-xs"
                        />
                        <p className="text-[11px] text-muted-foreground mt-1">
                            Pola tab browser target untuk pencocokan injeksi autofill
                        </p>
                    </div>

                    <div>
                        <Label htmlFor="icon_path">Logo / Path Ikon</Label>
                        <Input
                            id="icon_path"
                            value={data.icon_path}
                            onChange={(e) => setData('icon_path', e.target.value)}
                            placeholder="Contoh: /images/portals/kemenkes.png"
                            className="mt-1 text-xs"
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="description">Deskripsi Singkat Portal</Label>
                    <Textarea
                        id="description"
                        rows={2}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Deskripsi singkat fungsi pelaporan data pada sistem eksternal ini..."
                        className="mt-1 text-xs"
                    />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <Label htmlFor="sort_order">Urutan Tampilan (Sort Order)</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', parseInt(e.target.value, 10) || 0)}
                            className="mt-1 w-32"
                        />
                    </div>

                    <div className="flex items-center gap-3 self-center pt-3">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="rounded border-input text-primary focus:ring-primary size-4"
                        />
                        <Label htmlFor="is_active" className="text-sm cursor-pointer">
                            Portal Aktif di SIMRS
                        </Label>
                    </div>
                </div>
            </div>

            {/* Section 2: Account Policy & Shared Credentials */}
            <div className="rounded-2xl border border-border bg-card p-6 shadow-sm space-y-4">
                <div className="flex items-center gap-2.5 pb-2 border-b border-border">
                    <Shield className="size-5 text-primary" />
                    <div>
                        <h2 className="text-base font-semibold text-foreground">Kebijakan Akun & Autentikasi</h2>
                        <p className="text-xs text-muted-foreground">Tentukan apakah login memakai akun bersama RS atau akun personal staf</p>
                    </div>
                </div>

                <div className="max-w-md">
                    <Label htmlFor="auth_type">Kebijakan Login Petugas <span className="text-destructive">*</span></Label>
                    <Select
                        value={data.auth_type}
                        onValueChange={(val) => setData('auth_type', val as PortalAuthType)}
                    >
                        <SelectTrigger className="mt-1">
                            <SelectValue placeholder="Pilih kebijakan login" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="both">Akun Bersama RS & Akun Pribadi (Fleksibel / Hybrid)</SelectItem>
                            <SelectItem value="shared">Hanya Akun Bersama RS (Single Institutional Account)</SelectItem>
                            <SelectItem value="personal">Hanya Akun Pribadi Petugas (Personal Account Only)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {showSharedSection && (
                    <div className="p-4 rounded-xl bg-muted/30 border border-border/80 space-y-4">
                        <div className="flex items-center gap-2">
                            <Lock className="size-4 text-amber-500" />
                            <h3 className="text-sm font-semibold text-foreground">Kredensial Bersama Tingkat Rumah Sakit</h3>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Kredensial ini disimpan terenkripsi di database dan diinjeksi ke browser staf yang berwenang.
                        </p>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="shared_username">Username Bersama RS</Label>
                                <Input
                                    id="shared_username"
                                    value={data.shared_username}
                                    onChange={(e) => setData('shared_username', e.target.value)}
                                    placeholder="Username akun instansi..."
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <Label htmlFor="shared_password">
                                    Password Bersama RS
                                    {isEditing && initialData?.has_shared_password && (
                                        <span className="text-xs text-muted-foreground ml-1.5 font-normal">
                                            (Tersimpan terenkripsi. Kosongkan jika tidak diubah)
                                        </span>
                                    )}
                                </Label>
                                <div className="relative mt-1">
                                    <Input
                                        id="shared_password"
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.shared_password}
                                        onChange={(e) => setData('shared_password', e.target.value)}
                                        placeholder={
                                            isEditing && initialData?.has_shared_password
                                                ? '••••••••••••'
                                                : 'Ketik password akun bersama...'
                                        }
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    >
                                        {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                                    </button>
                                </div>
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
            <div className="flex items-center justify-between pt-4 border-t border-border">
                <Button asChild variant="outline">
                    <Link href="/admin/portals" className="gap-2">
                        <ArrowLeft className="size-4" /> Batal & Kembali
                    </Link>
                </Button>

                <Button type="submit" disabled={processing} className="gap-2">
                    <Check className="size-4" />
                    {isEditing ? 'Simpan Perubahan Portal' : 'Simpan & Tambah Portal'}
                </Button>
            </div>
        </form>
    );
}
```

- [x] **Step 2: Create `index.tsx`**

Buat file `resources/js/pages/admin/portals/index.tsx`:

import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Globe, KeyRound, Pencil, Plus, Search, ShieldCheck, Trash2, Users, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTablePagination } from '@/components/data-table-pagination';
import { DataTableToolbar } from '@/components/data-table-toolbar';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Portal } from '@/types';

interface PaginatedPortals {
    data: (Portal & { has_shared_password?: boolean; user_credentials_count: number })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    portals: PaginatedPortals;
    categories: string[];
    filters: {
        search: string;
        category: string;
        status: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
];

export default function AdminPortalsIndex({ portals, categories, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '_all');
    const [status, setStatus] = useState(filters.status || '_all');

    const applyFilters = useCallback(
        (newFilters: Record<string, string>) => {
            const query = {
                search,
                category: category === '_all' ? '' : category,
                status: status === '_all' ? '' : status,
                ...newFilters,
            };
            Object.keys(query).forEach((key) => {
                if (!query[key as keyof typeof query]) delete query[key as keyof typeof query];
            });
            router.get('/admin/portals', query, { preserveState: true, replace: true });
        },
        [search, category, status],
    );

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const handleToggleActive = (portal: Portal) => {
        router.patch(`/admin/portals/${portal.id}/toggle-active`, {}, { preserveScroll: true });
    };

    const [portalToDelete, setPortalToDelete] = useState<Portal | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = (portal: Portal) => {
        setPortalToDelete(portal);
    };

    const handleConfirmDelete = () => {
        if (!portalToDelete) return;
        setIsDeleting(true);
        router.delete(`/admin/portals/${portalToDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                setPortalToDelete(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Portal Pelaporan Eksternal" />

            <div className="flex flex-col gap-5">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <Heading
                        title="Master Portal Pelaporan Eksternal"
                        description="Kelola katalog website pelaporan resmi (Kemenkes & BKKBN), akun bersama RS, dan konfigurasi form selector autofill."
                    />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" className="gap-2">
                            <Link href="/admin/portals/mapping">
                                <ShieldCheck className="size-4 text-emerald-600" /> Mapping Akses
                            </Link>
                        </Button>
                        <Button asChild className="gap-2">
                            <Link href="/admin/portals/create">
                                <Plus className="size-4" /> Tambah Portal
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTableToolbar>
                    <form onSubmit={handleSearch} className="flex min-w-0 flex-1 gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Cari nama portal, kategori, atau URL..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 h-11"
                            />
                        </div>
                        <Button type="submit" variant="secondary" className="h-11">
                            Cari
                        </Button>
                    </form>

                    <Select
                        value={filters.category || '_all'}
                        onValueChange={(v) => applyFilters({ category: v === '_all' ? '' : v })}
                    >
                        <SelectTrigger className="h-11 w-[160px]">
                            <SelectValue placeholder="Semua Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Kategori</SelectItem>
                            {categories.map((cat) => (
                                <SelectItem key={cat} value={cat}>
                                    {cat}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.status || '_all'}
                        onValueChange={(v) => applyFilters({ status: v === '_all' ? '' : v })}
                    >
                        <SelectTrigger className="h-11 w-[140px]">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>

                    {(filters.search || filters.category || filters.status) && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-11"
                            onClick={() => router.get('/admin/portals')}
                            aria-label="Reset Filter"
                        >
                            <X className="size-4" />
                        </Button>
                    )}
                </DataTableToolbar>

                <div className="data-table overflow-hidden rounded-xl border border-border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                                <tr>
                                    <th className="px-4 py-3">Portal Target</th>
                                    <th className="px-4 py-3">Kebijakan Akun</th>
                                    <th className="px-4 py-3">Akun Bersama RS</th>
                                    <th className="px-4 py-3 text-center">Petugas</th>
                                    <th className="px-4 py-3 text-center">Urutan</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {portals.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-10 text-center">
                                            <EmptyState
                                                title="Belum Ada Portal"
                                                description="Belum ada website pelaporan yang didaftarkan. Klik tombol di bawah untuk menambah."
                                                action={
                                                    <Button asChild>
                                                        <Link href="/admin/portals/create">Tambah Portal</Link>
                                                    </Button>
                                                }
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    portals.data.map((portal) => (
                                        <tr key={portal.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-4 py-3">
                                                <div className="flex items-start gap-3">
                                                    <div className="size-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary shrink-0 mt-0.5">
                                                        <Globe className="size-4" />
                                                    </div>
                                                    <div>
                                                        <div className="font-semibold text-foreground flex items-center gap-2">
                                                            <span>{portal.name}</span>
                                                            <Badge variant="outline" className="text-[10px] py-0 px-1.5">
                                                                {portal.category}
                                                            </Badge>
                                                        </div>
                                                        <a
                                                            href={portal.url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-xs text-muted-foreground hover:text-primary flex items-center gap-1 mt-0.5"
                                                        >
                                                            <span className="truncate max-w-xs">{portal.url}</span>
                                                            <ExternalLink className="size-3" />
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        portal.auth_type === 'shared'
                                                            ? 'default'
                                                            : portal.auth_type === 'personal'
                                                              ? 'secondary'
                                                              : 'outline'
                                                    }
                                                    className="capitalize text-xs"
                                                >
                                                    {portal.auth_type === 'both' ? 'Hybrid' : portal.auth_type}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {portal.shared_username ? (
                                                    <div className="flex items-center gap-1.5 font-mono text-muted-foreground">
                                                        <KeyRound className="size-3 text-amber-500" />
                                                        <span>{portal.shared_username}</span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground italic">Personal saja</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Link
                                                    href={`/admin/portals/mapping?portal_id=${portal.id}`}
                                                    className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary font-medium hover:bg-primary/20"
                                                >
                                                    <Users className="size-3" />
                                                    <span>{portal.user_credentials_count}</span>
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-center font-mono text-xs text-muted-foreground">
                                                {portal.sort_order}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleActive(portal)}
                                                    className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors ${
                                                        portal.is_active
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {portal.is_active ? '● Aktif' : '○ Nonaktif'}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button asChild variant="ghost" size="icon" className="size-8">
                                                        <Link href={`/admin/portals/${portal.id}/edit`}>
                                                            <Pencil className="size-3.5" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(portal)}
                                                        className="size-8 text-destructive hover:bg-destructive/10"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {portals.last_page > 1 && <DataTablePagination links={portals.links} />}
                </div>
            </div>

            <ConfirmDialog
                open={portalToDelete !== null}
                onOpenChange={(open) => !open && setPortalToDelete(null)}
                title="Hapus Master Portal"
                description={`Apakah Anda yakin ingin menghapus portal "${portalToDelete?.name}"? Seluruh mapping hak akses petugas ke portal ini juga akan dihapus.`}
                confirmLabel="Hapus Portal"
                cancelLabel="Batal"
                variant="destructive"
                onConfirm={handleConfirmDelete}
                loading={isDeleting}
            />
        </AppLayout>
    );
}
```

- [x] **Step 3: Create `create.tsx` and `edit.tsx`**

Buat file `resources/js/pages/admin/portals/create.tsx`:

```tsx
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { PortalForm } from './portal-form';

interface Props {
    categories: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
    { title: 'Tambah Portal', href: '/admin/portals/create' },
];

export default function AdminPortalsCreate({ categories }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Master Portal Eksternal" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Tambah Master Portal Eksternal"
                    description="Daftarkan platform website pelaporan baru, akun bersama RS, dan konfigurasi DOM selector login."
                />

                <PortalForm categories={categories} />
            </div>
        </AppLayout>
    );
}
```

Buat file `resources/js/pages/admin/portals/edit.tsx`:

```tsx
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Portal } from '@/types';
import { PortalForm } from './portal-form';

interface Props {
    portal: Portal & { has_shared_password?: boolean };
    categories: string[];
}

export default function AdminPortalsEdit({ portal, categories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Master Portal Eksternal', href: '/admin/portals' },
        { title: `Edit: ${portal.name}`, href: `/admin/portals/${portal.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Portal: ${portal.name}`} />

            <div className="flex flex-col gap-6">
                <Heading
                    title={`Edit Portal: ${portal.name}`}
                    description="Perbarui informasi target website, password bersama RS, atau selector form login."
                />

                <PortalForm initialData={portal} categories={categories} isEditing />
            </div>
        </AppLayout>
    );
}
```

- [x] **Step 4: Verify TypeScript Types for Portal CRUD Pages**

Run: `npm run types`  
Expected: 0 errors.

- [x] **Step 5: Commit**

```bash
git add resources/js/pages/admin/portals/portal-form.tsx resources/js/pages/admin/portals/index.tsx resources/js/pages/admin/portals/create.tsx resources/js/pages/admin/portals/edit.tsx
git commit -m "feat(portal): add master portal CRUD inertia views"
```

---

### Task 6: Access Mapping Matrix UI Page (`mapping.tsx`)

**Files:**
- Create: `resources/js/components/ui/switch.tsx`
- Create: `resources/js/components/portal/mapping-portal-view.tsx`
- Create: `resources/js/components/portal/mapping-user-view.tsx`
- Create: `resources/js/pages/admin/portals/mapping.tsx`

**Interfaces:**
- Consumes: `AppLayout`, `Portal`, `User`, `UserPortalCredential` types, Radix Tabs, Switch, Select.
- Produces: Dual-mode mapping matrix page dengan arsitektur **Hybrid** (Row-Level Instant Auto-Save + Toolbar Batch Sync):
  1. Mode Berdasarkan Portal (Row-level Switch instant save & toolbar quick batch sync).
  2. Mode Berdasarkan Petugas (User-centric portal access management with instant save).

- [x] **Step 1: Create `switch.tsx` & `mapping-portal-view.tsx`**

Buat file `resources/js/components/ui/switch.tsx`:

```tsx
import * as React from "react";
import * as SwitchPrimitive from "@radix-ui/react-switch";
import { cn } from "@/lib/utils";

function Switch({
  className,
  ...props
}: React.ComponentProps<typeof SwitchPrimitive.Root>) {
  return (
    <SwitchPrimitive.Root
      data-slot="switch"
      className={cn(
        "peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input",
        className
      )}
      {...props}
    >
      <SwitchPrimitive.Thumb
        data-slot="switch-thumb"
        className={cn(
          "pointer-events-none block size-4 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0"
        )}
      />
    </SwitchPrimitive.Root>
  );
}

export { Switch };
```

Buat file `resources/js/components/portal/mapping-portal-view.tsx`:

```tsx
import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    CheckSquare,
    Loader2,
    Search,
    Users,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';
import { DataTablePagination } from '@/components/data-table-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    CredentialType,
    Portal,
    PortalMappingSummary,
    UserPortalCredential,
} from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    simrs_nik: string | null;
    role: string;
    dep_id: string | null;
}

type PortalItem = Portal | PortalMappingSummary;

interface MappingPortalViewProps {
    portals: PortalItem[];
    selectedPortal: PortalItem;
    users: {
        data: UserItem[];
        links?: { url: string | null; label: string; active: boolean }[];
        current_page?: number;
        last_page?: number;
        total?: number;
    };
    portalCredentials: Record<string | number, UserPortalCredential>;
    departments: string[];
    filters: {
        portal_id: number;
        search: string;
        department: string;
        role: string;
    };
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function MappingPortalView({
    portals,
    selectedPortal,
    users,
    portalCredentials,
    departments,
    filters,
}: MappingPortalViewProps) {
    const [assignments, setAssignments] = useState<
        Record<number, { has_access: boolean; credential_type: CredentialType; notes: string }>
    >(() => {
        const initial: Record<
            number,
            { has_access: boolean; credential_type: CredentialType; notes: string }
        > = {};
        users.data.forEach((u) => {
            const cred = portalCredentials[u.id];
            initial[u.id] = {
                has_access: !!cred && cred.is_active,
                credential_type: (cred?.credential_type || 'use_shared') as CredentialType,
                notes: cred?.notes || '',
            };
        });
        return initial;
    });

    const [rowStatus, setRowStatus] = useState<Record<number, 'idle' | 'saving' | 'saved' | 'error'>>({});
    const [isBatchSaving, setIsBatchSaving] = useState(false);

    const handleSelectPortal = (portalId: string) => {
        router.get(
            '/admin/portals/mapping',
            { ...filters, portal_id: portalId, view_mode: 'portal' },
            { preserveState: true }
        );
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/admin/portals/mapping',
            { ...filters, [key]: value === '_all' ? '' : value, view_mode: 'portal' },
            { preserveState: true }
        );
    };

    const autoSaveRow = async (
        userId: number,
        hasAccess: boolean,
        credType: CredentialType,
        notes?: string,
        explicitNotesUpdate: boolean = false,
    ) => {
        setRowStatus((prev) => ({ ...prev, [userId]: 'saving' }));
        try {
            const csrfToken = getCsrfToken();
            const payload: Record<string, unknown> = {
                portal_id: selectedPortal.id,
                user_id: userId,
                has_access: hasAccess,
                credential_type: credType,
            };

            if (explicitNotesUpdate) {
                payload.notes = notes && notes.trim() !== '' ? notes.trim() : null;
            }

            const response = await fetch('/admin/portals/mapping/save-row', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) throw new Error('Gagal menyimpan perubahan');

            setRowStatus((prev) => ({ ...prev, [userId]: 'saved' }));
            setTimeout(() => {
                setRowStatus((prev) => ({ ...prev, [userId]: 'idle' }));
            }, 1500);
        } catch {
            setRowStatus((prev) => ({ ...prev, [userId]: 'error' }));
        }
    };

    const handleToggleUser = (userId: number, checked: boolean) => {
        const current = assignments[userId] || {
            has_access: false,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            has_access: checked,
        };

        setAssignments((prev) => ({ ...prev, [userId]: updated }));
        autoSaveRow(
            userId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleCredentialTypeChange = (
        userId: number,
        type: CredentialType,
    ) => {
        const current = assignments[userId] || {
            has_access: true,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            credential_type: type,
        };

        setAssignments((prev) => ({ ...prev, [userId]: updated }));
        autoSaveRow(
            userId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleNotesBlur = (userId: number, notes: string) => {
        const current = assignments[userId];
        if (!current) return;
        autoSaveRow(
            userId,
            current.has_access,
            current.credential_type,
            notes,
            true,
        );
    };

    const handleNotesChange = (userId: number, notes: string) => {
        setAssignments((prev) => ({
            ...prev,
            [userId]: {
                ...prev[userId],
                notes,
            },
        }));
    };

    const handleBulkSetAccess = (hasAccess: boolean, defaultType: CredentialType = 'use_shared') => {
        setIsBatchSaving(true);
        const payload = {
            portal_id: selectedPortal.id,
            assignments: users.data.map((u) => ({
                user_id: u.id,
                has_access: hasAccess,
                credential_type: defaultType,
                notes: assignments[u.id]?.notes || null,
            })),
        };

        router.post('/admin/portals/mapping/sync-portal', payload, {
            onFinish: () => setIsBatchSaving(false),
            onSuccess: () => {
                setAssignments((prev) => {
                    const next = { ...prev };
                    users.data.forEach((u) => {
                        next[u.id] = {
                            ...next[u.id],
                            has_access: hasAccess,
                            credential_type: defaultType,
                        };
                    });
                    return next;
                });
            },
        });
    };

    const supportsPersonal = selectedPortal.auth_type === 'personal' || selectedPortal.auth_type === 'both';
    const supportsShared = selectedPortal.auth_type === 'shared' || selectedPortal.auth_type === 'both';

    return (
        <div className="space-y-5">
            {/* Portal Selection & Filters */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-xl border border-border bg-card">
                <div>
                    <label className="text-xs font-semibold text-muted-foreground">Pilih Portal Target</label>
                    <Select
                        value={selectedPortal.id.toString()}
                        onValueChange={handleSelectPortal}
                    >
                        <SelectTrigger className="mt-1">
                            <SelectValue placeholder="Pilih Portal" />
                        </SelectTrigger>
                        <SelectContent>
                            {portals.map((p) => (
                                <SelectItem key={p.id} value={p.id.toString()}>
                                    {p.name} ({p.category})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <label className="text-xs font-semibold text-muted-foreground">Filter Departemen</label>
                    <Select
                        value={filters.department || '_all'}
                        onValueChange={(v) => handleFilterChange('department', v)}
                    >
                        <SelectTrigger className="mt-1">
                            <SelectValue placeholder="Semua Departemen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Departemen</SelectItem>
                            {departments.map((dep) => (
                                <SelectItem key={dep} value={dep}>
                                    {dep}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="md:col-span-2">
                    <label className="text-xs font-semibold text-muted-foreground">Cari Nama / Email / NIK</label>
                    <div className="relative mt-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input
                            placeholder="Ketik nama atau NIK petugas..."
                            value={filters.search}
                            onChange={(e) => handleFilterChange('search', e.target.value)}
                            className="pl-9"
                        />
                    </div>
                </div>
            </div>

            {/* Bulk Toolbar */}
            <div className="flex flex-wrap items-center justify-between gap-3 p-3 bg-muted/30 border border-border rounded-xl">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs font-medium text-muted-foreground mr-1">Aksi Cepat Massal:</span>
                    {supportsShared && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isBatchSaving}
                            onClick={() => handleBulkSetAccess(true, 'use_shared')}
                            className="text-xs gap-1.5 h-8"
                        >
                            <CheckSquare className="size-3.5 text-primary" /> Izinkan Semua (Akun Bersama)
                        </Button>
                    )}
                    {supportsPersonal && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={isBatchSaving}
                            onClick={() => handleBulkSetAccess(true, 'personal')}
                            className="text-xs gap-1.5 h-8"
                        >
                            <Users className="size-3.5 text-indigo-500" /> Izinkan Semua (Akun Personal)
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={isBatchSaving}
                        onClick={() => handleBulkSetAccess(false)}
                        className="text-xs gap-1.5 h-8 text-destructive hover:bg-destructive/10"
                    >
                        <XCircle className="size-3.5" /> Cabut Semua
                    </Button>
                </div>

                <div className="text-xs text-muted-foreground flex items-center gap-1.5">
                    <CheckCircle2 className="size-3.5 text-emerald-500" />
                    <span>Perubahan baris otomatis tersimpan</span>
                </div>
            </div>

            {/* Matrix Table */}
            <div className="rounded-xl border border-border bg-card overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                        <tr>
                            <th className="px-4 py-3 w-16 text-center">Akses</th>
                            <th className="px-4 py-3">Nama Petugas & NIK</th>
                            <th className="px-4 py-3">Role & Dept</th>
                            <th className="px-4 py-3">Tipe Kredensial</th>
                            <th className="px-4 py-3">Catatan</th>
                            <th className="px-4 py-3 w-28 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {users.data.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground text-xs">
                                    Tidak ada data petugas yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        ) : (
                            users.data.map((user) => {
                                const current = assignments[user.id] || {
                                    has_access: false,
                                    credential_type: 'use_shared',
                                    notes: '',
                                };

                                const status = rowStatus[user.id] || 'idle';

                                return (
                                    <tr
                                        key={user.id}
                                        className={`transition-colors ${
                                            current.has_access ? 'bg-primary/5 hover:bg-primary/10' : 'hover:bg-muted/20'
                                        }`}
                                    >
                                        <td className="px-4 py-3 text-center">
                                            <Switch
                                                checked={current.has_access}
                                                onCheckedChange={(checked) => handleToggleUser(user.id, checked)}
                                                disabled={status === 'saving'}
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-foreground">{user.name}</div>
                                            <div className="text-xs text-muted-foreground font-mono">
                                                {user.simrs_nik ? `NIK: ${user.simrs_nik}` : user.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <div className="flex items-center gap-1.5">
                                                <Badge variant="outline" className="text-[10px] capitalize">
                                                    {user.role}
                                                </Badge>
                                                <span className="text-muted-foreground">{user.dep_id ?? '–'}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Select
                                                value={current.credential_type}
                                                onValueChange={(val) =>
                                                    handleCredentialTypeChange(user.id, val as CredentialType)
                                                }
                                                disabled={!current.has_access || status === 'saving'}
                                            >
                                                <SelectTrigger className="h-8 text-xs w-[180px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        value="use_shared"
                                                        disabled={!supportsShared}
                                                    >
                                                        Akun Bersama RS
                                                    </SelectItem>
                                                    <SelectItem
                                                        value="personal"
                                                        disabled={!supportsPersonal}
                                                    >
                                                        Akun Personal Staf
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Input
                                                value={current.notes}
                                                onChange={(e) => handleNotesChange(user.id, e.target.value)}
                                                onBlur={(e) => handleNotesBlur(user.id, e.target.value)}
                                                placeholder="Catatan..."
                                                disabled={!current.has_access || status === 'saving'}
                                                className="h-8 text-xs"
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {status === 'saving' && (
                                                <span className="text-[11px] text-muted-foreground inline-flex items-center gap-1">
                                                    <Loader2 className="size-3 animate-spin" /> Menyimpan...
                                                </span>
                                            )}
                                            {status === 'saved' && (
                                                <span className="text-[11px] text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-1 font-medium">
                                                    <Check className="size-3" /> Tersimpan
                                                </span>
                                            )}
                                            {status === 'error' && (
                                                <span className="text-[11px] text-destructive inline-flex items-center gap-1 font-medium">
                                                    <AlertCircle className="size-3" /> Gagal
                                                </span>
                                            )}
                                            {status === 'idle' && (
                                                <span className="text-[11px] text-muted-foreground/60">
                                                    {current.has_access ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            {users.links && users.links.length > 3 && (
                <div className="rounded-xl border border-border overflow-hidden">
                    <DataTablePagination links={users.links} />
                </div>
            )}
        </div>
    );
}
```

- [x] **Step 2: Create `mapping-user-view.tsx`**

Buat file `resources/js/components/portal/mapping-user-view.tsx`:

```tsx
import { router } from '@inertiajs/react';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    CheckSquare,
    Globe,
    Loader2,
    UserCheck,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    CredentialType,
    Portal,
    PortalMappingSummary,
    UserPortalCredential,
} from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    simrs_nik: string | null;
    role: string;
    dep_id: string | null;
}

type PortalItem = Portal | PortalMappingSummary;

interface MappingUserViewProps {
    portals: PortalItem[];
    selectedUser: UserItem | null;
    users: {
        data: UserItem[];
    };
    allUsers?: UserItem[];
    userCredentials: Record<string | number, UserPortalCredential>;
}

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector(
        'meta[name="csrf-token"]',
    ) as HTMLMetaElement | null;
    if (meta?.content) return meta.content;
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export function MappingUserView({
    portals,
    selectedUser,
    users,
    allUsers,
    userCredentials,
}: MappingUserViewProps) {
    const availableUsers = allUsers && allUsers.length > 0 ? allUsers : users.data;
    const [userSearchTerm, setUserSearchTerm] = useState('');

    const filteredUsers = React.useMemo(() => {
        if (!userSearchTerm.trim()) return availableUsers;
        const q = userSearchTerm.toLowerCase();
        return availableUsers.filter(
            (u) =>
                u.name.toLowerCase().includes(q) ||
                (u.simrs_nik && u.simrs_nik.toLowerCase().includes(q)) ||
                u.email.toLowerCase().includes(q) ||
                (u.dep_id && u.dep_id.toLowerCase().includes(q)),
        );
    }, [availableUsers, userSearchTerm]);
    const [assignments, setAssignments] = useState<
        Record<
            number,
            {
                has_access: boolean;
                credential_type: CredentialType;
                notes: string;
            }
        >
    >(() => {
        const initial: Record<
            number,
            {
                has_access: boolean;
                credential_type: CredentialType;
                notes: string;
            }
        > = {};
        portals.forEach((p) => {
            const cred = userCredentials[p.id];
            initial[p.id] = {
                has_access: !!cred && cred.is_active,
                credential_type: (cred?.credential_type ||
                    'use_shared') as CredentialType,
                notes: cred?.notes || '',
            };
        });
        return initial;
    });

    const [rowStatus, setRowStatus] = useState<
        Record<number, 'idle' | 'saving' | 'saved' | 'error'>
    >({});
    const [isBatchSaving, setIsBatchSaving] = useState(false);

    const handleSelectUser = (userId: string) => {
        router.get(
            '/admin/portals/mapping',
            { user_id: userId, view_mode: 'user' },
            { preserveState: true },
        );
    };

    const autoSaveRow = async (
        portalId: number,
        hasAccess: boolean,
        credType: CredentialType,
        notes?: string,
        explicitNotesUpdate: boolean = false,
    ) => {
        if (!selectedUser) return;
        setRowStatus((prev) => ({ ...prev, [portalId]: 'saving' }));
        try {
            const csrfToken = getCsrfToken();
            const payload: Record<string, unknown> = {
                portal_id: portalId,
                user_id: selectedUser.id,
                has_access: hasAccess,
                credential_type: credType,
            };

            if (explicitNotesUpdate) {
                payload.notes = notes && notes.trim() !== '' ? notes.trim() : null;
            }

            const response = await fetch('/admin/portals/mapping/save-row', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) throw new Error('Gagal menyimpan perubahan');

            setRowStatus((prev) => ({ ...prev, [portalId]: 'saved' }));
            setTimeout(() => {
                setRowStatus((prev) => ({ ...prev, [portalId]: 'idle' }));
            }, 1500);
        } catch {
            setRowStatus((prev) => ({ ...prev, [portalId]: 'error' }));
        }
    };

    const handleTogglePortal = (portalId: number, checked: boolean) => {
        const current = assignments[portalId] || {
            has_access: false,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            has_access: checked,
        };

        setAssignments((prev) => ({ ...prev, [portalId]: updated }));
        autoSaveRow(
            portalId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleCredentialTypeChange = (
        portalId: number,
        type: CredentialType,
    ) => {
        const current = assignments[portalId] || {
            has_access: true,
            credential_type: 'use_shared',
            notes: '',
        };

        const updated = {
            ...current,
            credential_type: type,
        };

        setAssignments((prev) => ({ ...prev, [portalId]: updated }));
        autoSaveRow(
            portalId,
            updated.has_access,
            updated.credential_type,
            updated.notes,
            false,
        );
    };

    const handleNotesBlur = (portalId: number, notes: string) => {
        const current = assignments[portalId];
        if (!current) return;
        autoSaveRow(
            portalId,
            current.has_access,
            current.credential_type,
            notes,
            true,
        );
    };

    const handleNotesChange = (portalId: number, notes: string) => {
        setAssignments((prev) => ({
            ...prev,
            [portalId]: {
                ...(prev[portalId] || {
                    has_access: false,
                    credential_type: 'use_shared',
                    notes: '',
                }),
                notes,
            },
        }));
    };

    const handleBulkSetAccess = (hasAccess: boolean) => {
        if (!selectedUser) return;
        setIsBatchSaving(true);

        const payload = {
            user_id: selectedUser.id,
            assignments: portals.map((p) => ({
                portal_id: p.id,
                has_access: hasAccess,
                credential_type:
                    assignments[p.id]?.credential_type || 'use_shared',
                notes: assignments[p.id]?.notes || null,
            })),
        };

        router.post('/admin/portals/mapping/sync-user', payload, {
            preserveScroll: true,
            onFinish: () => setIsBatchSaving(false),
            onSuccess: () => {
                setAssignments((prev) => {
                    const next = { ...prev };
                    portals.forEach((p) => {
                        next[p.id] = {
                            ...(next[p.id] || {
                                notes: '',
                                credential_type: 'use_shared',
                            }),
                            has_access: hasAccess,
                        };
                    });
                    return next;
                });
            },
        });
    };

    return (
        <div className="space-y-5">
            {/* User Selector Header */}
            <div className="flex flex-col justify-between gap-4 rounded-xl border border-border bg-card p-4 md:flex-row md:items-center">
                <div className="w-full md:w-96">
                    <label className="text-xs font-semibold text-muted-foreground">
                        Pilih Petugas Rumah Sakit
                    </label>
                    <div className="mt-1 space-y-1.5">
                        <Input
                            placeholder="Cari nama / NIK / unit..."
                            value={userSearchTerm}
                            onChange={(e) => setUserSearchTerm(e.target.value)}
                            className="h-8 text-xs"
                            aria-label="Filter Petugas"
                        />
                        <Select
                            value={selectedUser?.id?.toString() ?? ''}
                            onValueChange={handleSelectUser}
                        >
                            <SelectTrigger
                                className="h-9"
                                aria-label="Pilih Petugas Rumah Sakit"
                            >
                                <SelectValue placeholder="-- Pilih Petugas --" />
                            </SelectTrigger>
                            <SelectContent className="max-h-72">
                                {filteredUsers.length === 0 ? (
                                    <div className="p-2 text-center text-xs text-muted-foreground">
                                        Tidak ada petugas ditemukan
                                    </div>
                                ) : (
                                    filteredUsers.map((u) => (
                                        <SelectItem key={u.id} value={u.id.toString()}>
                                            {u.name} (
                                            {u.simrs_nik
                                                ? `NIK: ${u.simrs_nik}`
                                                : u.email}
                                            {u.dep_id ? ` • ${u.dep_id}` : ''}
                                            )
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {selectedUser && (
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <UserCheck className="size-5" />
                        </div>
                        <div>
                            <div className="text-sm font-semibold text-foreground">
                                {selectedUser.name}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Role:{' '}
                                <span className="capitalize">
                                    {selectedUser.role}
                                </span>{' '}
                                | Dept: {selectedUser.dep_id ?? '–'}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {selectedUser ? (
                <div className="space-y-4">
                    {/* Quick Action Toolbar */}
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/30 p-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="mr-1 text-xs font-medium text-muted-foreground">
                                Aksi Cepat:
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isBatchSaving}
                                onClick={() => handleBulkSetAccess(true)}
                                className="h-8 gap-1.5 text-xs"
                                aria-label="Izinkan Semua Portal"
                            >
                                <CheckSquare className="size-3.5 text-primary" />{' '}
                                Izinkan Semua Portal
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isBatchSaving}
                                onClick={() => handleBulkSetAccess(false)}
                                className="h-8 gap-1.5 text-xs text-destructive hover:bg-destructive/10"
                                aria-label="Cabut Semua Portal"
                            >
                                <XCircle className="size-3.5" /> Cabut Semua
                                Portal
                            </Button>
                        </div>

                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <CheckCircle2 className="size-3.5 text-emerald-500" />
                            <span>Perubahan baris otomatis tersimpan</span>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-border bg-muted/50 text-xs font-semibold uppercase text-muted-foreground">
                                <tr>
                                    <th className="w-16 px-4 py-3 text-center">
                                        Akses
                                    </th>
                                    <th className="px-4 py-3">Portal Target</th>
                                    <th className="px-4 py-3">Kategori</th>
                                    <th className="px-4 py-3">
                                        Tipe Kredensial
                                    </th>
                                    <th className="px-4 py-3">Catatan Akses</th>
                                    <th className="w-28 px-4 py-3 text-center">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {portals.map((portal) => {
                                    const current = assignments[portal.id] || {
                                        has_access: false,
                                        credential_type: 'use_shared',
                                        notes: '',
                                    };

                                    const supportsPersonal =
                                        portal.auth_type === 'personal' ||
                                        portal.auth_type === 'both';
                                    const supportsShared =
                                        portal.auth_type === 'shared' ||
                                        portal.auth_type === 'both';

                                    const status =
                                        rowStatus[portal.id] || 'idle';

                                    return (
                                        <tr
                                            key={portal.id}
                                            className={`transition-colors ${
                                                current.has_access
                                                    ? 'bg-primary/5 hover:bg-primary/10'
                                                    : 'hover:bg-muted/20'
                                            }`}
                                        >
                                            <td className="px-4 py-3 text-center">
                                                <Switch
                                                    checked={current.has_access}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        handleTogglePortal(
                                                            portal.id,
                                                            checked,
                                                        )
                                                    }
                                                    aria-label={`Akses portal ${portal.name}`}
                                                    disabled={status === 'saving'}
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2 font-medium text-foreground">
                                                    <Globe className="size-4 text-primary" />
                                                    <span>{portal.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {portal.category}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Select
                                                    value={
                                                        current.credential_type
                                                    }
                                                    onValueChange={(val) =>
                                                        handleCredentialTypeChange(
                                                            portal.id,
                                                            val as CredentialType,
                                                        )
                                                    }
                                                    disabled={
                                                        !current.has_access || status === 'saving'
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className="h-8 w-[180px] text-xs"
                                                        aria-label={`Tipe kredensial ${portal.name}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            value="use_shared"
                                                            disabled={
                                                                !supportsShared
                                                            }
                                                        >
                                                            Akun Bersama RS
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="personal"
                                                            disabled={
                                                                !supportsPersonal
                                                            }
                                                        >
                                                            Akun Pribadi Petugas
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    value={current.notes}
                                                    onChange={(e) =>
                                                        handleNotesChange(
                                                            portal.id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    onBlur={(e) =>
                                                        handleNotesBlur(
                                                            portal.id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Catatan..."
                                                    aria-label={`Catatan akses ${portal.name}`}
                                                    disabled={
                                                        !current.has_access || status === 'saving'
                                                    }
                                                    className="h-8 text-xs"
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {status === 'saving' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                                        <Loader2 className="size-3 animate-spin" />{' '}
                                                        Menyimpan...
                                                    </span>
                                                )}
                                                {status === 'saved' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                                        <Check className="size-3" />{' '}
                                                        Tersimpan
                                                    </span>
                                                )}
                                                {status === 'error' && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] font-medium text-destructive">
                                                        <AlertCircle className="size-3" />{' '}
                                                        Gagal
                                                    </span>
                                                )}
                                                {status === 'idle' && (
                                                    <span className="text-[11px] text-muted-foreground/60">
                                                        {current.has_access
                                                            ? 'Aktif'
                                                            : 'Nonaktif'}
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : (
                <div className="rounded-xl border border-dashed border-border p-12 text-center text-sm text-muted-foreground">
                    Pilih salah satu petugas pada menu di atas untuk menampilkan
                    daftar izin portal.
                </div>
            )}
        </div>
    );
}
```

- [x] **Step 3: Create `mapping.tsx`**

Buat file `resources/js/pages/admin/portals/mapping.tsx`:

```tsx
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Globe, ShieldCheck, Users } from 'lucide-react';
import React from 'react';
import { MappingPortalView } from '@/components/portal/mapping-portal-view';
import { MappingUserView } from '@/components/portal/mapping-user-view';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    Portal,
    PortalMappingSummary,
    UserPortalCredential,
} from '@/types';

interface UserItem {
    id: number;
    name: string;
    email: string;
    simrs_nik: string | null;
    role: string;
    dep_id: string | null;
}

type PortalItem = Portal | PortalMappingSummary;

interface Props {
    view_mode: 'portal' | 'user';
    portals: PortalItem[];
    selected_portal: PortalItem | null;
    selected_user: UserItem | null;
    users: {
        data: UserItem[];
        links?: { url: string | null; label: string; active: boolean }[];
        current_page?: number;
        last_page?: number;
        total?: number;
    };
    portal_credentials: Record<string | number, UserPortalCredential>;
    user_credentials: Record<string | number, UserPortalCredential>;
    departments: string[];
    all_users?: UserItem[];
    filters: {
        portal_id: number;
        user_id: number;
        search: string;
        department: string;
        role: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
    { title: 'Mapping Akses Petugas', href: '/admin/portals/mapping' },
];

export default function AdminPortalsMapping({
    view_mode,
    portals,
    selected_portal,
    selected_user,
    users,
    portal_credentials,
    user_credentials,
    departments,
    all_users,
    filters,
}: Props) {
    const handleTabChange = (mode: string) => {
        router.get(
            '/admin/portals/mapping',
            { ...filters, view_mode: mode },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mapping Akses Portal Pelaporan" />

            <div className="flex flex-col gap-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-bold tracking-tight text-foreground">
                            <ShieldCheck className="size-6 text-primary" />
                            Mapping Hak Akses Portal Pelaporan
                        </h1>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Atur otorisasi petugas rumah sakit ke portal
                            eksternal dan tentukan penggunaan akun bersama atau
                            akun personal.
                        </p>
                    </div>

                    <Button asChild variant="outline" className="gap-2">
                        <Link href="/admin/portals">
                            <ArrowLeft className="size-4" /> Kembali ke Master
                            Portal
                        </Link>
                    </Button>
                </div>

                <Tabs value={view_mode} onValueChange={handleTabChange}>
                    <TabsList className="h-10">
                        <TabsTrigger value="portal" className="gap-2 text-xs">
                            <Globe className="size-4" /> Matriks Berdasarkan
                            Portal
                        </TabsTrigger>
                        <TabsTrigger value="user" className="gap-2 text-xs">
                            <Users className="size-4" /> Matriks Berdasarkan
                            Petugas
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="portal" className="mt-4">
                        {selected_portal ? (
                            <MappingPortalView
                                key={`portal-${selected_portal.id}-${filters.department || ''}-${filters.search || ''}`}
                                portals={portals}
                                selectedPortal={selected_portal}
                                users={users}
                                portalCredentials={portal_credentials}
                                departments={departments}
                                filters={filters}
                            />
                        ) : (
                            <div className="p-8 text-center text-muted-foreground">
                                Belum ada master portal yang aktif. Silakan
                                tambahkan portal terlebih dahulu.
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="user" className="mt-4">
                        <MappingUserView
                            key={`user-${selected_user?.id ?? 'none'}`}
                            portals={portals}
                            selectedUser={selected_user}
                            users={users}
                            allUsers={all_users || users.data}
                            userCredentials={user_credentials}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
```

- [x] **Step 4: Run type check to verify zero TypeScript errors**

Run: `npm run types`  
Expected: 0 errors.

- [x] **Step 5: Commit**

```bash
git add resources/js/components/portal/mapping-portal-view.tsx resources/js/components/portal/mapping-user-view.tsx resources/js/pages/admin/portals/mapping.tsx
git commit -m "feat(portal): add access mapping matrix UI views"
```

---

### Task 7: End-to-End Integration, Type Checking & Build Verification

**Files:**
- Test: `tests/Feature/PortalPelaporan/AdminPortalControllerTest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalMappingControllerTest.php`
- Test: `tests/Feature/PortalPelaporan/PortalInertiaPropsTest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalMappingServiceTest.php`
- Test: `tests/Feature/PortalPelaporan/AdminPortalServiceTest.php`

**Interfaces:**
- Consumes: Seluruh modul Plan 1 dan Plan 2 beserta pengerasan hasil Code Review.
- Produces: Seluruh test Pest hijau (100% passing: 60 test, 307 assertions), clean TypeScript check (`tsc --noEmit`), dan clean Vite asset bundle build.

- [x] **Step 1: Run complete Pest test suite for PortalPelaporan**

Run: `php artisan test tests/Feature/PortalPelaporan`  
Expected: PASS untuk seluruh pengujian (27 test dari Plan 1 + 33 test dari Plan 2 = 60 passing tests, 307 assertions).

- [x] **Step 2: Run TypeScript static verification**

Run: `npm run types`  
Expected: Zero type errors (`tsc --noEmit` exits with code 0).

- [x] **Step 3: Run Vite build**

Run: `npm run build`  
Expected: Production build completes successfully without missing imports or assets.

- [x] **Step 4: Commit**

```bash
git add docs/superpowers/plans/2026-09-10-portal-eksternal-plan-2-admin-modul.md
git commit -m "docs(portal): complete implementation plan 2 for admin module and mapping matrix"
```

---

### Code Review Hardening Summary & Architectural Improvements

Berdasarkan audit independen oleh Senior Code Reviewer, implementasi Plan 2 telah diperkuat dengan poin-poin arsitektural berikut:
1. **Explicit Note Clearing vs Preservation**:
   - `AdminPortalMappingService::saveSingleAssignment` menerima parameter `?bool $updateNotes = null`.
   - Toggle switch / perubahan dropdown tipe akun hanya mengirim `portal_id`, `user_id`, `has_access`, `credential_type` tanpa key `notes` (sehingga catatan existing dipertahankan).
   - Event blur pada input catatan mengirim `notes` secara eksplisit, memungkinkan pengosongan catatan di database ketika petugas menghapus teksnya.
2. **Hospital Staff Reachability (>50 Users)**:
   - `AdminPortalMappingService::getMappingData` menyertakan `all_users` (seluruh staf aktif unpaginated) untuk dropdown `MappingUserView`.
   - `mapping-user-view.tsx` dilengkapi live client-side search input `userSearchTerm` (filter nama, NIK, email, departemen) agar staf urutan >50 tetap dapat dipilih dan dikonfigurasi.
3. **DataTablePagination Reusability**:
   - `mapping-portal-view.tsx` menggunakan komponen `DataTablePagination` bawaan project dengan link Inertia untuk navigasi halaman user.
4. **Accessible Confirmation Dialog**:
   - Menghapus pemanggilan native blocking `window.confirm()` pada `index.tsx`, digantikan oleh komponen `ConfirmDialog` yang accessible dengan keyboard trap dan ARIA dialog.
5. **Defense-in-Depth Route Authorization**:
   - Menambahkan middleware perimeter `can:manage,App\Models\Portal` langsung pada group `Route::prefix('admin/portals')` di `routes/web.php` sebagai proteksi lapis luar, melengkapi otorisasi level controller `$this->authorize('manage', Portal::class)`.
