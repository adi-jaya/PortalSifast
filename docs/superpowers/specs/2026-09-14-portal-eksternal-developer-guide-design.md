# Desain Spesifikasi: Panduan Developer Portal Pelaporan Eksternal & Browser Extension (Modul 13)

**Tanggal Dibuat:** 2026-09-14  
**Status:** Disetujui (Approved via Brainstorming)  
**Tipe Dokumen:** Spesifikasi Desain Dokumentasi & Living Technical Guide  
**Target Berkas:**
1. `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` (Berkas Utama)
2. `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md` (Pembaruan Indeks Modul Onboarding)

---

## 1. Latar Belakang & Tujuan Dokumen

Subsistem **Portal Pelaporan Eksternal & Custom Browser Extension Autofill** merupakan subsistem multi-layer yang menghubungkan SIMRS Sifast (Laravel 12, Inertia.js v2, React 19, TypeScript, Tailwind CSS v4) dengan berbagai platform pelaporan resmi pemerintah (Kemenkes & BKKBN) melalui ekstensi browser Chromium Manifest V3.

Pengembangan subsistem ini dirancang secara modular melalui 4 tahapan (*Implementation Plans*):
* **Plan 1:** Fondasi Backend, Database, Model, Service Layer, & Otorisasi *(Selesai - 29 Pest Tests PASS)*
* **Plan 2:** Modul Admin (Master Portal & Matriks Mapping Akses Dual-View) *(Selesai - 33 Pest Tests PASS, Total 62 Tests PASS)*
* **Plan 3:** Halaman Pengguna (Portal Agregator, Deteksi Ekstensi & Distribusi ZIP) *(Selesai - 29 Pest Tests PASS, Total 91 Pest Tests PASS)*
* **Plan 4:** Custom Browser Extension Manifest V3 (`rs-extension/`) *(Selesai - 51 Node.js Tests PASS)*

### Prinsip Single Source of Truth (SSOT):
Dokumen implementasi teknis (*plans*) dan dokumen spesifikasi sementara (*specs*) akan dihapus setelah seluruh subsistem selesai diimplementasikan. Oleh karena itu, panduan developer ini dirancang untuk:
1. **Mandiri (*Self-Contained*):** Menjadi Sumber Kebenaran Utama (*Single Source of Truth*) yang memuat seluruh esensi arsitektur, filosofi keamanan, skema database, kontrak API, spesifikasi ekstensi, dan alur kerja sistem secara permanen.
2. **Alat Bantu Code Review & Audit:** Menyediakan peta berkas (`[BARU]` dan `[MODIFIKASI]`), alasan arsitektural (*rationale*), serta poin-poin kritis yang harus diperiksa oleh pereview kode.
3. **Pusat Pengujian & Verifikasi (Testing Guide):** Memberikan instruksi pengujian otomatis (Pest PHP & Node.js test runner) serta skenario pengujian manual langkah-demi-langkah yang langsung dapat dipraktikkan hari ini untuk fitur yang sudah aktif, maupun pasca-implementasi untuk modul berikutnya.
4. **Kerangka Permanen (Tanpa Penambahan/Perubahan Bab):** Mengunci struktur dokumen ke dalam 9 bab definitif sejak awal, sehingga pembaruan di masa depan cukup mengubah status bab dan melengkapi bukti pengujian tanpa merombak nomor bab atau daftar isi.

---

## 2. Struktur Definitif Dokumen: 9 Bab Lengkap

Dokumen `docs/developer-onboarding/13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md` akan disusun dengan struktur bab dan sub-bab sebagai berikut:

### Bab 1: 📌 Ikhtisar Sistem & Filosofi Desain
* **1.1. Konteks Bisnis & Latar Belakang:** Kebutuhan pelaporan rutin staf rumah sakit ke platform Kemenkes dan BKKBN; tantangan puluhan URL, akun login terpisah, risiko akun tercecer, dan waktu habis untuk input manual.
* **1.2. 3 Pilar Keamanan & Filosofi Arsitektur:**
  1. *Zero-Persistence Security:* Ekstensi browser tidak pernah menyimpan kredensial ke media persisten browser (`chrome.storage.local`, `localStorage`, Cookies). Kredensial transit hanya di memori RAM Service Worker berbasis `tabId`, memiliki TTL 30 detik, dan langsung dihapus (*auto-flush*) seketika setelah autofill selesai.
  2. *Hybrid Credential Model:* Mendukung akun bersama rumah sakit (*shared institutional account*) dan akun pribadi staf (*personal account*). Hak akses diatur terpusat oleh Admin/IT, namun staf berhak memperbarui password akun pribadinya secara mandiri (*self-service*).
  3. *Native Synthetic Event Dispatcher:* Pengisian form tidak menggunakan `element.value = ...` langsung (yang diabaikan oleh React/Vue/Angular), melainkan melalui pemanggilan setter prototype asli (`setNativeValue`) diikuti event `input`, `change`, dan `blur` dengan `bubbles: true`.
