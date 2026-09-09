# Spesifikasi Desain: Portal Pelaporan Eksternal SIMRS Sifast & Custom Browser Extension Autofill

**Tanggal:** 2026-09-09  
**Status:** Approved  
**Tipe Proyek:** Architectural Subsystem  
**Target Platform:** SIMRS Sifast (Laravel 12, Inertia.js, React 19, TypeScript, Tailwind CSS v4) & Chromium-based Browsers (Manifest V3)

---

## 1. Latar Belakang & Tujuan

Staf rumah sakit secara rutin melakukan pelaporan data ke berbagai platform eksternal resmi pemerintah (Kementerian Kesehatan RI dan BKKBN). Setiap platform memiliki alamat URL dan akun login terpisah. Sering kali terjadi kendala seperti petugas lupa kredensial, akun berpindah tangan tanpa terkontrol, atau petugas harus memasukkan kredensial berulang kali.

### Tujuan Utama:
1. **Pusat Agregator Portal:** Menyediakan satu halaman agregator portal pelaporan di SIMRS Sifast sebagai titik akses terpusat ke semua website eksternal.
2. **Autofill Otomatis & Aman via Browser Extension (Manifest V3):** Mengisi otomatis bidang username dan password pada website eksternal saat staf mengklik portal dari SIMRS, dengan CAPTCHA tetap diselesaikan secara manual oleh pengguna.
3. **Fleksibilitas Kredensial (Hybrid Model):** Mendukung akun bersama rumah sakit (*shared institutional account*) maupun akun pribadi staf (*personal account*).
4. **Resilient Selector & Heuristic Scanner:** Mampu menangani website dengan struktur form yang bervariasi (termasuk SPA seperti React/Vue, form tanpa atribut `id`/`name`, dan perubahan DOM di kemudian hari).
5. **Zero-Persistence Security:** Ekstensi tidak menyimpan username/password di storage lokal browser; kredensial hanya dikirim satu kali (*one-time transfer*) dan langsung dihapus dari memori RAM setelah form terisi.

---

## 2. Model Data & Database Schema

Pengecekan hak akses manajemen portal dilakukan secara terprogram (Policy/Gate berbasis `role === 'admin'` atau `isSuperAdmin()`), sehingga **tabel `users` bawaan tidak diubah sama sekali**.

### 2.1. Tabel `portals` (Master Website Eksternal)
Menyimpan daftar website eksternal, konfigurasi form login, dan kredensial bersama tingkat RS.

| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | `bigint unsigned PK` | Auto-increment ID |
| `name` | `varchar(150)` | Nama resmi portal (contoh: "SIRS Online", "SITB", "MPDN") |
| `slug` | `varchar(150) UNIQUE` | URL-friendly slug |
| `category` | `varchar(100)` | Kategori (contoh: "Kemenkes", "BKKBN", "Mutu & Akreditasi") |
| `url` | `text` | URL landing / form login website target |
| `url_pattern` | `varchar(255) NULL` | Regex / wildcard pattern untuk verifikasi URL tab eksternal |
| `icon_path` | `varchar(255) NULL` | Path file logo/ikon portal di storage |
| `description` | `text NULL` | Deskripsi singkat fungsi pelaporan portal |
| `auth_type` | `enum('shared', 'personal', 'both')` | Kebijakan tipe akun login |
| `shared_username` | `varchar(255) NULL` | Username akun bersama RS |
| `shared_password` | `text NULL` | Password terenkripsi (`Crypt::encryptString`) |
| `shared_extra_fields` | `json NULL` | Field tambahan (misal: kode fasyankes / satker) |
| `form_config` | `json` | Konfigurasi selector form login dan opsi SPA |
| `is_active` | `boolean DEFAULT true` | Status aktif portal di SIMRS |
| `sort_order` | `int DEFAULT 0` | Urutan tampilan kartu pada halaman portal |
| `created_at`, `updated_at` | `timestamp` | Standar Eloquent timestamps |

