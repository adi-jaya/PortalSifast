# Panduan Developer Portal Pelaporan Eksternal (Modul 13) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyusun dokumen panduan teknis komprehensif `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` sebagai *Single Source of Truth* (SSOT) yang mandiri untuk subsistem Portal Pelaporan Eksternal & Browser Extension SIMRS Sifast, serta mendaftarkannya pada indeks onboarding `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md`.

**Architecture:** Dokumen disusun dalam 9 bab definitif dan permanen mencakup arsitektur end-to-end, skema database, layer service backend, antarmuka admin React/Inertia, cetak biru ekstensi Chromium Manifest V3, modul portal agregator pengguna, panduan pengujian menyeluruh (Pest PHP 62 test cases & Node.js test runner), serta runbook operasional/troubleshooting. Setiap bab implementasi dilengkapi sub-bab *Hands-on Quick Verification* dan *Peta Berkas & Code Review* berstandar `[BARU]` dan `[MODIFIKASI]`.

**Tech Stack:** Markdown (GitHub Flavored Markdown), Mermaid diagrams, Laravel 12, Inertia.js v2, React 19, TypeScript, Tailwind CSS v4, Chromium Extensions Manifest V3, Pest PHP, Node.js 22 Test Runner.

**Spec:** [`docs/superpowers/specs/2026-09-14-portal-eksternal-developer-guide-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-14-portal-eksternal-developer-guide-design.md)

## Global Constraints

- **Single Source of Truth (SSOT):** Dokumen harus mandiri (*self-contained*) dan memuat seluruh detail teknis penting tanpa mengandalkan berkas *spec* atau *plan* sementara yang kelak akan dihapus.
- **Definitive 9-Chapter Framework:** Struktur 9 bab bersifat permanen; tidak ada penambahan atau pengubahan nomor bab di masa depan. Bab yang belum terimplementasi (Bab 6 & 7) menggunakan penanda status visual `[STATUS: SIAP DIIMPLEMENTASIKAN]` dan `[STATUS: TERENCANA]`.
- **In-Chapter Quick Verification:** Setiap bab implementasi wajib memiliki sub-bab uji coba praktis langsung (*Hands-on Verification*) dengan perintah tes cepat dan tautan ke Bab 8.
- **Standardized File Maps:** Setiap bab implementasi wajib memuat tabel berkas dengan status `[BARU]` atau `[MODIFIKASI]`, deskripsi fungsi, alasan (*rationale*), dan poin kritis *code review*.
- **No Placeholders:** Dilarang menggunakan placeholder seperti "TODO", "TBD", atau "akan dibahas nanti". Semua rujukan kelas, method, endpoint, dan perintah pengujian ditulis konkret.

---

### Task 1: Scaffolding Fondasi Dokumen (Header, Bab 1, Bab 2, & Bab 3)

**Files:**
- Create: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`
- Spec Reference: `docs/superpowers/specs/2026-09-14-portal-eksternal-developer-guide-design.md` (Bagian 1, Bab 1–3)

**Interfaces:**
- Produces: Kerangka dasar berkas Modul 13 yang memuat Daftar Isi navigatif, Bab 1 (Ikhtisar Bisnis, 3 Pilar Keamanan, Diagram Mermaid Arsitektur End-to-End), Bab 2 (Matriks Status Modular Plan 1–4 & Matriks Fitur/Rute), dan Bab 3 (Model Data & Skema Database `portals` serta `user_portal_credentials` terenkripsi dan 8 seeder bawaan).

- [ ] **Step 1: Tulis Header, Bab 1, Bab 2, dan Bab 3 pada Modul 13**

Tulis berkas `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi komprehensif mencakup:
1. Header modul dan Daftar Isi lengkap (9 Bab permanen).
2. **Bab 1: 📌 Ikhtisar Sistem & Filosofi Desain**
   - Latar belakang pelaporan rutin Kemenkes (SIRS, SITB, SIHA, MPDN, SATUSEHAT, MutuFasyankes) & BKKBN (SIRIKA, SIGA).
   - 3 Pilar Keamanan: Zero-Persistence RAM Queue, Hybrid Credential Model (*shared* vs *personal*), Native Synthetic Event Dispatcher (`setNativeValue` prototype setter bypass).
   - Diagram Alur Interaksi Mermaid End-to-End (`flowchart TD`) menghubungkan SIMRS Backend, SIMRS Frontend, Content Bridge, Service Worker, Content Engine, dan Website Target.
3. **Bab 2: 📊 Matriks Status Implementasi & Roadmap Modul**
   - Tabel 4 Rencana Modular (Plan 1 & Plan 2: Selesai; Plan 3: Siap Diimplementasikan; Plan 4: Terencana).
   - Tabel Fitur, Endpoint, Permission Policy, dan Status Ketersediaan.
4. **Bab 3: 🗄️ Model Data & Skema Database (Plan 1 - SELESAI)**
   - Struktur kolom lengkap dan tipe data tabel `portals` & `user_portal_credentials`.
   - Format JSON `form_config` (`is_spa`, `wait_timeout_ms`, `username_field`, `password_field`, `extra_fields`, `auto_submit`).
   - Penjelasan enkripsi simetris `Crypt::encryptString` pada `shared_password` dan `personal_password` serta perlindungan `$hidden`.
   - Ringkasan 8 website pelaporan resmi bawaan [`PortalSeeder.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/database/seeders/PortalSeeder.php).