* **1.3. Diagram Arsitektur Interaksi Sistem:**
  * Diagram alur end-to-end Mermaid (`flowchart TD`): Interaksi antara Laravel Backend, React Frontend, Content Bridge SIMRS, Service Worker RAM Queue, Content Engine Target, dan Website Eksternal.

### Bab 2: 📊 Matriks Status Implementasi & Roadmap Modul
* **2.1. Matriks Status Rencana Modular:**
  * Plan 1 (Fondasi Backend & DB): `[STATUS: TERIMPLEMENTASI (PASS)]`
  * Plan 2 (Modul Admin Master & Mapping): `[STATUS: TERIMPLEMENTASI (PASS)]`
  * Plan 3 (Halaman Pengguna & Distribusi ZIP): `[STATUS: TERIMPLEMENTASI (PASS)]`
  * Plan 4 (Browser Extension Manifest V3): `[STATUS: TERIMPLEMENTASI (PASS)]`
* **2.2. Matriks Fitur, Rute, & Hak Akses:** Tabel pemetaan fitur, rute web/API, permission Gate/Policy yang dibutuhkan, dan status ketersediaannya.

### Bab 3: 🗄️ Model Data & Skema Database (Plan 1 - Selesai)
* **3.1. Tabel `portals` (Master Website Eksternal):**
  * Definisi kolom lengkap, tipe data, dan keterangan: `id`, `name`, `slug` (unique), `category`, `url`, `url_pattern`, `icon_path`, `description`, `auth_type` (`shared`, `personal`, `both`), `shared_username`, `shared_password` (terenkripsi simetris `Crypt::encryptString`), `shared_extra_fields` (JSON), `form_config` (JSON), `is_active`, `sort_order`, timestamps.
  * Spesifikasi struktur JSON `form_config`: `is_spa`, `wait_timeout_ms`, `username_field` (array selectors), `password_field`, `extra_fields`, `auto_submit`.
* **3.2. Tabel `user_portal_credentials` (Mapping Hak Akses & Akun Personal):**
  * Definisi kolom: `id`, `user_id` (FK cascade ke `users`), `portal_id` (FK cascade ke `portals`), `credential_type` (`use_shared`, `personal`), `personal_username`, `personal_password` (terenkripsi simetris `Crypt::encryptString`), `personal_extra_fields` (JSON), `is_active`, `notes`, timestamps.
  * Integritas Relasi & Constraint Unik: `UNIQUE(user_id, portal_id)` dan `onDelete('cascade')`.
* **3.3. Data Awal Default Seeder (8 Kelompok Portal Resmi):**
  * Daftar dan karakteristik website bawaan: SIRIKA (BKKBN), SIGA (Kemendukbangga SPA), SIHA (Kemenkes), MPDN (Kemenkes), SITB (Kemenkes Jatim), SIGIZI (Kemenkes), SATU SEHAT (Kemenkes), dan MutuFasyankes (IKP, PPRA, SIMAR, SIRS Online).

### Bab 4: ⚙️ Backend Core & Arsitektur Service Layer (Plan 1 - Selesai)
* **4.1. Arsitektur Concrete Services Layer (`App\Services\Portal\*`):**
  * `AdminPortalService`: CRUD master, generator auto-slug, paginasi filter, toggle status aktif, dan proteksi password kosong saat update.
  * `AdminPortalMappingService`: Pengambilan matriks dual-mode, simpan baris tunggal *instant auto-save* (`saveSingleAssignment`) dengan dukungan `$updateNotes`, sinkronisasi massal transaksional (`syncPortalUsers`, `syncUserPortals` dalam `DB::transaction()`).
  * `PortalDispatchService`: Resolusi kredensial login (personal vs shared), dekripsi password simetris `Crypt::decryptString`, verifikasi status aktif portal & user, pembentukan JSON response one-time transfer.
  * `PortalPersonalCredentialService`: Self-service pengelolaan username dan password mandiri oleh staf.
