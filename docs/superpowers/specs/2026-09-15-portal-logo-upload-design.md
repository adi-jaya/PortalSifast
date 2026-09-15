# Desain Arsitektur: Penyimpanan & Pengunggahan Berkas Logo Portal Eksternal

**Tanggal Dibuat:** 15 September 2026  
**Status:** Disetujui (Approved)  
**Branch:** `feature/portal-pelaporan`  
**Konteks Modul:** Modul 13 - Portal Pelaporan Eksternal SIMRS  
**Dokumen Induk Terkait:**
* [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](2026-09-09-portal-eksternal-autofill-design.md)
* [`docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`](../../developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md)

---

## 1. Latar Belakang & Masalah
Pada implementasi awal modul Portal Pelaporan Eksternal:
* Kolom `icon_path` (`VARCHAR(255) NULLABLE`) pada tabel `portals` telah tersedia melalui migrasi `2026_09_09_100000_create_portals_table.php`.
* Di formulir admin (`resources/js/pages/admin/portals/portal-form.tsx`), input `icon_path` masih berupa elemen teks biasa (`<Input placeholder="Contoh: /images/portals/kemenkes.png">`).
* Validator `app/Http/Requests/Admin/PortalRequest.php` hanya memeriksa tipe data string.
* Belum tersedia mekanisme pengunggahan berkas, validasi MIME/ukuran gambar, penamaan terstruktur, pembersihan berkas lama (*lifecycle storage*), maupun accessor URL publik untuk dikonsumsi frontend dan ekstensi peramban.

Dokumen ini menetapkan spesifikasi arsitektur terpadu untuk pengunggahan, penyimpanan, dan penyajian logo portal secara aman, efisien, dan konsisten dengan konvensi proyek Portal Sifast.

---

## 2. Keputusan Arsitektur Utama (ADR)

1. **Skema Database Tanpa Migrasi Tambahan**:
   * Kolom `icon_path` tetap berukuran `VARCHAR(255) NULLABLE` dan menyimpan path relatif storage, contoh: `portals/sirika-bkkbn-8f2a1b9c.png`.
2. **Penyimpanan Berkas via Public Disk & Symlink**:
   * Berkas disimpan pada disk `public` (`storage/app/public/portals/`).
   * Mengandalkan symbolic link `public/storage -> storage/app/public` yang sudah aktif, sehingga berkas logo disajikan langsung oleh web server sebagai aset statis berkecepatan tinggi tanpa beban runtime PHP.
3. **Konvensi Penamaan Berkas**:
   * Format: `portals/{slug}-{hash8}.{ext}` (menggunakan slug portal dan 8 karakter acak `Str::random(8)`).
   * Menghindari benturan nama file (*collision*) dan memberikan *automatic cache-busting* pada browser saat logo diperbarui.
4. **Kontrak Data & Model Eloquent**:
   * Accessor `icon_url` didefinisikan pada model `App\Models\Portal` menggunakan `Storage::disk('public')->url($this->icon_path)`.
   * Properti `$appends = ['icon_url']` ditambahkan agar `icon_url` selalu disertakan secara otomatis dalam representasi JSON (Inertia props, API token dispatch, dan respons ekstensi browser).
5. **Strategi Pengiriman Berkas & Form Handling (Hybrid)**:
   * **Mode Tambah (`create`)**: Menggunakan Inertia `useForm` multipart form submit (`POST /admin/portals`).
   * **Mode Edit (`edit`)**: 
     - **Batch Submit**: Menggunakan method spoofing `_method: 'put'` ke `POST /admin/portals/{portal}` sehingga perubahan teks dan logo baru disimpan bersamaan saat tombol submit utama diklik.
     - **Dedicated Instant Action**: Menyediakan endpoint terdedikasi `POST /admin/portals/{portal}/logo` (unggah cepat) dan `DELETE /admin/portals/{portal}/logo` (hapus instan) khusus untuk entitas portal yang sudah tersimpan di database.
   * **Label Tombol Submit**: Ditetapkan seragam menggunakan kata **"Simpan"**.