- [ ] **Step 2: Verifikasi sintaks Markdown dan diagram Mermaid**

Jalankan pengecekan file exists dan pastikan tidak ada tag unclosed atau sintaks mermaid yang rusak:
```bash
test -f docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md && echo "File created successfully"
```

- [ ] **Step 3: Commit Task 1**

```bash
git add docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): scaffold module 13 developer guide chapters 1 to 3"
```

---

### Task 2: Bab 4 - Backend Core & Arsitektur Service Layer

**Files:**
- Modify: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`
- Codebase Reference:
  - `app/Services/Portal/AdminPortalService.php`
  - `app/Services/Portal/AdminPortalMappingService.php`
  - `app/Services/Portal/PortalDispatchService.php`
  - `app/Services/Portal/PortalPersonalCredentialService.php`
  - `app/Http/Controllers/Admin/AdminPortalController.php`
  - `app/Http/Controllers/Admin/AdminPortalMappingController.php`
  - `app/Http/Controllers/PortalDispatchController.php`
  - `app/Http/Controllers/PortalPersonalCredentialController.php`
  - `app/Policies/PortalPolicy.php`
  - `routes/web.php`

**Interfaces:**
- Consumes: Struktur Bab 1–3 dari Task 1.
- Produces: Dokumentasi lengkap Bab 4 memuat Service Class Layer, Controller injection, Database Transactions, Note-clearing logic, Defense-in-depth authorization, API contracts, File Map Plan 1, dan Hands-on Quick Test commands.

- [ ] **Step 1: Tambahkan Bab 4 ke dalam Modul 13**

Tulis Bab 4 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. **4.1. Arsitektur Service Class Layer (`App\Services\Portal\*`)**
   - Pembahasan detail 4 Concrete Services: `AdminPortalService`, `AdminPortalMappingService`, `PortalDispatchService`, dan `PortalPersonalCredentialService`.
   - Constructor Injection dan filosofi *Thin Controllers*.
   - Integritas transaksi DB massal `DB::transaction()` pada `syncPortalUsers` dan `syncUserPortals`.
   - Penanganan parameter `$updateNotes` (`$request->has('notes')`) untuk mencegah hilangnya catatan staf saat switch akses di-toggle vs pengosongan catatan sadar ke `null`.
2. **4.2. Defense-in-Depth Authorization & Middleware Perimeter**
   - Rute web perimeter: `middleware('can:manage,App\Models\Portal')`.
   - Gate & Policy: `PortalPolicy` (`manage`, `view`, `dispatchToken`).
   - Shared Inertia Props: `can_manage_portals`.
3. **4.3. Kontrak API Endpoint Dispatch Token**
   - `POST /portal-pelaporan/{portal}/dispatch-token`.
   - Request payload & format response JSON: `url`, `target_pattern`, `username`, `password` (plaintext decrypted on-the-fly), `extra_fields`, `form_config`.
4. **4.4. Peta Berkas & Panduan Code Review (Plan 1)**
   - Tabel berkas baru dan modifikasi (`app/Models/Portal.php`, `app/Models/UserPortalCredential.php`, Form Requests, Services, Controllers, Policy, Seeder, dan Tests) beserta rationale dan poin review.
5. **4.5. Panduan Uji Coba Cepat (Hands-on Verification Bab 4)**
   - Perintah Pest langsung untuk menguji database schema, models, policy, dan dispatch API:
     ```bash
     php artisan test --filter=PortalDatabaseSchemaTest
     php artisan test --filter=PortalModelTest
     php artisan test --filter=PortalPolicyTest
     php artisan test --filter=PortalDispatchApiTest
     ```
   - Tautan navigasi ke Bab 8 untuk detail asersi.

- [ ] **Step 2: Jalankan pengujian Pest untuk memverifikasi perintah tes di Bab 4**

```bash
php artisan test --filter=PortalDatabaseSchemaTest
php artisan test --filter=PortalDispatchApiTest
```
Expected: PASS

- [ ] **Step 3: Commit Task 2**

```bash
git add docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): add chapter 4 backend core and service layer to module 13"
```

---

### Task 3: Bab 5 - Modul Admin SIMRS (Master Portal & Mapping Akses)

**Files:**
- Modify: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`
- Codebase Reference:
  - `resources/js/Pages/Admin/Portals/Index.tsx`
  - `resources/js/Pages/Admin/Portals/Form.tsx`
  - `resources/js/Pages/Admin/Portals/Mapping.tsx`
  - `resources/js/Pages/Admin/Portals/MappingPortalView.tsx`
  - `resources/js/Pages/Admin/Portals/MappingUserView.tsx`
  - `resources/js/Components/Portal/FormConfigEditor.tsx`
  - `resources/js/Components/Portal/SelectorTagInput.tsx`
  - `resources/js/Components/ConfirmDialog.tsx`
  - `resources/js/Components/app-sidebar.tsx`