### 2.2. Tabel `user_portal_credentials` (Mapping Akses & Akun Personal)
Menghubungkan user SIMRS dengan portal yang berhak diaksesnya serta menyimpan kredensial personal jika berlaku.

| Kolom | Tipe | Keterangan |
| :--- | :--- | :--- |
| `id` | `bigint unsigned PK` | Auto-increment ID |
| `user_id` | `bigint unsigned FK` | Relasi ke `users.id` (`onDelete('cascade')`) |
| `portal_id` | `bigint unsigned FK` | Relasi ke `portals.id` (`onDelete('cascade')`) |
| `credential_type` | `enum('use_shared', 'personal')` | Menggunakan akun bersama RS atau akun personal |
| `personal_username` | `varchar(255) NULL` | Username akun pribadi petugas |
| `personal_password` | `text NULL` | Password pribadi terenkripsi (`Crypt::encryptString`) |
| `personal_extra_fields`| `json NULL` | Field tambahan khusus user |
| `is_active` | `boolean DEFAULT true` | Status aktif hak akses user ini ke portal terkait |
| `notes` | `varchar(255) NULL` | Catatan mapping (opsional) |
| `created_at`, `updated_at` | `timestamp` | Standar Eloquent timestamps |

**Unique Constraint:** `UNIQUE(user_id, portal_id)`

---

## 3. Data Awal (Default Seeder) Portal Eksternal

Sistem disiapkan dengan data awal untuk 8 kelompok website eksternal:

1. **SIRIKA (BKKBN):** `https://siga-sirika.bkkbn.go.id/login`
   - Form config: Username selector `[#c, input[name='email']]`, Password selector `[#password, input[name='password']]`
2. **SIGA (Kemendukbangga):** `https://newsiga-siga.kemendukbangga.go.id/#/login` (SPA mode)
   - Form config: Username selector `[#email, input[name='email']]`, Password selector `[#password, input[name='password']]`
3. **SIHA (Kemenkes):** `https://sihapims2.kemkes.go.id/login`
   - Form config: Username selector `[#username, input[name='username']]`, Password selector `[#password, input[name='password']]`
4. **MPDN (Kemenkes):** `https://mpdn.kemkes.go.id/masuk`
   - Form config: Username selector `[#username, input[name='username']]`, Password selector `[#password, input[name='password']]`
5. **SITB (Kemenkes Jatim):** `https://jatim.sitb.id/sitb2024/app`
   - Form config: Username selector `[input[name='username']]`, Password selector `[#password, input[name='password']]`
6. **SIGIZI (Kemenkes):** `https://sigizikesga-stg.kemkes.go.id`
   - Form config: Username selector `[#username, input[name='username']]`, Password selector `[#password, input[name='password']]`
7. **SATU SEHAT (Kemenkes):** `https://satusehat.kemkes.go.id/platform/login`
   - Form config: Username selector `[#email, input[name='email']]`, Password selector `[#password, input[name='password']]`
8. **MutuFasyankes (Kemenkes):**
   - **IKP:** `https://mutufasyankes.kemkes.go.id/halaman/dashboard` (selectors: `#user`, `#pass`)
   - **PPRA:** `https://mutufasyankes.kemkes.go.id/ppra/` (selectors: `input[name='username']`, `input[name='password']`)
   - **SIMAR:** `https://mutufasyankes.kemkes.go.id/simar/` (selectors: `#uname`, `#pwd`)
   - **SIRS Online:** `https://akun-yankes.kemkes.go.id/` (Tanpa attribute ID dan Name; fallback selectors: `input[type='email']`, `input[type='password']`, `is_spa: true`)

---

## 4. Form Selector Engine & Heuristic Scanner

Untuk menjamin ketahanan terhadap variasi form dan perubahan tata letak di masa depan, sistem mengkombinasikan dua mekanisme:

```
[ Form Target Dimuat ]
         │
         ▼
[ 1. Coba Konfigurasi Statis (form_config) ] ──(Berhasil)──► [ Isi Nilai + Trigger Event ]
         │
      (Gagal / Kosong / DOM Berubah)
         ▼
[ 2. Jalankan Runtime Heuristic Scanner ]
    ├── Cari input[type='password'] yang visible
    ├── Cari input teks/email tepat sebelum password dalam form
    └── Analisis keyword label/placeholder ('email', 'user', 'id')
         │
     (Ditemukan)
         ▼
[ 3. Isi Nilai via Prototype Setter ]
         │
         ▼
[ 4. Dispatch Event 'input' & 'change' (Kompatibel React/Vue/Angular) ]
         │
         ▼
[ 5. Fokus ke Bidang CAPTCHA & Tampilkan Toast Sukses ]
```

### 4.1. Sintaks Konfigurasi `form_config` (JSON)
```json
{
  "is_spa": true,
  "wait_timeout_ms": 10000,
  "username_field": {
    "selectors": [
      "#email",
      "#c",
      "input[name='email']",
      "input[name='username']",
      "input[name='uname']",
      "input[name='user']",
      "input[type='email']",
      "input[placeholder*='email' i]",
      "//input[contains(@placeholder, 'Email')]"
    ]
  },
  "password_field": {
    "selectors": [
      "#password",
      "#pass",
      "input[name='password']",
      "input[name='pwd']",
      "input[type='password']"
    ]
  },
  "extra_fields": [
    {
      "key": "kode_satker",
      "selectors": ["#satker", "input[name='satker']"]
    }
  ],
  "auto_submit": false
}
```

### 4.2. Penanganan React / Vue Synthetic Events
Input pada website modern tidak akan merespons pengisian nilai langsung (`element.value = '...'`). Ekstensi menyuntikkan fungsi khusus:
```javascript
function setNativeValue(element, value) {
  const valueSetter = Object.getOwnPropertyDescriptor(element, 'value')?.set;
  const prototype = Object.getPrototypeOf(element);
  const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;
  
  if (prototypeValueSetter && valueSetter !== prototypeValueSetter) {
    prototypeValueSetter.call(element, value);
  } else if (valueSetter) {
    valueSetter.call(element, value);
  } else {
    element.value = value;
  }
  element.dispatchEvent(new Event('input', { bubbles: true }));
  element.dispatchEvent(new Event('change', { bubbles: true }));
}
```

---

## 5. Arsitektur Custom Browser Extension (Manifest V3)

Ekstensi disimpan di direktori `rs-extension/` di dalam repositori untuk kemudahan pengelolaan dan pengemasan file `.zip`.

### 5.1. Struktur Berkas Ekstensi
```
rs-extension/
├── manifest.json
├── background.js              # Service Worker (manajemen tab & in-memory credentials)
├── content-simrs.js           # Jembatan komunikasi pada domain SIMRS
├── content-autofill.js        # Script injeksi autofill & heuristic scanner pada domain target
├── popup/
│   ├── popup.html             # UI status ekstensi & Admin Form Inspector
│   ├── popup.js               # Logika inspeksi form 1-klik untuk Admin
│   └── popup.css
├── icons/
│   ├── icon-16.png
│   ├── icon-48.png
│   └── icon-128.png
└── README.md                  # Panduan instalasi dan deployment
```

### 5.2. Izin pada `manifest.json`
* **`permissions`:** `["tabs", "scripting", "storage"]`
* **`host_permissions`:**
  - Domain SIMRS lokal & produksi: `["*://*.rsasf.co.id/*", "http://localhost/*", "http://127.0.0.1/*"]`
  - Domain instansi target: `["https://*.kemkes.go.id/*", "https://*.bkkbn.go.id/*", "https://*.kemendukbangga.go.id/*", "https://*.sitb.id/*"]`