* **4.2. Prinsip Desain Backend:**
  * Constructor Injection & Thin Controllers (controller hanya bertugas memvalidasi request dan memanggil service).
  * Defense-in-Depth Authorization (`PortalPolicy` pada controller + middleware perimeter `can:manage,App\Models\Portal` pada level rute `routes/web.php`).
* **4.3. Kontrak API & Endpoint Dispatch Token:**
  * `POST /portal-pelaporan/{portal}/dispatch-token`: Spesifikasi request payload dan JSON response payload.
* **4.4. Peta Berkas & Panduan Code Review (Plan 1):**
  * Tabel berkas baru dan modifikasi (`app/Models/Portal.php`, `app/Models/UserPortalCredential.php`, `database/migrations/*`, `app/Services/Portal/*`, `app/Http/Controllers/*`, `app/Http/Requests/*`, `app/Policies/PortalPolicy.php`, `routes/web.php`, dll.) beserta *rationale* dan poin kritis review.
* **4.5. Panduan Uji Coba Cepat (Hands-on Verification Bab 4):**
  * Perintah Pest langsung untuk backend core & API. Tautan ke Bab 8.

### Bab 5: 🖥️ Modul Admin SIMRS: Master Portal & Mapping Akses (Plan 2 - Selesai)
* **5.1. Manajemen Master Portal (`/admin/portals`):**
  * Tabel daftar portal, pencarian realtime, filter kategori, toggle switch aktif/nonaktif, modal konfirmasi hapus aman accessible (`ConfirmDialog`).
  * Form builder create/edit: auto-slug reaktif, conditional fields bertipe akun, proteksi placeholder password yang sudah tersimpan.
* **5.2. Visual Form Configuration Editor:**
  * Komponen `FormConfigEditor` & `SelectorTagInput`: live toggle antara visual selector chips (dengan preset cepat `#id`, `name`, `type`) dan Raw JSON editor dengan validasi sintaks.
* **5.3. Matriks Mapping Akses Dual-Mode (`/admin/portals/mapping`):**
  * *Mode Portal-Centric:* Paginasi staf RS tersinkronisasi query string (`DataTablePagination`).
  * *Mode User-Centric:* Akses ke seluruh staf RS (`all_users` unpaginated) dengan pencarian realtime didebounce (300ms) dan daftar portal.
  * *Instant Auto-Save:* Perubahan switch status akses, select tipe akun, dan input catatan (onBlur) langsung tersimpan ke backend via `POST /admin/portals/mapping/save-row` dengan optimistic UI.
  * *Toolbar Aksi Cepat Massal:* "Izinkan Semua (Akun Bersama)", "Izinkan Semua (Akun Personal)", dan "Cabut Semua Akses".
  * *Ergonomi UI:* Layout horizontal, optimistic dropdown, pembersihan parameter query string kosong otomatis.
* **5.4. Peta Berkas & Panduan Code Review (Plan 2):**
  * Tabel berkas frontend dan navigasi (`Pages/Admin/Portals/Index.tsx`, `Form.tsx`, `Mapping.tsx`, `MappingPortalView.tsx`, `MappingUserView.tsx`, `Components/Portal/*`, `Components/app-sidebar.tsx`).
* **5.5. Panduan Uji Coba Langsung (Hands-on Verification Bab 5):**
  * Skenario langkah-demi-langkah mencoba di browser admin: buat portal, uji tag selector, uji auto-save mapping, verifikasi DB.
  * Perintah test cepat: `php artisan test --filter=AdminPortal`. Tautan ke Bab 8.

### Bab 6: 🚀 Modul Pengguna: Portal Agregator & Distribusi Ekstensi (Plan 3 - Selesai)
* **Status:** `[STATUS: TERIMPLEMENTASI (Plan 3 - PASS)]`
* **6.1. Halaman Agregator Pengguna (`/portal-pelaporan`):**
  * Grid kartu portal interaktif sesuai hak akses staf terdaftar.
  * Filter kategori dan pencarian live. Indikator tipe akun pada kartu.