**Interfaces:**
- Consumes: Bab 1–4 dari Task 1 & 2.
- Produces: Dokumentasi lengkap Bab 5 memuat antarmuka admin Master Portal, FormConfigEditor, SelectorTagInput, Matriks Mapping Akses Dual-View, Instant Auto-Save, Ergonomi UI, File Map Plan 2, dan Hands-on Quick Test walkthrough.

- [ ] **Step 1: Tambahkan Bab 5 ke dalam Modul 13**

Tulis Bab 5 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. **5.1. Manajemen Master Portal (`/admin/portals`)**
   - Antarmuka tabel master, filter pencarian & kategori, badge status aktif, tombol toggle cepat.
   - Form Builder create/edit: generator auto-slug dinamis, conditional rendering bertipe akun, proteksi placeholder password yang sudah tersimpan tanpa menimpa password jika tidak diisi.
   - Modal accessible `ConfirmDialog` pengganti blocking `window.confirm()`.
2. **5.2. Visual Form Configuration Editor**
   - Komponen `FormConfigEditor` & `SelectorTagInput`: live toggle antara visual selector chips (dengan preset cepat `#id`, `name`, `type`) dan Raw JSON editor dengan validasi sintaks sebelum form disimpan.
3. **5.3. Matriks Mapping Akses Dual-Mode (`/admin/portals/mapping`)**
   - *Mode Portal-Centric:* Pemilihan portal, paginasi staf tersinkronisasi query string (`DataTablePagination`), switch izin akses, select tipe akun, input catatan.
   - *Mode User-Centric:* Pemilihan staf dari seluruh karyawan rumah sakit (`all_users` unpaginated) via input pencarian realtime didebounce (300ms), daftar portal dengan switch akses instan.
   - *Instant Auto-Save & Optimistic UI:* Setiap perubahan baris otomatis tersimpan ke backend via `POST /admin/portals/mapping/save-row` dengan mikro-indikator status centang hijau *Tersimpan*.
   - *Toolbar Aksi Cepat Massal:* "Izinkan Semua (Akun Bersama)", "Izinkan Semua (Akun Personal)", dan "Cabut Semua Akses" (1 transaksi DB).
   - *Ergonomi UI (Commit 7c4f627):* Layout filter horizontal, optimistic dropdown, pembersihan query string kosong otomatis.