6. **Siklus Hidup Berkas (Lifecycle Management)**:
   * **Ganti Logo**: Saat berkas logo baru diunggah, berkas fisik lama yang tersimpan di disk `public` otomatis dihapus sebelum berkas baru disimpan.
   * **Hapus Logo**: Tombol aksi hapus logo / flag `remove_logo: true` menghapus berkas fisik dari storage dan mengosongkan kolom `icon_path` (`null`).
   * **Hapus Portal**: Saat portal dihapus via `destroyPortal`, berkas fisik logo ikut dihapus dari storage untuk mencegah *orphan files*.

---

## 3. Detail Spesifikasi Teknis

### 3.1. Backend: Model Eloquent (`app/Models/Portal.php`)
* Tambahkan import:
  ```php
  use Illuminate\Database\Eloquent\Casts\Attribute;
  use Illuminate\Support\Facades\Storage;
  ```
* Tambahkan properti `$appends`:
  ```php
  protected $appends = [
      'icon_url',
  ];
  ```
* Tambahkan accessor:
  ```php
  protected function iconUrl(): Attribute
  {
      return Attribute::make(
          get: fn (): ?string => $this->icon_path
              ? Storage::disk('public')->url($this->icon_path)
              : null,
      );
  }
  ```

---

### 3.2. Backend: Request Validation

#### A. Pembaruan `app/Http/Requests/Admin/PortalRequest.php`
Gantikan aturan string `icon_path` dengan:
```php
'icon_file' => [
    'nullable',
    'file',
    'mimes:png,jpg,jpeg,webp,svg',
    'max:2048', // Maksimal 2 MB
],
'remove_logo' => [
    'nullable',
    'boolean',
],
```

#### B. Form Request Baru `app/Http/Requests/Admin/UploadPortalLogoRequest.php`
```php
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

---

### 3.3. Backend: Service Layer (`app/Services/Portal/AdminPortalService.php`)

Tambahkan method pengelolaan berkas logo:
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

public function updatePortalLogo(Portal $portal, UploadedFile $file): Portal
{
    $this->deleteIconFile($portal->icon_path);

    $extension = strtolower($file->getClientOriginalExtension());
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

private function deleteIconFile(?string $path): void
{
    if ($path && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
}
```

Integrasikan ke method utama:
* **`storePortal(array $data)`**:
  - Jika `$data['icon_file'] ?? null` berupa `UploadedFile`: simpan berkas dengan penamaan `portals/{slug}-{hash8}.{ext}`, set `$data['icon_path'] = $path`.
  - Hapus key `icon_file` dan `remove_logo` dari array sebelum `Portal::create($data)`.
* **`updatePortal(Portal $portal, array $data)`**:
  - Jika `$data['remove_logo'] ?? false` aktif: panggil `$this->removePortalLogo($portal)`.
  - Jika `$data['icon_file'] ?? null` berupa `UploadedFile`: panggil `$this->updatePortalLogo($portal, $file)`.
  - Hapus key `icon_file` dan `remove_logo` sebelum `$portal->update($data)`.
* **`destroyPortal(Portal $portal)`**:
  - Hapus berkas fisik `$portal->icon_path` via `$this->deleteIconFile($portal->icon_path)` sebelum `$portal->delete()`.
* **`paginatePortals()` & `formatPortalForEdit()`**:
  - Pastikan menyertakan `'icon_url' => $portal->icon_url`.

---

### 3.4. Backend: Controller & Rute

#### A. `app/Http/Controllers/Admin/AdminPortalController.php`
Tambahkan 2 action method:
```php
use App\Http\Requests\Admin\UploadPortalLogoRequest;

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
```

#### B. `routes/web.php` (Grup Admin Portals)
```php
Route::post('/{portal}/logo', [AdminPortalController::class, 'uploadLogo'])->name('logo.upload');
Route::delete('/{portal}/logo', [AdminPortalController::class, 'removeLogo'])->name('logo.remove');
```

---

### 3.5. Frontend: Tipe Data & UI

#### A. `resources/js/types/portal.ts`
Tambahkan properti `icon_url` pada interface `Portal`:
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

#### B. `resources/js/pages/admin/portals/portal-form.tsx`
* **Form State**:
  ```typescript
  icon_file: null as File | null,
  remove_logo: false,
  ```
