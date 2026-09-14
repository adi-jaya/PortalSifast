# 🌐 Modul 13: Portal Pelaporan Eksternal Pemerintah & Custom Browser Extension Autofill

Modul ini mendokumentasikan subsistem **Portal Pelaporan Eksternal & Custom Browser Extension Autofill** pada SIMRS Sifast (RS Aisyiyah Siti Fatimah Tulangan). Subsistem ini menghubungkan staf rumah sakit dengan berbagai portal pelaporan resmi kementerian dan lembaga negara (Kemenkes RI dan BKKBN) melalui arsitektur hibrida: integrasi backend Laravel 12, frontend React 19 / Inertia.js v2, dan ekstensi browser Chromium Manifest V3 dengan prinsip keamanan **Zero-Persistence**.

---

## 📋 Informasi Metadata Modul

| Atribut | Nilai |
| :--- | :--- |
| **Kode Modul** | `MODUL-13-PORTAL-EKSTERNAL` |
| **Tech Stack Backend** | Laravel 12, PHP 8.2+, Eloquent ORM, Symmetric AES-256-CBC Encryption, Pest PHP |
| **Tech Stack Frontend** | React 19, TypeScript, Inertia.js v2, Tailwind CSS v4, Lucide React, Shadcn UI |
| **Browser Extension** | Chromium Manifest V3, Pure Vanilla ES2022+, Zero-Persistence RAM Queue, Node.js 22 Test Runner |
| **Status Implementasi** | **Plan 1 (Backend Core & DB):** `SELESAI (PASS)`<br/>**Plan 2 (Admin Portal & Mapping):** `SELESAI (PASS)`<br/>**Plan 3 (Browser Extension MV3):** `SIAP DIIMPLEMENTASIKAN`<br/>**Plan 4 (Halaman Pengguna & ZIP):** `TERENCANA` |
| **Total Cakupan Pengujian** | 62 Pest Test Cases (354 Assertions) — Seluruhnya Lulus (`PASS`) |
| **Relasi Modul Onboarding** | [Modul 01: Arsitektur & Tech Stack](./01-ARSITEKTUR-DAN-TECH-STACK.md), [Modul 02b: Frontend React Inertia](./02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md) |

---

## 📑 Daftar Isi (9 Bab Definitif)