4. **5.4. Peta Berkas & Panduan Code Review (Plan 2)**
   - Tabel berkas baru dan modifikasi frontend (`Pages/Admin/Portals/*`, `Components/Portal/*`, `Components/app-sidebar.tsx`) beserta rationale teknis dan poin review.
5. **5.5. Panduan Uji Coba Langsung (Hands-on Verification Bab 5)**
   - Langkah nyata mencoba di browser admin:
     1. Login sebagai Admin SIMRS, buka menu `/admin/portals`.
     2. Tambah portal baru, uji auto-slug dan selector tag editor.
     3. Buka menu `/admin/portals/mapping`, ubah switch akses dan catatan staf, refresh browser untuk memverifikasi data tersimpan seketika di database.
   - Perintah tes cepat Pest untuk Admin UI:
     ```bash
     php artisan test --filter=AdminPortalControllerTest
     php artisan test --filter=AdminPortalMappingControllerTest
     ```
   - Tautan navigasi ke Bab 8 untuk skenario lengkap.

- [ ] **Step 2: Jalankan pengujian Pest untuk memverifikasi perintah tes di Bab 5**

```bash
php artisan test --filter=AdminPortalControllerTest
php artisan test --filter=AdminPortalMappingControllerTest
```
Expected: PASS (33 tests PASS)

- [ ] **Step 3: Commit Task 3**

```bash
git add docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): add chapter 5 admin portal master and mapping ui to module 13"
```

---

### Task 4: Bab 6 & Bab 7 - Browser Extension & User Portal Specifications