* **Logo Uploader Box**:
  - Bingkai preview $80 \times 80$ px dengan `rounded-xl border bg-muted/20 flex items-center justify-center overflow-hidden`.
  - Merender gambar dari `previewUrl` (jika ada file baru dipilih) atau `initialData.icon_url` (jika mode edit dan tidak ditandai hapus).
  - Fallback placeholder menggunakan ikon `Globe` jika tidak ada logo.
* **Aksi Berkas**:
  - Tombol **"Pilih Logo"** yang memicu input berkas tersembunyi (`accept="image/png,image/jpeg,image/webp,image/svg+xml"`).
  - Teks bantuan: `"Format: PNG, JPG, WEBP, atau SVG (Maks. 2 MB). Disarankan rasio 1:1."`
  - Validasi error di bawah input jika ada error dari `errors.icon_file`.
* **Aksi Instan (Mode Edit)**:
  - Tombol **"Unggah Cepat"** untuk mengirim langsung berkas baru ke `POST /admin/portals/{id}/logo`.
  - Tombol **"Hapus Logo"** untuk menghapus berkas langsung via `DELETE /admin/portals/{id}/logo` atau menandai `remove_logo: true`.
* **Submit Form**:
  - Menggunakan method spoofing POST dengan `_method: 'put'` saat edit agar payload multipart berkas terbaca oleh PHP.
  - Teks tombol submit utama ditetapkan: **"Simpan"**.

#### C. `resources/js/pages/admin/portals/index.tsx`
Pada kolom "Portal Target":
* Gantikan ikon `Globe` statis dengan logo aktual:
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

---

## 4. Strategi Pengujian Otomatis (Test Suite)

Pengujian feature dan unit menggunakan Pest framework dengan isolasi storage (`Storage::fake('public')`):

| Berkas Uji | Deskripsi Kasus Uji | Ekspektasi |
|---|---|---|
| `AdminPortalControllerTest.php` | Store portal dengan `icon_file` valid | Berkas tersimpan di `portals/{slug}-*.ext`, `icon_path` tersimpan di DB, status 302 redirect |
| `AdminPortalControllerTest.php` | Store portal dengan berkas non-gambar / melebihi 2MB | Validasi gagal, session error pada `icon_file`, berkas tidak tersimpan |
| `AdminPortalControllerTest.php` | Update portal dengan logo baru | Berkas logo baru tersimpan, berkas lama terhapus dari storage, `icon_path` terbarui |
| `AdminPortalControllerTest.php` | Update portal dengan `remove_logo = true` | Berkas lama terhapus dari disk, `icon_path` menjadi `null` |
| `AdminPortalControllerTest.php` | Update portal tanpa menyertakan logo baru | Berkas lama tetap ada di disk, `icon_path` tidak berubah |
| `AdminPortalControllerTest.php` | Delete portal (`destroy`) | Berkas fisik logo terhapus dari storage saat entitas portal dihapus |
| `AdminPortalControllerTest.php` | Dedicated `POST /admin/portals/{portal}/logo` | Berkas terunggah langsung, berkas lama terhapus, redirect back dengan pesan sukses |
| `AdminPortalControllerTest.php` | Dedicated `DELETE /admin/portals/{portal}/logo` | Berkas terhapus langsung, `icon_path` menjadi null, redirect back dengan pesan sukses |
| `AdminPortalControllerTest.php` | Otorisasi rute logo oleh staf non-admin | Akses ditolak dengan kode status 403 Forbidden |
| `AdminPortalServiceTest.php` | Pengujian unit `updatePortalLogo()` dan `removePortalLogo()` | Operasi filesystem dan pembaruan database tereksekusi sesuai kontrak |
| `AdminPortalServiceTest.php` | Pengujian unit `destroyPortal()` | File fisik dibersihkan dari disk public saat portal dihapus |
| `PortalModelTest.php` | Accessor `icon_url` dan serialization | Mengembalikan URL valid saat `icon_path` ada, `null` saat kosong, ter-serialize di `toArray()` |
| Regression Test | Menjalankan seluruh pengujian modul (64 tests) | 100% lulus tanpa kegagalan regresi |

---

## 5. Rencana Transisi ke Implementasi
Setelah spesifikasi ini disetujui pengguna, langkah selanjutnya adalah menjalankan skill `writing-plans` untuk menghasilkan file implementation plan bertahap (`docs/superpowers/plans/2026-09-15-portal-logo-upload-plan.md`).