- [Bab 1: 📌 Ikhtisar Sistem & Filosofi Desain](#bab-1--ikhtisar-sistem--filosofi-desain)
  - [1.1 Konteks Bisnis & Latar Belakang Pelaporan](#11-konteks-bisnis--latar-belakang-pelaporan)
  - [1.2 3 Pilar Keamanan & Filosofi Arsitektur](#12-3-pilar-keamanan--filosofi-arsitektur)
  - [1.3 Diagram Arsitektur Interaksi End-to-End](#13-diagram-arsitektur-interaksi-end-to-end)
- [Bab 2: 📊 Matriks Status Implementasi & Roadmap Modul](#bab-2--matriks-status-implementasi--roadmap-modul)
  - [2.1 Matriks Status 4 Rencana Modular](#21-matriks-status-4-rencana-modular)
  - [2.2 Matriks Fitur, Rute, Otorisasi, & Ketersediaan](#22-matriks-fitur-rute-otorisasi--ketersediaan)
- [Bab 3: 🗄️ Model Data & Skema Database (Plan 1 - Selesai)](#bab-3-️-model-data--skema-database-plan-1---selesai)
  - [3.1 Skema Tabel `portals` (Master Portal Eksternal)](#31-skema-tabel-portals-master-portal-eksternal)
  - [3.2 Spesifikasi Format JSON `form_config`](#32-spesifikasi-format-json-form_config)
  - [3.3 Skema Tabel `user_portal_credentials` (Mapping Akses & Akun Personal)](#33-skema-tabel-user_portal_credentials-mapping-akses--akun-personal)
  - [3.4 Enkripsi Simetris Kredensial & Proteksi Model Eloquent](#34-enkripsi-simetris-kredensial--proteksi-model-eloquent)
  - [3.5 Ringkasan 8 Kelompok Portal Resmi Bawaan Seeder (`PortalSeeder.php`)](#35-ringkasan-8-kelompok-portal-resmi-bawaan-seeder-portalseederphp)
  - [3.6 Panduan Uji Coba Cepat (Hands-on Verification Bab 3)](#36-panduan-uji-coba-cepat-hands-on-verification-bab-3)
- [Bab 4: ⚙️ Backend Core & Arsitektur Service Layer (Plan 1 - Selesai)](#bab-4-️-backend-core--arsitektur-service-layer-plan-1---selesai)
  - [4.1 Arsitektur Service Class Layer (`App\Services\Portal\*`)](#41-arsitektur-service-class-layer-appservicesportal)
  - [4.2 Defense-in-Depth Authorization & Middleware Perimeter](#42-defense-in-depth-authorization--middleware-perimeter)
  - [4.3 Kontrak API Endpoint Dispatch Token](#43-kontrak-api-endpoint-dispatch-token)
  - [4.4 Peta Berkas & Panduan Code Review (Plan 1)](#44-peta-berkas--panduan-code-review-plan-1)
  - [4.5 Panduan Uji Coba Cepat (Hands-on Verification Bab 4)](#45-panduan-uji-coba-cepat-hands-on-verification-bab-4)
- [Bab 5: 🖥️ Modul Admin SIMRS: Master Portal & Mapping Akses (Plan 2 - Selesai)](#bab-5-️-modul-admin-simrs-master-portal--mapping-akses-plan-2---selesai)
- [Bab 6: 🧩 Custom Browser Extension Manifest V3 (Plan 3 - Siap Diimplementasikan)](#bab-6--custom-browser-extension-manifest-v3-plan-3---siap-diimplementasikan)
- [Bab 7: 🚀 Modul Pengguna: Portal Agregator & Distribusi Ekstensi (Plan 4 - Terencana)](#bab-7--modul-pengguna-portal-agregator--distribusi-ekstensi-plan-4---terencana)
- [Bab 8: 🧪 Panduan Pengujian & Skenario Verifikasi (Master Testing Guide)](#bab-8--panduan-pengujian--skenario-verifikasi-master-testing-guide)
- [Bab 9: 🛠️ Runbook Operasional, Pemeliharaan & Troubleshooting](#bab-9-️-runbook-operasional-pemeliharaan--troubleshooting)

---

## Bab 1: 📌 Ikhtisar Sistem & Filosofi Desain

### 1.1 Konteks Bisnis & Latar Belakang Pelaporan

Sebagai institusi pelayanan kesehatan terakreditasi, Rumah Sakit Aisyiyah Siti Fatimah Tulangan memiliki kewajiban regulasi untuk menyampaikan data operasional, mutu, surveilans epidemiologi, dan demografi secara berkala kepada otoritas kesehatan Republik Indonesia. Platform eksternal pemerintah yang digunakan meliputi:

1. **Kementerian Kesehatan RI (Kemenkes):**
   - **SIRS Online:** Sistem Informasi Rumah Sakit Online Ditjen Yankes untuk pelaporan kapasitas tempat tidur, ketenagaan, dan morbiditas/mortalitas.
   - **SITB (Sistem Informasi Tuberkulosis):** Pencatatan dan pelaporan program penanggulangan tuberkulosis sensitif dan resisten obat tingkat Jawa Timur.
   - **SIHA 2.1 (Sistem Informasi HIV/AIDS dan IMS):** Surveilans dan pelaporan pengobatan HIV/AIDS, IMS, dan pencegahan penularan ibu ke anak (PPIA).
   - **MPDN (Maternal Perinatal Death Notification):** Pelaporan kematian maternal dan perinatal secara real-time guna audit maternal perinatal.
   - **SIGIZI Terpadu:** Sistem pemantauan status gizi balita, intervensi stunting, dan kesehatan keluarga.
   - **SATU SEHAT Platform:** Integrasi rekam medis elektronik nasional sesuai standar FHIR HL7.
   - **MutuFasyankes:** Pelaporan Indikator Nasional Mutu (INM), Insiden Keselamatan Pasien (IKP), Program Pengendalian Resistensi Antimikroba (PPRA), dan Sistem Informasi Manajemen Akreditasi RS (SIMAR).

2. **Badan Kependudukan dan Keluarga Berencana Nasional (BKKBN / Kemendukbangga):**
   - **SIRIKA:** Sistem Informasi Rekonsiliasi Intervensi Penurunan Stunting BKKBN.
   - **New SIGA:** Sistem Informasi Keluarga untuk pencatatan pelayanan kontrasepsi dan keluarga berencana.

#### Masalah Operasional Lapangan (*Pain Points*)
Sebelum subsistem ini dibangun, staf rumah sakit menghadapi kendala operasional yang berulang setiap hari:
* **Fragmentasi Akses & URL:** Terdapat puluhan URL login pemerintah yang terpisah. Staf mengandalkan bookmark browser acak di workstation masing-masing.
* **Risiko Kebocoran Akun:** Password sering dicatat di kertas tempel (*sticky notes*) di layar monitor atau lembar spreadsheet lokal yang tidak terenkripsi, memicu risiko pelanggaran kepatuhan keamanan informasi medis.
* **Rotasi Shift & Staf:** Pergantian jadwal dinas sering terhambat karena petugas jaga malam tidak memiliki kata sandi akun pelaporan terkini.
* **Inefisiensi Waktu:** Staf menghabiskan waktu signifikan untuk proses login berulang dan navigasi manual alih-alih fokus pada verifikasi validitas data medis yang dilaporkan.

#### Solusi Arsitektur Sifast
Subsistem Portal Pelaporan Eksternal menyelesaikan masalah ini secara menyeluruh melalui:
1. **Pusat Agregator SIMRS:** Satu dashboard internal terpadu di SIMRS yang hanya menampilkan portal-portal resmi sesuai penugasan dan hak akses masing-masing petugas.
2. **Penyimpanan Terenkripsi Terpusat:** Kredensial institusi dan personal disimpan di database SIMRS dengan enkripsi simetris standar industri (`AES-256-CBC`).
3. **Custom Chromium Extension Autofill:** Ekstensi browser ringan berstandar Google Manifest V3 yang menyuntikkan username dan password secara instan ke form web target tanpa pernah membocorkan password plaintext ke layar staf maupun ke hard disk browser.

---

### 1.2 3 Pilar Keamanan & Filosofi Arsitektur

Arsitektur modul ini dirancang dengan prinsip *Defense-in-Depth* berlandaskan 3 pilar utama:

```
+-----------------------------------------------------------------------------------+
|                            3 PILAR KEAMANAN MODUL 13                             |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [Pilar 1: Zero-Persistence RAM]       [Pilar 2: Hybrid Credential]              |
|  * Tidak ada chrome.storage / cookies  * Shared institutional account (Admin IT)  |
|  * Volatile Map di Service Worker RAM  * Personal staff account (Self-service)    |
|  * TTL 30s auto-expire + auto-flush    * Granular access control & audit trail    |
|                                                                                   |
|                      [Pilar 3: Native Synthetic Dispatcher]                      |
|                      * Bypass setter prototype (setNativeValue)                   |
|                      * Support React, Vue, Angular, jQuery, SPA                   |
|                      * Full bubbling: input -> change -> blur                     |
+-----------------------------------------------------------------------------------+
```

#### 1. Zero-Persistence Security (Kerahasiaan Mutlak Tanpa Jejak Persisten)
Kelemahan fatal pada password manager komersial atau skrip autofill sederhana adalah penyimpanan kredensial pada media penyimpanan lokal browser (`chrome.storage.local`, `chrome.storage.sync`, `localStorage`, atau Web Cookies). Jika workstation rumah sakit diakses bersama atau terserang malware lokal, kredensial tersebut dapat diekstrak secara mudah.

Pada subsistem ini:
- Ekstensi browser **SAMA SEKALI TIDAK MENGGUNAKAN** media penyimpanan persisten apapun.
- Kredensial hanya diterima saat pengguna secara sadar mengklik kartu portal di SIMRS melalui panggilan API berotorisasi (`POST /portal-pelaporan/{portal}/dispatch-token`).
- Token dan kredensial dikirimkan ke Background Service Worker ekstensi (`background.js`) dan disimpan secara eksklusif di dalam memori **RAM** volatile menggunakan struktur data `Map<tabId, credentialPayload>`.
- Diberlakukan batas waktu kedaluwarsa ketat (**Time-To-Live / TTL = 30 detik**). Jika tab target tidak terbuka atau gagal di-autofill dalam 30 detik, entri di RAM dihapus secara otomatis (*auto-expire*).
- Segera setelah Content Engine di halaman target selesai melakukan pengisian form, content script mengirimkan sinyal konsumsi selesai dan Service Worker **langsung menghapus bersih** entri kredensial dari RAM (`pendingTabs.delete(tabId)` / *auto-flush*).
- Jika pengguna menutup tab sebelum halaman termuat, listener darurat `chrome.tabs.onRemoved` memastikan memori RAM dibersihkan seketika.

#### 2. Hybrid Credential Model (Model Kredensial Hibrida)
Kebutuhan pelaporan institusi kesehatan memiliki dualisme karakteristik akun:
- **Akun Bersama Lembaga (*Shared Institutional Account*):** Digunakan untuk portal yang hanya memberikan 1 akun resmi per rumah sakit (contoh: SIRS Online Yankes, SIRIKA BKKBN). Password akun ini adalah aset institusi yang sangat sensitif. Password dikelola dan di-input terpusat oleh Administrator IT RS. Staf pelapor dapat melakukan login otomatis melalui ekstensi tanpa pernah mengetahui string kata sandi aslinya.
- **Akun Mandiri Staf (*Personal Account*):** Digunakan untuk portal yang mewajibkan kredensial individual berdasarkan NIK, SIP, atau akun email pribadi petugas (contoh: SIHA 2.1, MPDN Kemenkes). Administrator RS mengendalikan izin akses (mengizinkan staf membuka portal terkait), sedangkan username dan password pribadi diisi serta diperbarui secara mandiri oleh masing-masing staf (*self-service credential management*).
- Master portal mendukung fleksibilitas konfigurasi `auth_type`:
  - `shared`: Portal hanya mengizinkan akun bersama institusi.
  - `personal`: Portal hanya mengizinkan akun personal mandiri staf.
  - `both`: Portal mendukung fleksibilitas akun bersama maupun akun personal, ditentukan secara granular per individu petugas pada tabel mapping.

#### 3. Native Synthetic Event Dispatcher (`setNativeValue` Prototype Setter Bypass)
Mayoritas portal modern pemerintah (seperti SATU SEHAT dan New SIGA) dibangun menggunakan framework reaktif modern berbasis JavaScript (React, Vue, Angular). Framework-framework ini melakukan pembungkusan (*overriding*) terhadap setter properti `value` pada antarmuka DOM `HTMLInputElement`.

Jika ekstensi browser hanya menjalankan penugasan standar seperti:
```javascript
// PENDEKATAN SALAH (Gagal pada React / Vue / Angular):
inputElement.value = "username_rahasia";
```
State internal framework tidak akan pernah terpicu. Ketika tombol login diklik atau divalidasi, form akan tetap menganggap bidang input kosong (*validation error*).

Ekstensi Sifast mengatasi kendala ini secara elegan dengan memanggil descriptor setter prototipe asli browser:
```javascript
// PENDEKATAN TEPAT (Universal Form Compatibility):
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

    // Picu rangkaian synthetic events lengkap dengan propagasi bubbling
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
    element.dispatchEvent(new Event('blur', { bubbles: true }));
}
```
Metode ini menjamin 100% kompatibilitas baik pada portal HTML murni, jQuery klasik, maupun Single Page Application (SPA) reaktif.

---

### 1.3 Diagram Arsitektur Interaksi End-to-End

Diagram berikut mengilustrasikan siklus hidup peluncuran portal dan aliran kredensial dari backend SIMRS Sifast, melalui ekstensi browser, hingga berhasil terinjeksi ke formulir login web target pemerintah:

```mermaid
flowchart TD
    subgraph SIF_BE["SIMRS Sifast (Laravel 12 Backend)"]
        DB[("Database PostgreSQL/MySQL<br/>Tabel portals & user_portal_credentials<br/>(Password Terenkripsi AES-256-CBC)")]
        PDS["PortalDispatchService<br/>(Resolusi Kredensial & Dekripsi On-The-Fly)"]
        DTOKEN["Endpoint POST /portal-pelaporan/{portal}/dispatch-token<br/>(Throttle: 30/min, Policy: dispatchToken)"]
        DB --> PDS
        PDS --> DTOKEN
    end

    subgraph SIF_FE["SIMRS Sifast (React 19 & Inertia v2 Frontend)"]
        UI["Halaman /portal-pelaporan<br/>(Grid Kartu Portal & Indikator Ekstensi)"]
        CLICK["User Mengklik Kartu Portal"]
        API_CALL["Axios Request POST dispatch-token"]
        C_EVENT["Emisi DOM CustomEvent:<br/>'SIFAST_PORTAL_LAUNCH'<br/>(Target URL & Kredensial)"]
        UI --> CLICK
        CLICK --> API_CALL
        API_CALL -.->|HTTPS POST Request| DTOKEN
        DTOKEN -.->|JSON Response One-Time Kredensial| API_CALL
        API_CALL --> C_EVENT
    end

    subgraph EXT_BRIDGE["Content Bridge Ekstensi (content-simrs.js)"]
        HANDSHAKE["DOM Dataset Handshake:<br/>html[data-sifast-extension-installed='true']"]
        EVT_LISTEN["Event Listener DOM 'SIFAST_PORTAL_LAUNCH'"]
        MSG_EXT["chrome.runtime.sendMessage:<br/>{ action: 'LAUNCH_PORTAL', payload }"]
        HANDSHAKE -.->|Inisialisasi Badge Aktif| UI
        C_EVENT --> EVT_LISTEN
        EVT_LISTEN --> MSG_EXT
    end

    subgraph EXT_BG["Background Service Worker (background.js)"]
        RAM_QUEUE[("Volatile RAM Map (Zero-Persistence)<br/>pendingTabs.set(targetTabId, payload)<br/>TTL: 30 Detik Auto-Expire")]
        NEW_TAB["Buka Tab Baru Browser Target<br/>chrome.tabs.create({ url: targetUrl })"]
        MSG_EXT --> NEW_TAB
        NEW_TAB --> RAM_QUEUE
    end

    subgraph EXT_AUTOFILL["Content Engine Target (content-autofill.js)"]
        REQ_CRED["Query Kredensial via chrome.runtime.sendMessage:<br/>{ action: 'GET_CREDENTIALS', tabId }"]
        FIND_DOM["Resolusi Selector DOM Form Target<br/>(form_config Priority Chain / Heuristic Scanner)"]
        SET_NATIVE["Bypass Setter Framework:<br/>setNativeValue(input, value)<br/>Dispatch Events: input, change, blur"]
        FLUSH_RAM["chrome.runtime.sendMessage:<br/>{ action: 'CREDENTIALS_CONSUMED' }"]
        FOCUS_CAPTCHA["Auto-Focus ke Input CAPTCHA<br/>(Jika Ditemukan / Submit Manual)"]
        
        REQ_CRED -.->|Ambil Payload Berbasis tabId| RAM_QUEUE
        RAM_QUEUE -.->|Kirim Payload Sekali Pakai| REQ_CRED
        REQ_CRED --> FIND_DOM
        FIND_DOM --> SET_NATIVE
        SET_NATIVE --> FLUSH_RAM
        FLUSH_RAM -.->|Hapus Bersih dari RAM: pendingTabs.delete(tabId)| RAM_QUEUE
        SET_NATIVE --> FOCUS_CAPTCHA
    end

    subgraph TARGET_WEB["Platform Pelaporan Resmi Pemerintah"]
        LOGIN_FORM["Halaman Login Portal Target<br/>(Kemenkes / BKKBN / dsb)"]
        SET_NATIVE -->|Injeksi Otomatis Nilai Input| LOGIN_FORM
    end
```

---

## Bab 2: 📊 Matriks Status Implementasi & Roadmap Modul

### 2.1 Matriks Status 4 Rencana Modular

Pengembangan subsistem Portal Pelaporan Eksternal dibagi menjadi 4 tahapan (*Implementation Plans*). Tabel di bawah ini merangkum status ketersediaan setiap rencana kerja:

| Plan | Nama Rencana Kerja | Ruang Lingkup Fungsional | Status Saat Ini | Hasil Verifikasi |
| :--- | :--- | :--- | :--- | :--- |
| **Plan 1** | **Fondasi Backend, Database, Service Layer, & Otorisasi** | Skema tabel `portals` & `user_portal_credentials`, Model Eloquent terenkripsi, `PortalDispatchService`, `PortalPersonalCredentialService`, `PortalPolicy`, Controller API, rute web, dan database seeder 8 kelompok portal. | **`TERIMPLEMENTASI (PASS)`** | 29 Pest Tests Lulus (100% PASS) |
| **Plan 2** | **Modul Admin SIMRS: Master Portal & Mapping Akses Dual-View** | `AdminPortalService`, `AdminPortalMappingService`, `AdminPortalController`, `AdminPortalMappingController`, Halaman React/Inertia (`Index.tsx`, `Form.tsx`, `Mapping.tsx`), visual `FormConfigEditor`, `SelectorTagInput`, instant auto-save row, bulk sync. | **`TERIMPLEMENTASI (PASS)`** | 33 Pest Tests Tambahan Lulus (Total 62 Tests PASS) |
| **Plan 3** | **Custom Browser Extension Chromium Manifest V3 (`rs-extension/`)** | Berkas manifes `manifest.json` (MV3), Background Service Worker (`background.js`) dengan RAM queue berbasis `tabId` (TTL 30s), Content Bridge SIMRS (`content-simrs.js`), Content Engine Target (`content-autofill.js`) dengan `setNativeValue` & fallback heuristic scanner, UI popup inspector (`popup/`), suite pengujian unit Node.js 22. | **`SIAP DIIMPLEMENTASIKAN`** | Spesifikasi lengkap & siap dieksekusi |
| **Plan 4** | **Halaman Pengguna: Portal Agregator & Distribusi Ekstensi** | Halaman `/portal-pelaporan` untuk seluruh staf rumah sakit, pencarian live & filter kategori, kartu interaktif dengan deteksi ekstensi (badge hijau status aktif / banner panduan unduh), modal self-service update password pribadi, endpoint download file ZIP ekstensi terkompresi. | **`TERENCANA`** | Desain UI dan spesifikasi teknis telah disetujui |

---

### 2.2 Matriks Fitur, Rute, Otorisasi, & Ketersediaan

Berikut adalah peta rute HTTP, controller penanggung jawab, middleware pengamanan, otorisasi Gate/Policy, dan status operasional sistem:

| Fitur & Kegunaan | HTTP Method & URL | Controller & Action | Middleware & Policy Gate | Status Modul |
| :--- | :--- | :--- | :--- | :--- |
| **Pengambilan Kredensial Sekali Pakai (One-Time Dispatch Token)** | `POST /portal-pelaporan/{portal}/dispatch-token` | [`PortalDispatchController@dispatch`](../../app/Http/Controllers/PortalDispatchController.php) | `auth`, `verified`, `throttle:30,1`, `can:dispatchToken,portal` | Selesai (Plan 1) |
| **Pembaruan Mandiri Password Personal Staf (Self-Service)** | `PUT /portal-pelaporan/{portal}/personal-credentials` | [`PortalPersonalCredentialController@update`](../../app/Http/Controllers/PortalPersonalCredentialController.php) | `auth`, `verified`, `throttle:30,1`, `can:updatePersonalCredential,portal` | Selesai (Plan 1) |
| **Daftar Master Portal (Admin)** | `GET /admin/portals` | [`AdminPortalController@index`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Form Tambah Master Portal** | `GET /admin/portals/create` | [`AdminPortalController@create`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Simpan Master Portal Baru** | `POST /admin/portals` | [`AdminPortalController@store`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Form Edit Master Portal** | `GET /admin/portals/{portal}/edit` | [`AdminPortalController@edit`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Update Data Master Portal** | `PUT /admin/portals/{portal}` | [`AdminPortalController@update`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Hapus Master Portal (Soft/Cascade)** | `DELETE /admin/portals/{portal}` | [`AdminPortalController@destroy`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Toggle Status Aktif/Nonaktif Portal** | `PATCH /admin/portals/{portal}/toggle-active` | [`AdminPortalController@toggleActive`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Matriks Mapping Akses Petugas (Dual-View)** | `GET /admin/portals/mapping` | [`AdminPortalMappingController@index`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Simpan Baris Tunggal Instant Auto-Save** | `POST /admin/portals/mapping/save-row` | [`AdminPortalMappingController@saveRow`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Sinkronisasi Massal Portal ke Banyak Petugas** | `POST /admin/portals/mapping/sync-portal` | [`AdminPortalMappingController@syncPortal`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Sinkronisasi Massal Petugas ke Banyak Portal** | `POST /admin/portals/mapping/sync-user` | [`AdminPortalMappingController@syncUser`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Update Kredensial Tunggal Mapping** | `PATCH /admin/portals/mapping/{credential}` | [`AdminPortalMappingController@updateCredential`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Cabut Akses Kredensial Mapping** | `DELETE /admin/portals/mapping/{credential}` | [`AdminPortalMappingController@destroyCredential`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `auth`, `verified`, `can:manage,App\Models\Portal` | Selesai (Plan 2) |
| **Halaman Agregator Portal Pengguna** | `GET /portal-pelaporan` | `PortalPelaporanController@index` | `auth`, `verified`, `can:viewAny,App\Models\Portal` | Terencana (Plan 4) |
| **Unduh Berkas ZIP Paket Ekstensi** | `GET /portal-pelaporan/extension/download` | `PortalExtensionDownloadController@download` | `auth`, `verified` | Terencana (Plan 4) |

---

## Bab 3: 🗄️ Model Data & Skema Database (Plan 1 - Selesai)

Skema database dirancang dengan normalisasi relasional tinggi, integritas referensial penuh (*Foreign Key Cascadable*), dan proteksi enkripsi kolom otomatis pada lapisan Eloquent Model.

```mermaid
erDiagram
    USERS ||--o{ USER_PORTAL_CREDENTIALS : "has mapped credentials"
    PORTALS ||--o{ USER_PORTAL_CREDENTIALS : "assigned to users"

    PORTALS {
        bigint id PK
        varchar name "Nama Portal (150)"
        varchar slug UK "URL Friendly Slug (150)"
        varchar category "Kategori (100)"
        text url "URL Target Login"
        varchar url_pattern "URL Match Pattern (255)"
        varchar icon_path "Icon / Logo Path (255)"
        text description "Deskripsi Pelaporan"
        enum auth_type "shared, personal, both"
        varchar shared_username "Username Institusi"
        text shared_password "Encrypted AES-256"
        json shared_extra_fields "Atribut Tambahan"
        json form_config "DOM Selectors & Rules"
        boolean is_active "Status Aktif"
        integer sort_order "Prioritas Tampilan"
        timestamp created_at
        timestamp updated_at
    }

    USER_PORTAL_CREDENTIALS {
        bigint id PK
        bigint user_id FK "Cascade on Delete"
        bigint portal_id FK "Cascade on Delete"
        enum credential_type "use_shared, personal"
        varchar personal_username "Username Staf"
        text personal_password "Encrypted AES-256"
        json personal_extra_fields "Atribut Kustom Staf"
        boolean is_active "Hak Akses Staf Aktif"
        varchar notes "Catatan Penugasan Staf"
        timestamp created_at
        timestamp updated_at
    }
```

---

### 3.1 Skema Tabel `portals` (Master Portal Eksternal)

Migrasi database didefinisikan pada [`database/migrations/2026_09_09_100000_create_portals_table.php`](../../database/migrations/2026_09_09_100000_create_portals_table.php). Tabel ini menyimpan master informasi seluruh website pelaporan eksternal:

| Nama Kolom | Tipe Data & Modifier | Default | Deskripsi & Nilai Contoh |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | *None* | Primary Key unik portal. |
| `name` | `VARCHAR(150)` | *None* | Nama resmi portal pelaporan (contoh: `"SIRIKA (BKKBN)"`, `"SIHA 2.1 (Kemenkes)"`). |
| `slug` | `VARCHAR(150) UNIQUE` | *None* | Identifier ramah URL untuk route-model binding (contoh: `"sirika-bkkbn"`). Diisi otomatis via slug generator jika kosong. |
| `category` | `VARCHAR(100)` | *None* | Pengelompokan portal untuk tab filter di UI (contoh: `"Kemenkes"`, `"BKKBN"`, `"Mutu & Akreditasi"`). |
| `url` | `TEXT` | *None* | Alamat URL lengkap halaman login website target (contoh: `"https://siga-sirika.bkkbn.go.id/login"`). |
| `url_pattern` | `VARCHAR(255) NULLABLE` | `null` | Pola pencocokan URL untuk izin ekstensi browser (contoh: `"*://siga-sirika.bkkbn.go.id/*"`). |
| `icon_path` | `VARCHAR(255) NULLABLE` | `null` | Path lokasi berkas logo lokal atau nama identifier ikon Lucide React. |
| `description` | `TEXT NULLABLE` | `null` | Penjelasan fungsi pelaporan dan unit kerja yang bertanggung jawab. |
| `auth_type` | `ENUM('shared', 'personal', 'both')` | `'both'` | Tipe kredensial yang didukung: akun bersama, akun individu, atau keduanya. |
| `shared_username` | `VARCHAR(255) NULLABLE` | `null` | Username akun bersama institusi rumah sakit. |
| `shared_password` | `TEXT NULLABLE` | `null` | Kata sandi akun bersama terenkripsi simetris `AES-256-CBC` via Laravel `Crypt`. |
| `shared_extra_fields` | `JSON NULLABLE` | `null` | Pasangan data tambahan berbentuk JSON (misal kode registrasi RS, role selector, token instansi). |
| `form_config` | `JSON NULLABLE` | `null` | Spesifikasi DOM selector dan parameter autofill yang dikonsumsi oleh ekstensi browser. |
| `is_active` | `BOOLEAN` | `true` | Flag ketersediaan portal. Jika `false`, portal dinonaktifkan dari seluruh staf. |
| `sort_order` | `INTEGER` | `0` | Urutan tampilan prioritas kartu portal pada halaman agregator. |
| `created_at` | `TIMESTAMP NULLABLE` | `null` | Jejak audit waktu pembuatan entri. |
| `updated_at` | `TIMESTAMP NULLABLE` | `null` | Jejak audit waktu modifikasi terakhir. |

---

### 3.2 Spesifikasi Format JSON `form_config`

Kolom `form_config` pada tabel `portals` menyimpan aturan pencocokan elemen DOM formulir login website target. Format JSON ini dibaca oleh ekstensi browser Chromium untuk mengeksekusi autofill yang akurat:

```json
{
  "is_spa": false,
  "wait_timeout_ms": 10000,
  "username_field": {
    "selectors": [
      "#c",
      "input[name='email']",
      "input[name='username']",
      "input[type='email']",
      "input[type='text']"
    ]
  },
  "password_field": {
    "selectors": [
      "#password",
      "input[name='password']",
      "input[type='password']"
    ]
  },
  "extra_fields": [
    {
      "name": "hospital_code",
      "selectors": [
        "#fasyankes_id",
        "input[name='hospital_code']"
      ]
    }
  ],
  "auto_submit": false
}
```

#### Rincian Struktur Atribut `form_config`:
1. **`is_spa` (`boolean`):**
   - Menandai apakah portal target merupakan Single Page Application (SPA) berbasis routing client-side seperti React, Vue, atau Angular.
   - Jika bernilai `true`, Content Engine ekstensi akan menggunakan `MutationObserver` atau penundaan cerdas (*smart polling*) untuk menunggu elemen form siap dirender ke DOM sebelum mencoba melakukan injeksi.
2. **`wait_timeout_ms` (`integer`, standar: `10000` ms / 10 detik):**
   - Batas waktu toleransi maksimal bagi ekstensi untuk menunggu kemunculan elemen form di layar sebelum menghentikan pencarian dan mencatat status gagal.
3. **`username_field.selectors` (`array of string`):**
   - Rantai prioritas CSS selector (*fallback priority chain*). Ekstensi mengevaluasi selector dari indeks pertama (paling spesifik, misal `#id`) hingga selector umum. Elemen pertama yang ditemukan di DOM akan digunakan sebagai target injeksi username.
4. **`password_field.selectors` (`array of string`):**
   - Rantai prioritas CSS selector untuk bidang kata sandi (contoh: `["#password", "input[type='password']"]`).
5. **`extra_fields` (`array of objects`, opsional):**
   - Digunakan jika formulir login target membutuhkan input tambahan di luar username dan password biasa, seperti kode Fasyankes Kemenkes, identitas wilayah, atau opsi captcha statis.
6. **`auto_submit` (`boolean`, standar: `false`):**
   - Menentukan apakah form akan langsung di-submit secara otomatis setelah nilai terisi.
   - **Rekomendasi Keamanan:** Selalu disetel `false` jika website target memiliki tantangan verifikasi visual (Google reCAPTCHA, Cloudflare Turnstile, captcha angka acak) atau memerlukan konfirmasi pengguna sebelum masuk.

---

### 3.3 Skema Tabel `user_portal_credentials` (Mapping Akses & Akun Personal)

Migrasi database didefinisikan pada [`database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php`](../../database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php). Tabel ini berfungsi ganda: sebagai tabel relasi *many-to-many* pemberian hak akses staf ke portal, sekaligus sebagai media penyimpanan kredensial akun personal mandiri:

| Nama Kolom | Tipe Data & Modifier | Default | Deskripsi & Nilai Contoh |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | *None* | Primary Key baris relasi mapping. |
| `user_id` | `BIGINT UNSIGNED` | *None* | Foreign Key merujuk ke `users(id)` dengan relasi `cascadeOnDelete()`. |
| `portal_id` | `BIGINT UNSIGNED` | *None* | Foreign Key merujuk ke `portals(id)` dengan relasi `cascadeOnDelete()`. |
| `credential_type` | `ENUM('use_shared', 'personal')` | `'use_shared'` | Menentukan apakah user menggunakan akun bersama RS atau akun pribadinya. |
| `personal_username` | `VARCHAR(255) NULLABLE` | `null` | Username pribadi staf (hanya relevan jika `credential_type = 'personal'`). |
| `personal_password` | `TEXT NULLABLE` | `null` | Kata sandi pribadi staf terenkripsi simetris `AES-256-CBC` via Laravel `Crypt`. |
| `personal_extra_fields` | `JSON NULLABLE` | `null` | Konfigurasi tambahan khusus milik akun staf terkait. |
| `is_active` | `BOOLEAN` | `true` | Switch status hak akses staf ke portal terkait. Jika `false`, staf dilarang mengakses. |
| `notes` | `VARCHAR(255) NULLABLE` | `null` | Catatan administratif penugasan (contoh: `"PJ Pelaporan VK"`, `"Dinas Rawat Inap"`). |
| `created_at` | `TIMESTAMP NULLABLE` | `null` | Jejak audit waktu pemberian hak akses. |
| `updated_at` | `TIMESTAMP NULLABLE` | `null` | Jejak audit modifikasi terakhir hak akses / password personal. |

#### Batasan Integritas Data & Unik (*Database Constraints*):
- **Constraint Unik Pasangan:** `$table->unique(['user_id', 'portal_id']);`
  Menjamin integritas data: satu staf rumah sakit hanya memiliki tepat satu baris konfigurasi mapping per portal. Tidak mungkin terjadi duplikasi mapping pada satu portal untuk pengguna yang sama.
- **Cascade on Delete:** Jika suatu entri portal pada tabel `portals` dihapus, seluruh baris mapping terkait di tabel `user_portal_credentials` akan otomatis terhapus secara bersih oleh DBMS engine (*foreign key cascade*). Demikian pula jika akun user dihapus dari tabel `users`.

---

### 3.4 Enkripsi Simetris Kredensial & Proteksi Model Eloquent

Keamanan kata sandi adalah prioritas mutlak arsitektur subsistem ini. Karena ekstensi browser membutuhkan teks asli kata sandi untuk disuntikkan ke dalam form web target, kata sandi **TIDAK BISA DI-HASH** menggunakan algoritma satu arah seperti Bcrypt atau Argon2. Sebagai gantinya, diterapkan enkripsi simetris berkekuatan tinggi:

#### 1. Mekanisme Enkripsi Simetris Otomatis Laravel
Pada model [`App\Models\Portal`](../../app/Models/Portal.php) dan [`App\Models\UserPortalCredential`](../../app/Models/UserPortalCredential.php), atribut password dideklarasikan menggunakan casting `'encrypted'` pada method `casts()`:

```php
// app/Models/Portal.php
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

// app/Models/UserPortalCredential.php
protected function casts(): array
{
    return [
        'personal_password' => 'encrypted',
        'personal_extra_fields' => 'array',
        'is_active' => 'boolean',
    ];
}
```

* **Saat Menyimpan ke Database (`INSERT` / `UPDATE`):** Eloquent secara transparan mengenkripsi string kata sandi menggunakan `Illuminate\Support\Facades\Crypt::encryptString()` menggunakan algoritma **AES-256-CBC** yang diautentikasi dengan Message Authentication Code (**HMAC-SHA256**) berbasis `APP_KEY`. Nilai yang tersimpan di kolom fisik database adalah payload JSON yang di-encode Base64 (`{"iv":"...","value":"...","mac":"..."}`).
* **Saat Membaca dari Database:** Eloquent otomatis mendekripsi ciphertext menjadi string asli hanya ketika properti `$portal->shared_password` atau `$credential->personal_password` diakses langsung dalam kode PHP backend.

#### 2. Pencegahan Kebocoran Payload via Properti `$hidden`
Untuk mencegah kebocoran kredensial plaintext ke dalam serialisasi response (seperti response JSON Inertia `page.props`, response API collection, atau log debugging):
```php
protected $hidden = [
    'shared_password', // Pada Model Portal
];

protected $hidden = [
    'personal_password', // Pada Model UserPortalCredential
];
```
Dengan deklarasi ini, fungsi serialisasi seperti `toArray()` atau `json_encode()` pada model akan otomatis **menghilangkan kolom password** dari hasil output. Kata sandi hanya didekripsi secara khusus oleh [`PortalDispatchService`](../../app/Services/Portal/PortalDispatchService.php) ketika terjadi pemanggilan resmi ke rute `POST /portal-pelaporan/{portal}/dispatch-token`.

---

### 3.5 Ringkasan 8 Kelompok Portal Resmi Bawaan Seeder (`PortalSeeder.php`)

Database Seeder [`database/seeders/PortalSeeder.php`](../../database/seeders/PortalSeeder.php) menyediakan konfigurasi 8 kelompok portal pelaporan resmi pemerintah (mencakup 11 entri portal) yang telah dilengkapi dengan URL, pola URL, tipe otorisasi, dan CSS selector formulir login:

| No | Nama Portal Resmi | Slug | Kategori | Jenis Akun | Tipe Web (SPA?) | Karakteristik Selector Form Login |
| :---: | :--- | :--- | :--- | :---: | :---: | :--- |
| **1** | **SIRIKA (BKKBN)** | `sirika-bkkbn` | BKKBN | Shared | Non-SPA | Input username: `#c`, `input[name='email']`<br/>Password: `#password` |
| **2** | **New SIGA (Kemendukbangga)** | `siga-kemendukbangga` | BKKBN | Shared | **SPA** (Hash route) | URL: `.../#/login`<br/>Input: `#email`, `#password` |
| **3** | **SIHA 2.1 (Kemenkes)** | `siha-kemenkes` | Kemenkes | Both | Non-SPA | Input: `#username`, `input[name='user']`<br/>Password: `#password` |
| **4** | **MPDN (Kemenkes)** | `mpdn-kemenkes` | Kemenkes | Both | Non-SPA | Input: `#username`, `input[name='email']`<br/>Password: `#password` |
| **5** | **SITB Jatim (Kemenkes)** | `sitb-kemenkes` | Kemenkes | Both | Non-SPA | Input: `input[name='username']`, `#username`<br/>Password: `#password` |
| **6** | **SIGIZI Terpadu (Kemenkes)** | `sigizi-kemenkes` | Kemenkes | Both | Non-SPA | Input: `#username`, `input[name='username']`<br/>Password: `#password` |
| **7** | **SATU SEHAT Platform (Kemenkes)** | `satusehat-kemenkes` | Kemenkes | Both | **SPA** | Input: `#email`, `input[type='email']`<br/>Password: `#password` |
| **8** | **MutuFasyankes - IKP** | `mutufasyankes-ikp` | Mutu & Akreditasi | Both | Non-SPA | Input: `#user`, `input[name='user']`<br/>Password: `#pass` |
| **9** | **MutuFasyankes - PPRA** | `mutufasyankes-ppra` | Mutu & Akreditasi | Both | Non-SPA | Input: `input[name='username']`, `#username`<br/>Password: `input[name='password']` |
| **10** | **MutuFasyankes - SIMAR** | `mutufasyankes-simar` | Mutu & Akreditasi | Both | Non-SPA | Input: `#uname`, `input[name='uname']`<br/>Password: `#pwd` |
| **11** | **SIRS Online (Yankes)** | `sirs-online` | Kemenkes | Shared | **SPA** | Input: `input[type='email']`, `input[placeholder*='email' i]`<br/>Password: `input[type='password']` |

> [!NOTE]
> Seluruh password akun bersama bawaan diisi nilai placeholder aman `"GantiPasswordSegera!"`. Administrator IT wajib memperbarui password institusi melalui modul admin SIMRS (`/admin/portals/{portal}/edit`) sebelum modul digunakan di lingkungan produksi.

---

### 3.6 Panduan Uji Coba Cepat (Hands-on Verification Bab 3)

Developer dapat memverifikasi integritas skema database, foreign key cascade, dan mekanisme enkripsi model kapan saja menggunakan pengujian otomatis Pest PHP bawaan:

```bash
# Uji coba integritas skema tabel database portals dan user_portal_credentials
php artisan test tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php

# Uji coba enkripsi otomatis, proteksi $hidden, dan relasi Eloquent Model
php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php

# Uji coba idempotensi dan data bawaan database seeder
php artisan test tests/Feature/PortalPelaporan/PortalSeederTest.php
```

*Untuk panduan menyeluruh seluruh 62 skenario pengujian otomatis dan manual, lihat [Bab 8: 🧪 Panduan Pengujian & Skenario Verifikasi](#bab-8--panduan-pengujian--skenario-verifikasi-master-testing-guide).*

---

## Bab 4: ⚙️ Backend Core & Arsitektur Service Layer (Plan 1 - Selesai)

**Status Implementasi:** `[STATUS: SELESAI (Plan 1 - PASS)]`

Bab ini membahas arsitektur backend inti (*backend core*) subsistem Portal Pelaporan Eksternal SIMRS Sifast. Arsitektur ini dibangun dengan memisahkan secara tegas antara lapisan transport HTTP (*Controllers & Requests*), logika bisnis aplikasi (*Concrete Service Layer*), dan kebijakan keamanan akses data (*Policies & Middleware Perimeter*).

```
+----------------------------------------------------------------------------------------------------+
|                         ARSITEKTUR BACKEND CORE PORTAL PELAPORAN EKSTERNAL                         |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [HTTP Request]                                                                                    |
|        │                                                                                           |
|        ▼                                                                                           |
|  [Route Middleware Perimeter] ───► can:manage,App\Models\Portal (routes/web.php)                   |
|        │                                                                                           |
|        ▼                                                                                           |
|  [Form Request Validation]   ───► PortalRequest / SaveMappingRowRequest / etc.                     |
|        │                                                                                           |
|        ▼                                                                                           |
|  [Thin Controllers]          ───► AdminPortalController / AdminPortalMappingController /           |
|        │                          PortalDispatchController / PortalPersonalCredentialController    |
|        │                          (Otorisasi: Gate::authorize('manage' / 'dispatchToken', ...))    |
|        ▼                                                                                           |
|  [Service Class Layer]       ───► App\Services\Portal\*                                            |
|        ├── AdminPortalService              ──► CRUD Master, Auto-Slug, Password Preservation       |
|        ├── AdminPortalMappingService       ──► Dual-View Matrix, DB::transaction(), Note Preservation |
|        ├── PortalDispatchService           ──► Decrypt-on-the-fly, Hybrid Credential Resolution    |
|        └── PortalPersonalCredentialService ──► Self-Service Staff Credential Updates               |
|        │                                                                                           |
|        ▼                                                                                           |
|  [Eloquent Models]           ──► Portal / UserPortalCredential / User                              |
|                                   (Casts: 'encrypted' AES-256-CBC, $hidden password)               |
+----------------------------------------------------------------------------------------------------+
```

---

### 4.1 Arsitektur Service Class Layer (`App\Services\Portal\*`)

Untuk mempertahankan standar kode yang bersih, mudah diuji (*testable*), dan mudah dipelihara, SIMRS Sifast menerapkan prinsip **Thin Controllers, Rich Services**. Seluruh kontroler pada modul portal dilarang memuat kueri database yang kompleks, transaksi multi-tabel, maupun logika enkripsi langsung. Tugas kontroler murni terbatas pada menerima *Form Request*, memicu otorisasi *Gate*, memanggil metode pada *Service Class*, dan mengembalikan respon (*Inertia View*, *JSON*, atau *HTTP Redirect*).

Semua service class diinjeksikan ke dalam kontroler menggunakan **Constructor Property Promotion** (PHP 8.2+):
```php
public function __construct(
    private AdminPortalService $portalService,
) {}
```

Subsistem ini digerakkan oleh 4 *concrete service class* yang terspesialisasi di direktori [`app/Services/Portal/`](../../app/Services/Portal/):

---

#### 1. `AdminPortalService` ([`app/Services/Portal/AdminPortalService.php`](../../app/Services/Portal/AdminPortalService.php))
Service ini mengelola siklus hidup (*lifecycle*) data master portal eksternal pada tabel `portals`:

* **`paginatePortals(array $filters = []): array`**
  - Mengambil data portal dengan relasi agregat `withCount('userCredentials')` untuk menampilkan jumlah petugas yang memiliki akses.
  - **Pencarian Multi-Kolom Aman:** Menerapkan filter pencarian teks pada kolom `name`, `category`, dan `url`. Karakter wildcard SQL seperti `%` dan `_` disanitasi menggunakan `addcslashes($search, '%_\\')` untuk mencegah manipulasi query:
    ```php
    $escaped = addcslashes($search, '%_\\');
    $query->where(function ($q) use ($escaped) {
        $q->where('name', 'like', "%{$escaped}%")
            ->orWhere('category', 'like', "%{$escaped}%")
            ->orWhere('url', 'like', "%{$escaped}%");
    });
    ```
  - **Filter Kategori & Status:** Mendukung pemfilteran kategori spesifik (mengabaikan opsi default `_all`) dan status ketersediaan portal (`active` / `inactive`).
  - **Paginasi & Transformasi Koleksi:** Mengurutkan data via scope model `ordered()` (`sort_order ASC`, `name ASC`) dan membatasi 15 item per halaman (`paginate(15)->withQueryString()`). Menggunakan method `through()` untuk membentuk array aman bagi frontend React tanpa membocorkan kata sandi:
    ```php
    'has_shared_password' => filled($portal->shared_password),
    ```
  - Mengembalikan array berisi objek paginator `portals`, koleksi unik `categories` (`Portal::distinct()->pluck('category')`), dan array `filters` aktif.

* **`getFormData(): array`**
  - Menyediakan nilai default konfigurasi DOM selector `default_form_config` saat admin membuka form penambahan portal baru:
    ```php
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
    ```
  - Mengambil daftar kategori unik aktif dari database untuk opsi auto-complete / dropdown pada form.

* **`storePortal(array $data): Portal`**
  - **Auto-Generation Slug:** Jika field `slug` dikosongkan oleh pengguna, service otomatis menghasilkan slug ramah URL berbasis nama portal: `Str::slug((string) $data['name'])`.
  - **Auto Sort Order:** Jika `sort_order` tidak disetel, service menghitung urutan baru dengan rumus: `(Portal::max('sort_order') ?? 0) + 10`.
  - **Fallback Form Config:** Memastikan atribut `form_config` terisi dengan konfigurasi dasar yang valid jika request tidak menyertakannya.
  - Menyimpan entri baru via `Portal::create($data)`.

* **`formatPortalForEdit(Portal $portal): array`**
  - Mempersiapkan atribut portal untuk dioper ke form builder edit Inertia React.
  - Menjaga prinsip **Zero-Leakage**: Nilai `shared_password` tidak dikirim ke form frontend, melainkan digantikan dengan indikator boolean `has_shared_password => filled($portal->shared_password)`.

* **`updatePortal(Portal $portal, array $data): Portal`**
  - **Proteksi Password Kosong Saat Update (*Password Preservation*):**
    ```php
    if (! filled($data['shared_password'] ?? null)) {
        unset($data['shared_password']);
    }

    $portal->update($data);
    ```
    Jika administrator tidak memasukkan teks baru pada input kata sandi bersama (dibiarkan kosong), kunci `shared_password` otomatis dihapus dari array sebelum operasi update dieksekusi. Dengan logika ini, ciphertext kata sandi lama yang telah tersimpan di database **tidak akan pernah tertimpa atau terhapus secara tidak sengaja**.

* **`toggleActive(Portal $portal): bool`**
  - Membalik status keaktifan portal (`$portal->update(['is_active' => ! $portal->is_active])`) dan mengembalikan status boolean baru.

* **`destroyPortal(Portal $portal): bool`**
  - Menghapus rekaman portal dari tabel `portals`. Berkat constraint database `onDelete('cascade')`, seluruh rekaman hak akses staf terkait pada tabel `user_portal_credentials` akan terhapus secara otomatis oleh engine DBMS.

---

#### 2. `AdminPortalMappingService` ([`app/Services/Portal/AdminPortalMappingService.php`](../../app/Services/Portal/AdminPortalMappingService.php))
Service ini mengelola matriks otorisasi penugasan staf dan hak akses portal eksternal:

* **`getMappingData(?int $selectedPortalId, ?int $selectedUserId, string $viewMode = 'portal', array $filters = []): array`**
  - Mendukung dua mode visualisasi data:
    1. **Mode Portal-Centric (`viewMode = 'portal'`):** Memilih 1 portal target, lalu menampilkan daftar seluruh staf RS dengan status akses masing-masing terhadap portal tersebut.
    2. **Mode User-Centric (`viewMode = 'user'`):** Memilih 1 staf RS target, lalu menampilkan daftar seluruh portal eksternal dengan status akses staf tersebut ke masing-masing portal.
  - **Filter Staf Fleksibel:** Menyaring pengguna berdasarkan pencarian nama, email, atau NIK SIMRS (`simrs_nik`), filter departemen/unit kerja (`dep_id`), dan role.
  - **Paginasi & Optimasi Query:** Menghasilkan koleksi staf terpaginasi 50 user per halaman untuk tampilan tabel utama, serta koleksi `all_users` unpaginated (hanya kolom ID, nama, email, NIK, role, dan dep_id) untuk pencarian cepat pada dropdown selector.
  - **Indexing Akses O(1):** Mengambil mapping hak akses aktif dan mengindeksnya dengan `keyBy('user_id')` (pada mode portal) atau `keyBy('portal_id')` (pada mode user) sehingga komponen frontend dapat memeriksa status akses secara instan tanpa iterasi linier.

* **`syncPortalUsers(Portal $portal, array $assignments): void` & `syncUserPortals(User $user, array $assignments): void`**
  - **Integritas Transaksi DB Massal (`DB::transaction()`):**
    Operasi penyimpanan massal dibungkus secara utuh dalam transaksi basis data untuk menjamin kepatuhan ACID (*Atomicity, Consistency, Isolation, Durability*):
    ```php
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
    ```
    Jika terjadi kegagalan jaringan atau galat database di tengah iterasi ratusan pengguna, sistem otomatis me-rollback seluruh operasi tanpa meninggalkan data mapping yang menggantung atau inkonsisten.

* **`saveSingleAssignment(int $portalId, int $userId, bool $hasAccess, string $credentialType = 'use_shared', ?string $notes = null, ?bool $updateNotes = null): ?UserPortalCredential`**
  - Digunakan oleh endpoint *instant auto-save* baris tunggal (`POST /admin/portals/mapping/save-row`).
  - **Mekanisme Kritis Penanganan Parameter `$updateNotes`:**
    Di antarmuka admin, setiap baris tabel memiliki tiga elemen interaktif: switch toggle akses, dropdown tipe kredensial, dan input teks catatan tugas.
    
    ```php
    $shouldUpdateNotes = $updateNotes !== null ? $updateNotes : ($notes !== null);
    if ($shouldUpdateNotes) {
        $attributes['notes'] = ($notes !== null && trim($notes) !== '') ? trim($notes) : null;
    }
    ```

    Pada controller [`AdminPortalMappingController::saveRow()`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php), method ini dipanggil dengan menyematkan `$request->has('notes')` sebagai argumen `$updateNotes`:

    ```php
    $credential = $this->mappingService->saveSingleAssignment(
        (int) $request->validated('portal_id'),
        (int) $request->validated('user_id'),
        (bool) $request->validated('has_access'),
        (string) ($request->validated('credential_type') ?? 'use_shared'),
        $request->validated('notes'),
        $request->has('notes') // <--- Evaluasi kehadiran key 'notes'
    );
    ```

    | Skenario Pengguna di Frontend | Request Payload Dikirim | Nilai `$request->has('notes')` | Evaluasi `$shouldUpdateNotes` | Dampak pada Kolom `notes` di DB |
    | :--- | :--- | :---: | :---: | :--- |
    | **Admin mengklik toggle switch akses atau mengubah tipe akun** | `{ portal_id, user_id, has_access, credential_type }` *(key `notes` tidak ada)* | `false` | `false` | **Preserved:** Nilai catatan staf lama di database **TIDAK TERSENTUH**. Mencegah hilangnya catatan staf yang sudah ada secara tidak sengaja. |
    | **Admin mengubah teks catatan lalu blur input** | `{ portal_id, user_id, has_access, notes: "PJ Stunting" }` | `true` | `true` | **Updated:** Nilai catatan diperbarui menjadi `"PJ Stunting"`. |
    | **Admin menghapus seluruh isi catatan lalu blur input** | `{ portal_id, user_id, has_access, notes: "" }` atau `{ notes: null }` | `true` | `true` | **Cleared:** Nilai catatan di database secara sadar dikosongkan menjadi `null`. |

* **`updateCredential(UserPortalCredential $credential, array $data): UserPortalCredential` & `destroyCredential(UserPortalCredential $credential): bool`**
  - Layanan atomik untuk mengubah atribut baris mapping atau mencabut izin akses staf dari portal terkait.

---

#### 3. `PortalDispatchService` ([`app/Services/Portal/PortalDispatchService.php`](../../app/Services/Portal/PortalDispatchService.php))
Service ini adalah gerbang keamanan utama (*security gateway*) yang bertanggung jawab atas resolusi hak akses dan penyusunan payload kredensial untuk ekstensi browser:

* **`dispatch(User $user, Portal $portal): array`**
  Alur logika evaluasi otorisasi dan resolusi kredensial:
  
  ```
  [User menekan tombol "Buka Portal"]
                  │
                  ▼
         [Portal aktif?] ───(TIDAK)───► Throw 403: "Portal pelaporan ini sedang nonaktif."
                  │ (YA)
                  ▼
    [Ambil UserPortalCredential aktif]
                  │
          ┌───────┴───────┐
       (Ada)           (Tidak Ada)
          │               │
          │               ▼
          │         [User Admin / SuperAdmin?]
          │               │ (YA)                  (TIDAK)
          │               ├── Portal supportsShared? ──► Throw 403: "Izin akses tidak aktif."
          │               │      │ (YA)         (TIDAK)
          │               │      │                 └──► Throw 403: "Portal bertipe personal."
          │               │      ▼
          │               │  [Dispatch Shared Credentials RS]
          │               ▼
          ▼
  [Tipe Kredensial User?]
          ├─► 'personal' & Portal supportsPersonal?
          │         │ (YA)
          │         └──► [Dispatch Personal Credentials Staf] (Decrypt personal_password)
          │
          └─► 'use_shared'
                    │
                    ├── Portal supportsShared?
                    │         │ (YA)
                    │         └──► [Dispatch Shared Credentials RS] (Decrypt shared_password)
                    │
                    └── (TIDAK) ──► Throw 403: "Portal ini bertipe personal."
  ```

  1. **Validasi Status Portal:** Memastikan `$portal->is_active === true`. Jika nonaktif, seketika menghentikan eksekusi dengan melempar `Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException`.
  2. **Lookup Hak Akses Aktif:** Memeriksa keberadaan baris mapping aktif pada tabel `user_portal_credentials`:
     ```php
     $credential = $portal->userCredentials()
         ->where('user_id', $user->id)
         ->where('is_active', true)
         ->first();
     ```
  3. **Bypass Hak Akses Administrator:**
     Jika user belum memiliki mapping individual namun memiliki hak akses level Administrator (`$user->isAdmin() || $user->isSuperAdmin()`):
     - Jika portal mendukung akun bersama (`$portal->supportsShared()`), sistem secara otomatis mengizinkan admin menggunakan akun institusi rumah sakit via `buildSharedPayload($portal)`.
     - Jika portal bertipe `personal` murni, melempar exception karena kredensial personal wajib dikonfigurasi terlebih dahulu.
  4. **Penolakan Staf Tanpa Hak Akses:** Jika staf biasa tidak memiliki mapping aktif, sistem menolak request dengan `AccessDeniedHttpException('Anda tidak memiliki izin akses aktif ke portal ini.')`.
  5. **Resolusi Kredensial Hibrida:**
     - **Akun Personal:** Jika `$credential->isPersonal()` dan portal mendukung personal, menyusun kredensial dari `$credential->personal_username` dan password terdekripsi transparan `$credential->personal_password`.
     - **Akun Bersama:** Jika mapping bertipe `use_shared`, menyusun kredensial dari `$portal->shared_username` dan password terdekripsi transparan `$portal->shared_password`.
  6. **Dekripsi Simetris On-The-Fly:** Eloquent cast `'encrypted'` mendekripsi ciphertext langsung saat atribut diakses di memori PHP worker. Kredensial tidak pernah ditulis ke disk atau cache.
  7. **Stempel Waktu:** Menyematkan atribut `'dispatched_at' => now()->toIso8601String()`.

* **`buildSharedPayload(Portal $portal): array` & `formatPortalMeta(Portal $portal): array`**
  - Menyusun struktur payload standar berisi metadata portal (`id`, `name`, `slug`, `category`, `url`, `url_pattern`, dan JSON `form_config`) serta objek kredensial.

---

#### 4. `PortalPersonalCredentialService` ([`app/Services/Portal/PortalPersonalCredentialService.php`](../../app/Services/Portal/PortalPersonalCredentialService.php))
Service ini memfasilitasi fitur mandiri (*self-service*) bagi staf rumah sakit untuk mengelola akun pribadi mereka:

* **`updatePersonalCredential(User $user, Portal $portal, array $data): UserPortalCredential`**
  - Mengambil rekaman mapping yang sah milik pengguna pada portal target:
    ```php
    $credential = UserPortalCredential::where('user_id', $user->id)
        ->where('portal_id', $portal->id)
        ->firstOrFail();
    ```
  - Menyetel `credential_type = 'personal'` dan memperbarui `personal_username`.
  - **Preservasi Password Saat Dikosongkan:**
    ```php
    if (filled($data['password'] ?? null)) {
        $updateData['personal_password'] = $data['password'];
    }
    ```
    Jika staf hanya memperbarui username tanpa mengubah kata sandi, kata sandi personal yang telah terenkripsi sebelumnya tetap dipertahankan.
  - Menyimpan konfigurasi bidang tambahan `personal_extra_fields` jika disediakan.

---

### 4.2 Defense-in-Depth Authorization & Middleware Perimeter

Keamanan akses portal pelaporan eksternal dirancang bertingkat (*Defense-in-Depth*) melalui 4 lapis perlindungan:

```
[Request Masuk]
      │
      ▼
[Lapisan 1: Route Middleware Perimeter] ──► middleware('can:manage,App\Models\Portal')
      │                                      (routes/web.php)
      ▼
[Lapisan 2: Form Request Authorization] ──► PortalRequest::authorize()
      │                                      (Cek izin sebelum validasi input dijalankan)
      ▼
[Lapisan 3: Controller Policy Gate]     ──► Gate::authorize('manage' / 'dispatchToken', $portal)
      │                                      (app/Policies/PortalPolicy.php)
      ▼
[Lapisan 4: Service Level Assertion]    ──► AccessDeniedHttpException
                                             (Validasi status aktif portal & tipe kredensial)
```

#### 1. Lapisan 1: Middleware Perimeter Rute (`routes/web.php`)
Seluruh rute manajemen admin dikelompokkan dalam satu grup rute dengan middleware perimeter:

```php
// routes/web.php
Route::prefix('admin/portals')
    ->name('admin.portals.')
    ->middleware('can:manage,App\Models\Portal')
    ->group(function () {
        Route::get('/', [AdminPortalController::class, 'index'])->name('index');
        Route::get('/create', [AdminPortalController::class, 'create'])->name('create');
        Route::post('/', [AdminPortalController::class, 'store'])->name('store');
        Route::get('/{portal}/edit', [AdminPortalController::class, 'edit'])->name('edit');
        Route::put('/{portal}', [AdminPortalController::class, 'update'])->name('update');
        Route::delete('/{portal}', [AdminPortalController::class, 'destroy'])->name('destroy');
        Route::patch('/{portal}/toggle-active', [AdminPortalController::class, 'toggleActive'])->name('toggle-active');

        Route::get('/mapping', [AdminPortalMappingController::class, 'index'])->name('mapping.index');
        Route::post('/mapping/save-row', [AdminPortalMappingController::class, 'saveRow'])->name('mapping.save-row');
        Route::post('/mapping/sync-portal', [AdminPortalMappingController::class, 'syncPortal'])->name('mapping.sync-portal');
        Route::post('/mapping/sync-user', [AdminPortalMappingController::class, 'syncUser'])->name('mapping.sync-user');
        Route::patch('/mapping/{credential}', [AdminPortalMappingController::class, 'updateCredential'])->name('mapping.update-credential');
        Route::delete('/mapping/{credential}', [AdminPortalMappingController::class, 'destroyCredential'])->name('mapping.destroy-credential');
    });
```
Jika staf non-admin mencoba memanggil salah satu rute ini secara langsung, middleware perimeter Laravel langsung menolak request dengan status **HTTP 403 Forbidden** sebelum kode kontroler dieksekusi.

#### 2. Lapisan 2: Gate & Kebijakan Otorisasi (`PortalPolicy`)
Kebijakan otorisasi didefinisikan secara deklaratif pada [`App\Policies\PortalPolicy`](../../app/Policies/PortalPolicy.php):

| Method Policy | Signature | Aturan Evaluasi | Tujuan Penggunaan |
| :--- | :--- | :--- | :--- |
| `manage` | `User $user` | `$user->isAdmin() \|\| $user->isSuperAdmin()` | Mengendalikan seluruh akses fitur CRUD master portal dan mapping staf. |
| `viewAny` | `User $user` | `true` | Mengizinkan seluruh pengguna terautentikasi membuka katalog agregator portal. |
| `viewAnyAdmin` | `User $user` | `manage($user)` | Menentukan apakah user berhak melihat daftar master portal admin. |
| `view` | `User $user, Portal $portal` | Admin **ATAU** portal aktif & user memiliki baris mapping aktif (`is_active = true`). | Mengizinkan staf membuka rincian kartu portal tertentu. |
| `create`, `update`, `delete` | `User $user, [Portal $portal]` | `manage($user)` | Perlindungan mutasi entitas portal di level controller. |
| `dispatchToken` | `User $user, Portal $portal` | Portal wajib aktif (`$portal->is_active`), serta user adalah Admin **ATAU** user memiliki mapping aktif. | Mengamankan endpoint transfer kredensial ke ekstensi. |
| `updatePersonalCredential` | `User $user, Portal $portal` | Portal wajib aktif, portal mendukung akun personal (`supportsPersonal()`), serta user memiliki mapping aktif. | Mengamankan pengeditan akun pribadi oleh staf. |

Penerapan pada kontroler dilakukan melalui fasad Gate:
```php
// app/Http/Controllers/PortalDispatchController.php
public function dispatch(Request $request, Portal $portal): JsonResponse
{
    Gate::authorize('dispatchToken', $portal);

    $payload = $this->dispatchService->dispatch($request->user(), $portal);

    return response()->json($payload);
}
```

#### 3. Lapisan 3: Otorisasi Form Request
Seluruh *Form Request* kelas admin (`PortalRequest`, `SaveMappingRowRequest`, `SyncPortalUsersRequest`, `SyncUserPortalsRequest`, `UpdateMappingCredentialRequest`) mengimplementasikan validasi otorisasi mandiri:
```php
public function authorize(): bool
{
    return $this->user()?->can('manage', Portal::class) ?? false;
}
```

#### 4. Lapisan 4: Shared Inertia Props (`HandleInertiaRequests.php`)
Untuk menghindari hardcoding logika role di sisi frontend React, middleware [`App\Http\Middleware\HandleInertiaRequests`](../../app/Http/Middleware/HandleInertiaRequests.php) membagikan atribut otorisasi secara global:

```php
'permissions' => [
    'can_manage_portals' => $request->user()?->can('manage', \App\Models\Portal::class) ?? false,
    // ...
]
```
Komponen antarmuka seperti Sidebar Navigasi ([`app-sidebar.tsx`](../../resources/js/components/app-sidebar.tsx)) dapat mengevaluasi prop `can_manage_portals` untuk menampilkan atau menyembunyikan menu manajemen secara deklaratif.

---

### 4.3 Kontrak API Endpoint Dispatch Token

Endpoint Dispatch Token adalah satu-satunya kanal komunikasi resmi yang menyediakan kredensial login kepada ekstensi browser Chromium.

#### 1. Spesifikasi Teknis Endpoint
* **HTTP Method:** `POST`
* **URI:** `/portal-pelaporan/{portal}/dispatch-token`
* **Route Name:** `portal-pelaporan.dispatch-token`
* **Controller:** [`App\Http\Controllers\PortalDispatchController::dispatch`](../../app/Http/Controllers/PortalDispatchController.php)
* **Model Binding:** Menggunakan *Implicit Route Model Binding* Laravel. Parameter `{portal}` menerima ID integer (contoh: `/portal-pelaporan/1/dispatch-token`) atau slug string unik (contoh: `/portal-pelaporan/sirika-bkkbn/dispatch-token`).
* **Header Wajib:**
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN: <csrf_token_session>`
* **Autentikasi:** Web Session Cookie Laravel (hanya dapat dipanggil dalam konteks browser yang sedang aktif masuk ke akun SIMRS Sifast).
* **Otorisasi:** `Gate::authorize('dispatchToken', $portal)`.

#### 2. Format Request Payload
Endpoint ini tidak memerlukan request body (`{}`). Seluruh konteks user diidentifikasi melalui sesi autentikasi (`$request->user()`), dan konteks portal diidentifikasi melalui parameter URL `{portal}`.

#### 3. Format Response Payload Berhasil (`200 OK`)
Struktur JSON yang dikembalikan mencakup metadata navigasi web target, rantai selector CSS, dan kredensial plaintext hasil dekripsi simetris on-the-fly:

```json
{
  "success": true,
  "portal": {
    "id": 1,
    "name": "SIRIKA (BKKBN)",
    "slug": "sirika-bkkbn",
    "category": "BKKBN",
    "url": "https://sirika.bkkbn.go.id/login",
    "url_pattern": "https://sirika.bkkbn.go.id/*",
    "form_config": {
      "is_spa": false,
      "wait_timeout_ms": 10000,
      "username_field": {
        "selectors": [
          "#c",
          "input[name='email']",
          "input[type='text']"
        ]
      },
      "password_field": {
        "selectors": [
          "#password",
          "input[name='password']"
        ]
      },
      "extra_fields": [],
      "auto_submit": false
    }
  },
  "credentials": {
    "type": "shared",
    "username": "rs_siti_fatimah",
    "password": "PasswordPlaintextHasilDekripsi",
    "extra_fields": []
  },
  "dispatched_at": "2026-09-14T09:45:00+07:00"
}
```

#### 4. Format Response Galat (*Error Responses*)
* **401 Unauthorized (Belum Login / Sesi Kedaluwarsa):**
  ```json
  {
    "message": "Unauthenticated."
  }
  ```
* **403 Forbidden (Tidak Memiliki Izin Akses atau Portal Nonaktif):**
  ```json
  {
    "message": "Anda tidak memiliki izin akses aktif ke portal ini."
  }
  ```
  *(Atau: `"Portal pelaporan ini sedang nonaktif."` jika portal dinonaktifkan oleh admin).*
* **404 Not Found (Portal Tidak Terdaftar):**
  ```json
  {
    "message": "No query results for model [App\\Models\\Portal] 999"
  }
  ```

#### 5. Pembahasan Aspek Zero-Leakage
Endpoint dispatch token dirancang khusus untuk memenuhi standar keamanan ketat:
1. **On-Demand Dispatch:** Token kredensial hanya dipanggil ketika staf rumah sakit secara sadar mengklik tombol **"Buka Portal"** pada UI SIMRS. Kredensial tidak pernah di-load di awal (*pre-fetched*) saat halaman katalog dibuka.
2. **Ephemerality (Sifat Sementara):** Payload JSON dikonsumsi langsung oleh *Content Bridge* ekstensi dan disimpan di memori volatile RAM Service Worker dengan waktu hidup maksimal 30 detik (lihat Bab 6).
3. **No In-Page Exposure:** Kata sandi yang didekripsi tidak pernah disematkan ke dalam tag HTML DOM, objek JavaScript global `window`, maupun Inertia Page Props.

---

### 4.4 Peta Berkas & Panduan Code Review (Plan 1)

Tabel berikut adalah panduan komprehensif bagi developer dan reviewer untuk mengaudit berkas-berkas implementasi Plan 1 (Backend Core, Model, Database, & Service Layer):

| No | Berkas / Komponen | Status | Deskripsi Fungsi | Rationale Arsitektural | Poin Kritis Code Review |
| :---: | :--- | :---: | :--- | :--- | :--- |
| **1** | [`app/Models/Portal.php`](../../app/Models/Portal.php) | `[BARU]` | Model Eloquent master portal eksternal. | Menyediakan representasi entitas portal, relasi `userCredentials`, helper `supportsShared()` / `supportsPersonal()`, dan scope query. | Verifikasi cast `'shared_password' => 'encrypted'`, properti `$hidden = ['shared_password']`, dan penanganan array casts JSON. |
| **2** | [`app/Models/UserPortalCredential.php`](../../app/Models/UserPortalCredential.php) | `[BARU]` | Model pivot relasi penugasan staf dan kredensial akun personal. | Mengisolasi kredensial personal per staf, status aktif hak akses, dan relasi ke `users` serta `portals`. | Pastikan cast `'personal_password' => 'encrypted'` dan `$hidden = ['personal_password']` aktif agar kata sandi tidak bocor saat serialisasi. |
| **3** | [`app/Models/User.php`](../../app/Models/User.php) | `[MODIFIKASI]` | Model entitas pengguna SIMRS Sifast. | Menambahkan relasi `portalCredentials()` (`hasMany`) dan `portals()` (`belongsToMany`) untuk mendukung navigasi relasi dua arah. | Pastikan foreign key merujuk tepat ke `user_portal_credentials.user_id` tanpa mengganggu relasi modul lain (Aset, Simmutu, Patroli). |
| **4** | [`database/migrations/2026_09_09_100000_create_portals_table.php`](../../database/migrations/2026_09_09_100000_create_portals_table.php) | `[BARU]` | DDL skema database tabel `portals`. | Mendefinisikan struktur penyimpanan master portal, indeks slug unik, kolom kredensial shared, dan JSON form config. | Pastikan `slug` memiliki constraint `unique()`, kolom `shared_password` bertipe `text` (menampung ciphertext Base64), dan indeks `is_active`. |
| **5** | [`database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php`](../../database/migrations/2026_09_09_100001_create_user_portal_credentials_table.php) | `[BARU]` | DDL skema database tabel `user_portal_credentials`. | Menyimpan mapping otorisasi penugasan staf dan akun personal dengan integritas referensial. | Periksa keberadaan constraint unik komposit `$table->unique(['user_id', 'portal_id'])` dan `cascadeOnDelete()` pada kedua foreign key. |
| **6** | [`database/seeders/PortalSeeder.php`](../../database/seeders/PortalSeeder.php) | `[BARU]` | Seeder data awal 8 kelompok portal resmi kementerian/lembaga. | Menyediakan konfigurasi portal siap pakai (URL, selector DOM form login, pola URL) untuk lingkungan pengujian dan produksi awal. | Pastikan metode penyemaian bersifat idempoten menggunakan `updateOrCreate(['slug' => ...])` dan password bawaan berformat placeholder aman. |
| **7** | [`database/seeders/DatabaseSeeder.php`](../../database/seeders/DatabaseSeeder.php) | `[MODIFIKASI]` | Master database seeder aplikasi. | Mendaftarkan `PortalSeeder::class` ke dalam alur seeding utama SIMRS. | Pastikan seeder dieksekusi setelah seeder tabel `users` selesai agar relasi referensial terpenuhi. |
| **8** | [`app/Services/Portal/AdminPortalService.php`](../../app/Services/Portal/AdminPortalService.php) | `[BARU]` | Concrete service untuk manajemen master portal. | Mengabstraksi logika kueri pencarian multi-kolom, sanitasi wildcard, auto-slug, paginasi, dan preservasi password. | Audit method `updatePortal`: pastikan `unset($data['shared_password'])` berjalan saat field password kosong agar ciphertext lama tidak hilang. |
| **9** | [`app/Services/Portal/AdminPortalMappingService.php`](../../app/Services/Portal/AdminPortalMappingService.php) | `[BARU]` | Concrete service untuk matriks hak akses staf. | Mengelola data dual-view, transaksi database massal (`DB::transaction()`), dan logika preservasi catatan penugasan staf. | **Poin Kritis:** Cek parameter `$updateNotes` pada `saveSingleAssignment()` agar catatan staf lama tidak terhapus saat toggle switch akses di-klik. |
| **10** | [`app/Services/Portal/PortalDispatchService.php`](../../app/Services/Portal/PortalDispatchService.php) | `[BARU]` | Concrete service untuk resolusi dan transfer kredensial. | Memvalidasi keaktifan portal, memverifikasi mapping staf, menangani bypass admin, dan mendekripsi password secara transparan. | Pastikan melempar `AccessDeniedHttpException` jika portal nonaktif atau hak akses tidak sah. Audit struktur payload JSON. |
| **11** | [`app/Services/Portal/PortalPersonalCredentialService.php`](../../app/Services/Portal/PortalPersonalCredentialService.php) | `[BARU]` | Concrete service untuk self-service kredensial personal staf. | Mengizinkan staf memperbarui username dan password akun personal mereka sendiri secara mandiri. | Pastikan verifikasi kepemilikan record (`where('user_id', $user->id)`) dan preservasi password lama jika field password dikosongkan. |
| **12** | [`app/Http/Controllers/Admin/AdminPortalController.php`](../../app/Http/Controllers/Admin/AdminPortalController.php) | `[BARU]` | Kontroler admin untuk master portal. | Menyediakan endpoint HTTP RESTful untuk CRUD master portal dan integrasi tampilan Inertia React. | Pastikan semua aksi kontroler dilindungi `Gate::authorize('manage', Portal::class)` dan murni memanggil `AdminPortalService`. |
| **13** | [`app/Http/Controllers/Admin/AdminPortalMappingController.php`](../../app/Http/Controllers/Admin/AdminPortalMappingController.php) | `[BARU]` | Kontroler admin untuk matriks mapping akses. | Menangani render matriks mapping dual-mode, auto-save baris tunggal, dan sinkronisasi massal transaksional. | Periksa method `saveRow()`: pastikan argumen keenam mengirimkan `$request->has('notes')` ke service layer. |
| **14** | [`app/Http/Controllers/PortalDispatchController.php`](../../app/Http/Controllers/PortalDispatchController.php) | `[BARU]` | Kontroler endpoint dispatch token. | Menghubungkan browser extension dengan `PortalDispatchService` via HTTP POST berformat JSON. | Verifikasi evaluasi `Gate::authorize('dispatchToken', $portal)` sebelum eksekusi service. |
| **15** | [`app/Http/Controllers/PortalPersonalCredentialController.php`](../../app/Http/Controllers/PortalPersonalCredentialController.php) | `[BARU]` | Kontroler pembaruan kredensial personal staf. | Memproses pembaruan akun pribadi mandiri melalui request HTTP PUT berformat JSON. | Verifikasi evaluasi `Gate::authorize('updatePersonalCredential', $portal)`. |
| **16** | [`app/Http/Requests/Admin/PortalRequest.php`](../../app/Http/Requests/Admin/PortalRequest.php) | `[BARU]` | Form Request validasi master portal. | Memvalidasi nama, URL, slug unik (ignore ID saat update), auth type, dan JSON form config. | Periksa method `prepareForValidation()` untuk auto-slug dan aturan validasi `auth_type` (`shared,personal,both`). |
| **17** | [`app/Http/Requests/Admin/SaveMappingRowRequest.php`](../../app/Http/Requests/Admin/SaveMappingRowRequest.php) | `[BARU]` | Form Request auto-save mapping baris tunggal. | Memvalidasi parameter `portal_id`, `user_id`, `has_access`, `credential_type`, dan `notes`. | Pastikan otorisasi `can('manage', Portal::class)` aktif dan validasi foreign key `exists` berjalan semestinya. |
| **18** | [`app/Http/Requests/Admin/SyncPortalUsersRequest.php`](../../app/Http/Requests/Admin/SyncPortalUsersRequest.php) | `[BARU]` | Form Request sinkronisasi massal portal-centric. | Memvalidasi array `assignments` per portal mencakup `user_id`, `has_access`, dan `credential_type`. | Pastikan validasi nested array `assignments.*.user_id` memiliki aturan ketat `exists:users,id`. |
| **19** | [`app/Http/Requests/Admin/SyncUserPortalsRequest.php`](../../app/Http/Requests/Admin/SyncUserPortalsRequest.php) | `[BARU]` | Form Request sinkronisasi massal user-centric. | Memvalidasi array `assignments` per user mencakup `portal_id`, `has_access`, dan `credential_type`. | Pastikan validasi nested array `assignments.*.portal_id` memiliki aturan ketat `exists:portals,id`. |
| **20** | [`app/Http/Requests/Admin/UpdateMappingCredentialRequest.php`](../../app/Http/Requests/Admin/UpdateMappingCredentialRequest.php) | `[BARU]` | Form Request update satu baris mapping. | Memvalidasi perubahan `credential_type`, status `is_active`, dan `notes`. | Pastikan aturan enum `in:use_shared,personal` dan boolean status tervalidasi. |
| **21** | [`app/Http/Requests/UpdatePersonalCredentialRequest.php`](../../app/Http/Requests/UpdatePersonalCredentialRequest.php) | `[BARU]` | Form Request update akun personal staf. | Memvalidasi `username` (wajib), `password` (nullable, minimal 4 karakter), dan `extra_fields`. | Pastikan password bersifat opsional (`nullable`) agar staf tidak dipaksa mengisi ulang password saat hanya mengedit username. |
| **22** | [`app/Policies/PortalPolicy.php`](../../app/Policies/PortalPolicy.php) | `[BARU]` | Policy otorisasi subsistem portal. | Mengatur otorisasi terpusat berbasis peran dan hak akses individual staf ke portal eksternal. | Pastikan `dispatchToken` memverifikasi `$portal->is_active` dan relasi aktif user sebelum memberikan izin. |
| **23** | [`app/Http/Middleware/HandleInertiaRequests.php`](../../app/Http/Middleware/HandleInertiaRequests.php) | `[MODIFIKASI]` | Middleware pembagian shared props Inertia. | Membagikan permission flag `can_manage_portals` ke seluruh komponen frontend React. | Pastikan evaluasi permission menggunakan null-safe operator `$request->user()?->can('manage', Portal::class) ?? false`. |
| **24** | [`routes/web.php`](../../routes/web.php) | `[MODIFIKASI]` | Definisi rute web dan API internal SIMRS. | Mendaftarkan perimeter rute admin portal (`admin/portals`) dan endpoint publik staf (`portal-pelaporan/*`). | Pastikan middleware `can:manage,App\Models\Portal` melindungi seluruh rute admin. |
| **25** | [`tests/Feature/PortalPelaporan/*`](../../tests/Feature/PortalPelaporan/) | `[BARU]` | Berkas pengujian otomatis Pest PHP (13 files). | Menguji seluruh fungsionalitas backend core, model, otorisasi, kontroler, dan layanan service layer. | Pastikan seluruh pengujian lulus 100% tanpa regresi pada modul lain di SIMRS Sifast. |

---

### 4.5 Panduan Uji Coba Cepat (Hands-on Verification Bab 4)

Pengembang yang baru bergabung dapat menjalankan pengujian otomatis Pest PHP berikut untuk memvalidasi bahwa seluruh komponen backend core dan arsitektur service layer berjalan dengan sempurna di lingkungan lokal:

```bash
# 1. Uji integritas skema tabel database dan foreign key cascade
php artisan test tests/Feature/PortalPelaporan/PortalDatabaseSchemaTest.php

# 2. Uji enkripsi simetris, casting model, dan relasi Eloquent
php artisan test tests/Feature/PortalPelaporan/PortalModelTest.php

# 3. Uji aturan kebijakan otorisasi (PortalPolicy)
php artisan test tests/Feature/PortalPelaporan/PortalPolicyTest.php

# 4. Uji endpoint API Dispatch Token (Otorisasi, Response 200/403/401)
php artisan test tests/Feature/PortalPelaporan/PortalDispatchApiTest.php

# 5. Uji logika bisnis resolusi kredensial pada PortalDispatchService
php artisan test tests/Feature/PortalPelaporan/PortalDispatchServiceTest.php

# 6. Uji logika master portal pada AdminPortalService (CRUD, Auto-slug, Password Preservation)
php artisan test tests/Feature/PortalPelaporan/AdminPortalServiceTest.php

# 7. Uji logika matriks mapping dan transaksi DB pada AdminPortalMappingService
php artisan test tests/Feature/PortalPelaporan/AdminPortalMappingServiceTest.php

# 8. Uji self-service kredensial personal staf (Service & API Endpoint)
php artisan test tests/Feature/PortalPelaporan/PortalPersonalCredentialServiceTest.php
php artisan test tests/Feature/PortalPelaporan/PortalPersonalCredentialApiTest.php
```

#### Menjalankan Seluruh Pengujian Modul Portal Pelaporan Sekaligus:
```bash
# Mengeksekusi seluruh 13 test files (62 test cases, 354 assertions)
php artisan test tests/Feature/PortalPelaporan
```

*Untuk rincian matriks pengujian lengkap, skenario pengujian browser extension, dan panduan walkthrough manual QA, silakan lihat [Bab 8: 🧪 Panduan Pengujian & Skenario Verifikasi (Master Testing Guide)](#bab-8--panduan-pengujian--skenario-verifikasi-master-testing-guide).*

---