* **6.2. Handshake Ekstensi di UI React:**
  * Pengecekan dataset dan ping ekstensi.
  * Tampilan badge hijau `● Ekstensi Sifast Aktif (v1.0.0)` jika terpasang.
  * Banner bantuan edukatif dengan tombol unduh jika ekstensi belum terpasang.
* **6.3. Modal Kredensial Pribadi Mandiri (*Self-Service Credential Modal*):**
  * Pengaturan username & password pribadi langsung oleh staf pemilik akun.
* **6.4. Mekanisme Distribusi & Packaging File ZIP:**
  * Distribusi aset statis `GET /downloads/sifast-autofill-extension.zip` yang dipaketkan via `npm run package:extension`.
* **6.5. Peta Berkas Target & Rencana Code Review (Plan 3):** Tabel berkas halaman pengguna dan controller.
* **6.6. Panduan Uji Coba Cepat (Hands-on Verification Bab 6):** Skenario pengujian akses staf dan unduhan ZIP. Tautan ke Bab 8.

### Bab 7: 🧩 Custom Browser Extension Manifest V3 (Plan 4 - Selesai)
* **Status:** `[STATUS: TERIMPLEMENTASI (Plan 4 - PASS)]`
* **7.1. Filosofi & Batasan Teknis:** Pure Vanilla JS (ES2022+), zero bundler runtime bloat, ukuran < 100 KB, mudah diaudit.
* **7.2. Konfigurasi `manifest.json` (Manifest V3):** Permissions spesifik (`["tabs", "scripting", "activeTab"]`), host permissions terbatas (domain RS & domain target pemerintah).
* **7.3. Background Service Worker (`background.js`):**
  * In-memory RAM Map queue ber-index `tabId`: `pendingTabs.set(targetTabId, payload)`.
  * TTL 30 detik auto-expire.
  * Auto-flush seketika setelah kredensial dikonsumsi oleh content script target (`pendingTabs.delete(targetTabId)`).
  * Listener darurat penutupan tab (`chrome.tabs.onRemoved`).
  * Validasi protokol (hanya http/https) dan origin matching.
* **7.4. Content Bridge SIMRS (`content-simrs.js`):**
  * Handshake atribut dataset DOM pada elemen `<html>` (`dataset.sifastExtensionInstalled`, `dataset.sifastExtensionVersion`).
  * Emisi event `SIFAST_EXTENSION_READY` & listener ping/pong `SIFAST_PING_EXTENSION` untuk dukungan navigasi SPA React.
  * Relay event klik kartu `SIFAST_PORTAL_LAUNCH` ke Service Worker.
* **7.5. Content Engine Target (`content-autofill.js`):**
  * Pengambilan payload dari background worker berbasis `tabId`.
  * Resolusi selector statis dari database (`form_config`).
  * Fallback Runtime Heuristic Scanner jika selector DOM tidak ditemukan.
  * Bypass setter framework modern via `setNativeValue` (prototype setter invocation + event bubbles).
  * Deteksi bidang CAPTCHA: auto-focus ke input CAPTCHA dan pemunculan toast ramah pengguna (CAPTCHA tetap manual).
* **7.6. Admin Popup Tool (`popup/`):**
  * UI popup status koneksi ekstensi.
  * Fitur 1-Click Form Inspector: menganalisis form aktif di tab target dan menghasilkan JSON `form_config` siap salin.
* **7.7. Packaging & Distribusi Ekstensi:** Script pengemasan `rs-extension/scripts/package-extension.js`.
* **7.8. Peta Berkas Target & Rencana Code Review (Plan 4):** Tabel 19 berkas dalam `rs-extension/` dan tes Node.js.
* **7.9. Panduan Uji Coba Cepat (Hands-on Extension Verification Bab 7):** Perintah `npm run test:extension` dan cara memuat unpacked extension di Chrome/Edge (`chrome://extensions`). Tautan ke Bab 8.

### Bab 8: 🧪 Panduan Pengujian & Skenario Verifikasi (Master Testing Guide)
* **8.1. Automated Backend Testing (Pest PHP):**
  * Perintah eksekusi: `vendor/bin/pest tests/Feature/PortalPelaporan tests/Feature/PortalNavParityTest.php`.
  * Matriks 13 berkas test feature Pest beserta deskripsi cakupan dan asersinya (91 Feature Tests + 2 Nav Tests PASS).