**Files:**
- Modify: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`
- Plan Reference:
  - `docs/superpowers/plans/2026-09-14-portal-eksternal-plan-3-browser-extension.md`
  - `docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md` (Plan 3 & Plan 4 sections)

**Interfaces:**
- Consumes: Bab 1–5 dari Task 1, 2, & 3.
- Produces: Dokumentasi lengkap Bab 6 (Spesifikasi Ekstensi Manifest V3 `rs-extension/` bertanda `[STATUS: SIAP DIIMPLEMENTASIKAN (Plan 3)]`) dan Bab 7 (Spesifikasi Halaman Pengguna Portal Agregator & Distribusi ZIP bertanda `[STATUS: TERENCANA (Plan 4)]`).

- [ ] **Step 1: Tambahkan Bab 6 ke dalam Modul 13**

Tulis Bab 6 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. Status: `[STATUS: SIAP DIIMPLEMENTASIKAN (Plan 3)]`.
2. Filosofi Teknis: Pure Vanilla JS (ES2022+), zero bundler runtime bloat, ukuran < 100 KB, mudah diaudit.
3. Konfigurasi `manifest.json`: Strict permissions `["tabs", "scripting", "storage"]`, host permissions spesifik (domain RS & domain target resmi).
4. Background Service Worker (`background.js`): In-memory RAM queue berbasis `tabId`, TTL 30s, auto-flush seketika setelah autofill, listener penutupan tab `chrome.tabs.onRemoved`.
5. Content Bridge SIMRS (`content-simrs.js`): Dataset DOM handshake pada elemen `<html>` (`dataset.sifastExtensionInstalled`, `dataset.sifastExtensionVersion`), CustomEvent `SIFAST_EXTENSION_READY`, ping/pong listener, dan relay event `SIFAST_PORTAL_LAUNCH`.
6. Content Engine Target (`content-autofill.js`): Resolusi selector statis, fallback Runtime Heuristic Scanner, bypass prototype setter `setNativeValue`, deteksi bidang CAPTCHA (auto-focus + toast ramah pengguna).
7. Admin Popup Tool (`popup/`): UI clean teal/emerald, fitur 1-Click Form Inspector untuk ekstrak konfigurasi JSON form login target.
8. Peta Berkas Target `rs-extension/` & Rencana Code Review (Plan 3).
9. Panduan Uji Coba Cepat (Hands-on Extension Verification): Perintah eksekusi `node --test rs-extension/tests/*.test.js` dan panduan memuat unpacked extension di Chrome/Edge (`chrome://extensions`).

- [ ] **Step 2: Tambahkan Bab 7 ke dalam Modul 13**

Tulis Bab 7 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. Status: `[STATUS: TERENCANA (Plan 4)]`.
2. Halaman Portal Pelaporan Pengguna (`/portal-pelaporan`): Grid kartu portal terfilter hak akses staf terdaftar, filter kategori, live search, badge indikator akun bersama vs personal.
3. Deteksi Handshake Ekstensi di UI React: Badge hijau `● Ekstensi Sifast Aktif (v1.0.0)` vs Banner bantuan unduh ekstensi.
4. Modal Kredensial Pribadi Mandiri (*Self-Service Credential Modal*): Form input username & password pribadi langsung oleh staf pemilik akun.
5. Mekanisme Distribusi & Packaging File ZIP Ekstensi: Endpoint `GET /portal-pelaporan/extension/download` dan service bundler ZIP otomatis.
6. Peta Berkas Target & Rencana Code Review (Plan 4).
7. Panduan Uji Coba Cepat (Hands-on Verification Bab 7): Skenario login staf biasa, verifikasi filter kartu, dan tes download ZIP.

- [ ] **Step 3: Commit Task 4**

```bash
git add docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): add chapter 6 extension and chapter 7 user portal specs to module 13"
```

---

### Task 5: Bab 8 & Bab 9 - Master Testing Guide & Operational Runbook

**Files:**
- Modify: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`
- Codebase Reference:
  - `tests/Feature/PortalPelaporan/*` (9 feature test files)
  - `database/seeders/PortalSeeder.php`

**Interfaces:**
- Consumes: Bab 1–7 dari Task 1 s/d 4.
- Produces: Dokumentasi lengkap Bab 8 (Master Testing Suite: Pest PHP 62 tests PASS, Node.js runner, 5 skenario manual QA mendalam) dan Bab 9 (Runbook Operasional: tambah portal, retuning selector DOM target, debugging ekstensi, audit keamanan zero-leakage).

- [ ] **Step 1: Tambahkan Bab 8 ke dalam Modul 13**

Tulis Bab 8 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. **8.1. Automated Backend Testing (Pest PHP)**
   - Perintah eksekusi satu baris:
     ```bash
     php artisan test --filter=PortalPelaporan
     ```
   - Status: 62 test cases PASS, 354 assertions.
   - Tabel matriks komprehensif 9 berkas Pest Feature Test:
     * `PortalDatabaseSchemaTest`: Struktur kolom tabel & foreign keys cascade.
     * `PortalModelTest`: Enkripsi simetris Eloquent, helper method, integritas factory.
     * `PortalPolicyTest`: Hak kelola Admin, hak lihat user terdaftar, izin dispatch token.
     * `PortalInertiaPropsTest`: Shared props `can_manage_portals` untuk role admin vs user vs guest.
     * `AdminPortalServiceTest` & `AdminPortalControllerTest`: Filter, paginasi, auto-slug, proteksi password kosong saat update, toggle aktif, perimeter middleware.
     * `AdminPortalMappingServiceTest` & `AdminPortalMappingControllerTest`: Dual-view matriks, instant auto-save `saveRow`, filter dept/search, transaksional massal, pemisahan note-clearing (`$updateNotes`).
     * `PortalDispatchServiceTest` & `PortalDispatchApiTest`: Resolusi kredensial (personal vs shared), fallback admin, proteksi status aktif, respon JSON one-time transfer.
     * `PortalPersonalCredentialServiceTest` & `PortalPersonalCredentialApiTest`: Self-service update username & password tanpa menimpa password jika tidak diisi.
     * `PortalSeederTest`: Idempotensi 8 kelompok portal eksternal resmi.
2. **8.2. Automated Extension Testing (Node.js Test Runner - Plan 3)**
   - Perintah: `node --test rs-extension/tests/*.test.js`.
   - Cakupan: schema manifest, in-memory queue, DOM handshake, autofill heuristic scanner, popup inspector.
3. **8.3. Skenario Manual QA Langkah-demi-Langkah (Walkthrough Lengkap)**
   - Skenario QA 1: CRUD Master Portal & Selector Tag Editor (UI, validasi, enkripsi DB).
   - Skenario QA 2: Matriks Mapping Akses & Instant Auto-Save (Dual-view, switch toggle, onBlur note, refresh persistence).
   - Skenario QA 3: One-Time Dispatch Token & Zero-Leakage (Panggilan API, dekripsi payload, verifikasi tidak bocor di props).
   - Skenario QA 4: Handshake & Autofill Ekstensi (Load unpacked di Chrome, deteksi badge hijau, autofill form, fokus CAPTCHA, verifikasi auto-flush RAM).
   - Skenario QA 5: Halaman Pengguna Staf Biasa & Distribusi ZIP (Akses terbatas, update personal credentials, unduh ZIP).

- [ ] **Step 2: Tambahkan Bab 9 ke dalam Modul 13**

Tulis Bab 9 pada `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` dengan isi:
1. **9.1. Prosedur Menambah Portal Pelaporan Eksternal Baru**
   - Metode A: Melalui Web Admin UI (`/admin/portals/create`) dengan bantuan Form Inspector ekstensi.
   - Metode B: Melalui Database Seeder (`PortalSeeder.php`).
2. **9.2. Prosedur Penanganan Perubahan DOM Form Login Target**
   - Analisis kegagalan selector, inspeksi elemen form login baru via Chrome DevTools, update konfigurasi selector di web admin SIMRS tanpa rilis ulang kode.
3. **9.3. Prosedur Debugging Ekstensi Chromium**
   - Cara me-reload ekstensi di browser pengembang.
   - Membuka console log Service Worker (`chrome://extensions` $\rightarrow$ Inspect views: service worker).
   - Membuka console log Content Script pada tab target (DevTools context selector).
4. **9.4. Checklist Audit Keamanan & Zero-Leakage**
   - Audit React DevTools / Inertia page props (memastikan tidak ada password lolos).
   - Audit Network tab browser (HTTPS dispatch token only).
   - Audit RAM Background Service Worker (`pendingTabs.size === 0`).

- [ ] **Step 3: Commit Task 5**

```bash
git add docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): add chapter 8 testing guide and chapter 9 runbook to module 13"
```

---

### Task 6: Integrasi Indeks Onboarding & Verifikasi Menyeluruh

**Files:**
- Modify: `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md`
- Verify: `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`

**Interfaces:**
- Consumes: Berkas Modul 13 lengkap dari Task 1 s/d 5.
- Produces: Indeks onboarding terbarui yang memuat Modul 13 pada tabel direktori dokumen dan rekomendasi urutan belajar developer, serta hasil verifikasi bebas broken links dan tes Pest 100% PASS.

- [ ] **Step 1: Perbarui Berkas Indeks Onboarding `00-INDEX-DAN-PANDUAN-MEMBACA.md`**

1. Tambahkan baris nomor 13 pada tabel di bagian *## 📚 Struktur Modul Dokumentasi Onboarding*:
   ```markdown
   | **13** | [`13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`](./13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md) | Subsistem Agregator Portal Pelaporan Eksternal Pemerintah (Kemenkes & BKKBN), Kredensial Hibrida Terenkripsi, dan Custom Chromium Extension Manifest V3 (Zero-Persistence Autofill). |
   ```
2. Tambahkan poin nomor 5 pada bagian *## 🎯 Rekomendasi Urutan Belajar Developer Baru*:
   ```markdown
   5. **Hari ke-5 (Integrasi Eksternal & Browser Extension):** Baca [`13`](./13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md) untuk memahami arsitektur portal pelaporan, enkripsi kredensial, dan ekstensi Chromium.
   ```

- [ ] **Step 2: Jalankan Verifikasi Menyeluruh Test Suite Pest**

Jalankan perintah pengujian untuk memastikan seluruh suite tes subsistem portal pelaporan lulus tanpa regresi:
```bash
php artisan test --filter=PortalPelaporan
```
Expected: 62 passed (354 assertions)

- [ ] **Step 3: Verifikasi kelengkapan berkas dan tautan internal**

Periksa kelengkapan file `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` (pastikan tidak ada kata "TODO" atau "TBD"):
```bash
grep -nE "(TODO|TBD)" docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md || echo "No placeholders found! Clean."
```

- [ ] **Step 4: Commit Task 6**

```bash
git add docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md
git commit -m "docs(onboarding): register module 13 in index and finalize developer guide SSOT"
```