### 5.3. Alur Komunikasi One-Time Dispatch (Zero-Persistence)
1. **Pemicu:** Pengguna mengklik portal di halaman SIMRS.
2. **Backend Dispatch:** Frontend memanggil `POST /portal-pelaporan/{id}/dispatch-token`. Backend memverifikasi otorisasi, mengambil kredensial yang berlaku, mendekripsi password, dan merespons dalam JSON.
3. **Event Jembatan:** Frontend SIMRS mengeksekusi `window.dispatchEvent(new CustomEvent('SIFAST_PORTAL_LAUNCH', { detail }))`.
4. **Service Worker:** `content-simrs.js` meneruskan pesan ke `background.js`. Service worker membuka tab target (`chrome.tabs.create`), mencatat kredensial ke struktur Map di memori RAM `pendingTabs.set(targetTabId, payload)`, dan menyetel timer pembersihan 30 detik.
5. **Autofill & Flush:** Saat tab target selesai memuat halaman, `content-autofill.js` meminta kredensial ke background worker. Background mengirimkan kredensial dan **langsung menghapusnya dari RAM** (`pendingTabs.delete(targetTabId)`).

---

## 6. Antarmuka Pengguna (Frontend UI/UX)

### 6.1. Halaman Pengguna: Portal Pelaporan (`/portal-pelaporan`)
* **Deteksi Ekstensi di DOM & Event Handshake:**
  - `content-simrs.js` secara otomatis menginjeksi atribut dataset pada elemen `<html>`:
    ```javascript
    document.documentElement.dataset.sifastExtensionInstalled = "true";
    document.documentElement.dataset.sifastExtensionVersion = "1.0.0";
    ```
  - Untuk mendukung navigasi SPA Inertia.js (di mana halaman tidak di-reload penuh), `content-simrs.js` juga memancarkan event:
    `window.dispatchEvent(new CustomEvent('SIFAST_EXTENSION_READY', { detail: { version: "1.0.0" } }));`
    dan merespons event ping dari React (`SIFAST_PING_EXTENSION` -> `SIFAST_PONG_EXTENSION`).
  - **Tampilan Status UI React:**
    - Jika terdeteksi: Menampilkan badge hijau `● Ekstensi Aktif (v${version})` di header.
    - Jika tidak terdeteksi: Menampilkan banner bantuan instalasi dengan tombol unduh.
* **Tampilan Kartu:** Grid kartu responsif dengan ikon, kategori, tombol "Buka Portal", dan indikator tipe kredensial yang digunakan.
* **Modal Atur Akun Pribadi:** Memungkinkan staf memasukkan username & password pribadi mereka sendiri untuk portal yang mengizinkan akun personal.

### 6.2. Halaman Admin: Master Portal (`/admin/portals`)
* **Tabel Master:** Manajemen portal, toggle status aktif, pengaturan urutan (*sort order*).
* **Form Builder:** Input metadata portal, konfigurasi akun bersama RS, dan editor visual/JSON untuk form selector.

### 6.3. Halaman Admin: Mapping Akses (`/admin/portals/mapping`)
* **Tampilan Matriks:** Mode filter per Portal atau per Pengguna.
* **Aksi Massal:** Kemudahan mencentang akses portal untuk banyak pengguna sekaligus.

### 6.4. Distribusi Ekstensi
* Tersedia tombol unduh file `sifast-autofill-extension.zip` langsung dari SIMRS (dihasilkan secara otomatis oleh sistem atau di-host di storage lokal).

---

## 7. Rencana Pengujian & Verifikasi

1. **Unit & Feature Test (Pest/PHPUnit):**
   - Tes CRUD Master Portal dan enkripsi/dekripsi password.
   - Tes otorisasi endpoint dispatch (hanya user aktif yang memiliki mapping yang dapat menerima kredensial).
   - Tes isolasi hak akses (non-admin dilarang mengakses route `/admin/portals`).
2. **Browser Extension Test:**
   - Tes deteksi ekstensi pada domain SIMRS.
   - Tes pembukaan tab target dan konsumsi kredensial berbasis `tabId`.
   - Tes verifikasi penghapusan kredensial dari RAM setelah autofill (*zero memory leak*).
   - Tes ketahanan selector pada halaman contoh (SIRS Online, SITB, SIGA) dan keberhasilan Heuristic Scanner saat selector dinonaktifkan.