* **8.2. Automated Extension Testing (Node.js Test Runner - Plan 4):**
  * Perintah eksekusi: `npm run test:extension` (atau `node --test rs-extension/tests/*.test.js`).
  * Matriks 6 berkas test suite Node.js (51 Tests PASS).
* **8.3. Skenario Manual QA Langkah-demi-Langkah (Walkthrough Komprehensif):**
  * Skenario 1: CRUD Master Portal & Selector Tag Editor (UI, validasi, enkripsi DB).
  * Skenario 2: Matriks Mapping Akses & Instant Auto-Save (Dual-view, switch toggle, onBlur note, refresh persistence).
  * Skenario 3: One-Time Dispatch Token & Zero-Leakage (Panggilan API, dekripsi payload, verifikasi tidak bocor di props).
  * Skenario 4: Handshake & Autofill Ekstensi (Load unpacked di Chrome, deteksi badge hijau, autofill form, fokus CAPTCHA, verifikasi auto-flush RAM).
  * Skenario 5: Halaman Pengguna Staf Biasa & Distribusi ZIP (Akses terbatas, update personal credentials, unduh ZIP).

### Bab 9: 🛠️ Runbook Operasional, Pemeliharaan & Troubleshooting
* **9.1. Prosedur Menambah Portal Pelaporan Eksternal Baru:**
  * Metode A (Web Admin UI dengan bantuan Form Inspector ekstensi).
  * Metode B (Database Seeder `PortalSeeder.php`).
* **9.2. Prosedur Penanganan Perubahan DOM Form Login Target:**
  * Analisis kegagalan autofill, inspeksi DOM menggunakan DevTools, penyesuaian konfigurasi selector di web admin SIMRS tanpa rilis ulang kode.
* **9.3. Prosedur Debugging Ekstensi Chromium:**
  * Cara me-reload ekstensi di browser pengembang.
  * Membuka console log Service Worker (`chrome://extensions` $\rightarrow$ Inspect views: service worker).
  * Membuka console log Content Script pada tab target (DevTools context selector).
* **9.4. Checklist Audit Keamanan & Zero-Leakage:**
  * Audit React DevTools / Inertia page props (memastikan tidak ada password lolos).
  * Audit Network tab browser (HTTPS dispatch token only).
  * Audit RAM Background Service Worker (`pendingTabs.size === 0`).

---

## 3. Integrasi Indeks Modul Onboarding

Berkas [`docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md) akan diperbarui:
1. **Penambahan pada Tabel Modul Onboarding:**
   ```markdown
   | **13** | [`13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md`](./13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md) | Subsistem Agregator Portal Pelaporan Eksternal Pemerintah (Kemenkes & BKKBN), Kredensial Hibrida Terenkripsi, dan Custom Chromium Extension Manifest V3 (Zero-Persistence Autofill). |
   ```
2. **Penambahan pada Rekomendasi Urutan Belajar Developer:**
   ```markdown
   5. **Hari ke-5 (Integrasi Eksternal & Browser Extension):** Baca [`13`](./13-MODUL-PORTAL-PELAPORAN-EKSTERNAL.md) untuk memahami arsitektur portal pelaporan, enkripsi kredensial, dan ekstensi Chromium.
   ```

---

## 4. Evaluasi Kualitas & Kriteria Sukses (Self-Review Checklist)

* [x] **Bebas Placeholder:** Tidak ada `TODO` atau `TBD`. Semua spesifikasi teknis dan rujukan nama kelas/berkas telah terdefinisi secara konkret.
* [x] **Konsistensi Arsitektur:** Struktur berkas dan alur data selaras 100% dengan implementasi Plan 1, Plan 2, rancangan Plan 3, dan spesifikasi induk.
* [x] **Kemampuan Berdiri Sendiri (SSOT):** Dokumen tetap utuh dan informatif meskipun berkas *plan* atau *spec* sementara di direktori `docs/superpowers/` kelak dihapus.
* [x] **Fungsionalitas Review & QA:** Menyediakan rujukan berkas yang jelas dan langkah pengujian yang langsung dapat dieksekusi oleh developer.
