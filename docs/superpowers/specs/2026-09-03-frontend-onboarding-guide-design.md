# 📐 Spesifikasi Desain: Panduan Frontend React & Inertia untuk Developer Laravel (Transisi dari Blade & jQuery) serta Koreksi Audit Onboarding

**Tanggal Dokumen:** 2026-09-03  
**Status:** Disetujui (Approved)  
**Target Pembaca:** Developer Laravel yang memiliki latar belakang Blade & jQuery / Vanilla JS yang akan mengembangkan atau memelihara Portal Sifast (RS Aisyiyah Siti Fatimah Tulangan).

---

## 1. Latar Belakang & Tujuan

Portal Sifast dibangun dengan arsitektur modern full-stack menggunakan **Laravel 12**, **Inertia.js v2**, **React 19**, **TypeScript**, **Tailwind CSS v4**, **Radix UI**, dan **Laravel Wayfinder**. 

Bagi developer yang selama ini terbiasa dengan pola monolitik tradisional Laravel (Blade templates, form submits, manipulasi DOM berbasis jQuery `$('#id')`, serta routing Ziggy `route()`), arsitektur ini menghadirkan perubahan paradigma yang signifikan:
1. Tidak ada file `.blade.php` untuk halaman konten aplikasi; hanya ada satu berkas root template shell: [`resources/views/app.blade.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/views/app.blade.php).
2. Tampilan bersifat deklaratif berbasis komponen dan *State* React, menggantikan manipulasi DOM langsung (`document.getElementById` atau `$`).
3. Interaksi data server-klien tidak menggunakan AJAX manual (`$.ajax()`) atau perenderan ulang halaman penuh (*full page reload*), melainkan dikelola oleh Inertia.js (`Inertia::render()`, `useForm()`, `router.visit()`).
4. Type safety ketat dengan TypeScript dan penamaan rute modular via Wayfinder (`@/routes/*`), bukan `route('...')` global.

Tujuan dari pekerjaan ini adalah:
1. Membuat dokumen panduan baru yang komprehensif: [`docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md).
2. Menerapkan alur belajar bertahap: **Tahap 1 Bedah Kode & Kamus Padanan** (membedah modul tiket [`TicketController.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Http/Controllers/TicketController.php) dan [`resources/js/pages/tickets/`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/pages/tickets/)), dilanjutkan dengan **Tahap 2 Praktik Mandiri CRUD Step-by-Step** (menggunakan modul proyek [`ProjectController.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Http/Controllers/ProjectController.php) dan [`resources/js/pages/projects/`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/pages/projects/)).
3. Memperbaiki ketidakakuratan dan celah informasi pada dokumen onboarding existing ([`00`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md), [`01`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md), [`02`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md), [`11`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md)) berdasarkan hasil audit Gemini 3.8 Flash terhadap codebase aktual.

---

## 2. Struktur Modul Baru: `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`

Modul `02b` akan berisi 6 bab berurutan:

### Bab 1: Pergeseran Paradigma (Mental Model Shift) & Kamus Padanan
* **Arsitektur Single Blade Shell:** Mengapa `resources/views/app.blade.php` adalah satu-satunya template web, dan bagaimana `@inertia` menjadi titik kait (*mount point*) aplikasi React.
* **Kamus Padanan Lengkap:** Tabel komparasi langsung antara Blade/jQuery dengan React 19/Inertia v2 (perenderan view, passing data props, layouting, syntax JSX vs Blade directive, penanganan form, dsb.).
* **Anatomi dan Paradigma React Hooks:**
  * Apa itu Hook (`use...`) bagi developer Blade/jQuery.
  * Padanan `useState` terhadap variabel lokal dan pembaruan DOM.
  * Padanan `useEffect` terhadap `$(document).ready()` dan pembersih (*cleanup*).
  * Manfaat Inertia Hooks: `useForm` (pengganti `$('form').serialize()`) dan `usePage` (pengganti pemanggilan global `auth()->user()`).
  * Custom Hooks Portal Sifast (`useEcho`, `useUserPresence`, `useAppearance`).
* **Konsep Kunci React Tambahan:**
  * Aturan JSX/TSX (`className`, `htmlFor`, wajib self-closing `<input />`, Fragment `<> ... </>`).
  * Komponen & Props (mengapa bersifat read-only / immutable).
  * Controlled vs Uncontrolled Input.
  * Lifting State Up.
  * Atribut wajib `key` pada perulangan data (`.map()`).
  * React Context untuk data global lintas komponen (`resources/js/contexts/`).
  * Dasar TypeScript untuk komponen (definisi `type Props = { ... }`).

### Bab 2: Bedah Kasus Nyata Modul Helpdesk Tiket (Tahap 1: Analisis)
* **Alur End-to-End Controller ke React:**
  * Analisis method `TicketController::index` baris 83 yang memanggil `Inertia::render('tickets/index', [...])`.
  * Penerimaan data pada `resources/js/pages/tickets/index.tsx` via `type Props`.
* **Mekanisme Filter & Pencarian Tanpa Reload:**
  * Penggunaan `useState` untuk menyimpan string pencarian.
  * Eksekusi `router.get('/tickets', newFilters, { preserveState: true, replace: true })`.
* **Bedah Form Pembuatan Tiket:**
  * Analisis `resources/js/pages/tickets/create.tsx` yang menggunakan `useForm()`.
  * Input controlled, binding data via `setData()`, submit form dengan `post()`, dan penanganan error validasi via `<InputError message={errors.title} />`.
* **Penggunaan Wayfinder Routing Aktual:**
  * Menunjukkan cara import modular dari `@/routes/tickets` dan pemanggilan `show(id).url`.

### Bab 3: Tutorial Hands-on CRUD Step-by-Step (Tahap 2: Praktik)
* **Studi Kasus Modul Proyek / Rencana Kerja (`projects`):**
  * **Langkah 1 (Backend):** Controller `ProjectController` yang me-render halaman Inertia dan menerima request form.
  * **Langkah 2 (TypeScript Types):** Mendefinisikan tipe data `ProjectItem`, `PaginatedProjects`, dan `Props`.
  * **Langkah 3 (Halaman Index):** Membangun tabel daftar data dengan `<AppLayout>`, perulangan `.map()`, dan filter live.
  * **Langkah 4 (Halaman Form Create):** Membangun form dengan `useForm`, input controlled, handling validasi Laravel FormRequest, dan status loading `processing`.
  * **Langkah 5 (Feedback):** Mengambil flash message sukses dari Laravel via `usePage().props.flash`.

### Bab 4: Arsitektur Styling & Komponen Antarmuka
* **Tailwind CSS v4 (CSS-First Architecture):**
  * Penjelasan mengapa tidak ada `tailwind.config.js`.
  * Konfigurasi tema dan token warna di `@theme` pada `resources/css/app.css`.
  * Mekanisme Dark Mode via varian `@custom-variant dark`.
* **Katalog Komponen UI Primitif (`resources/js/components/ui/`):**
  * Standar Shadcn / Radix: Button, Input, Dialog, Select, DropdownMenu, Badge.
  * Penggunaan helper `cn(...)` (`clsx` + `tailwind-merge`) untuk komposisi class styling yang aman dari konflik class.

### Bab 5: Reaktivitas Real-Time & WebSockets
* Integrasi **Laravel Reverb** dan **Laravel Echo** di sisi frontend.
* Cara kerja penyuntikan konfigurasi dari Blade: `window.REVERB_CONFIG` di `resources/views/app.blade.php` dibaca oleh `resources/js/echo.js`.
* Penggunaan hook `useEcho` dan channel `presence-online-users` / `private-chat.{id}`.

### Bab 6: Anti-Patterns, Tips Produktivitas & Debugging
* **Daftar Larangan Keras bagi Mantan Pengguna jQuery:**
  * Dilarang menggunakan `document.getElementById`, `document.querySelector`, atau jQuery `$('#id')` untuk mengubah isi atau visibilitas elemen.
  * Dilarang melakukan mutasi variabel state secara langsung (misal: `data.title = 'baru'` tanpa `setData`).
* **React 19 Compiler:** Penjelasan bahwa compiler secara otomatis menangani memoization fungsi dan variabel kalkulasi, sehingga developer tidak perlu menulis `useMemo` / `useCallback` untuk kebutuhan umum.
* **Toolkit Debugging:**
  * Memeriksa props dan state menggunakan tab Network (melihat payload JSON XHR Inertia).
  * Penggunaan React Developer Tools & Inertia DevTools.

---

## 3. Rencana Koreksi Audit Dokumen Existing

### A. [`docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md)
1. Menambahkan baris modul `02b` pada tabel direktori dokumen onboarding.
2. Memperbarui jadwal belajar developer baru pada Hari ke-1 / Hari ke-2 untuk menyertakan modul `02b` sebelum mempelajari modul-modul bisnis.

### B. [`docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md)
1. Menambahkan sub-poin **React 19 Compiler** (`babel-plugin-react-compiler`) pada daftar tech stack frontend.
2. Memperjelas arsitektur **Tailwind CSS v4** yang menggunakan sistem CSS-first (`@theme` di `resources/css/app.css`) tanpa file `tailwind.config.js`.
3. Menjelaskan status **Single Blade Shell** (`resources/views/app.blade.php`) dan peruntukan file blade cetak/email lainnya.

### C. [`docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md)
1. **Memperbaiki sintaks Wayfinder** di Bagian 3:
   * Menghapus contoh salah ala Ziggy: `import { route } from '@/wayfinder'; route('tickets.show', ...)`.
   * Menggantinya dengan contoh kode nyata Wayfinder: `import { show, resolve } from '@/routes/tickets';` dan pemanggilan `show(ticket.id).url`.

### D. [`docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md)
1. Memperbarui cuplikan kode inisialisasi client pada Bagian 4 agar selaras dengan implementasi nyata di `resources/js/echo.js`.
2. Menjelaskan strategi injeksi `window.REVERB_CONFIG` dari `resources/views/app.blade.php` untuk mencegah kesalahan URL/port WebSocket antara localhost dan domain produksi.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)

1. Berkas [`docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md) terbuat dengan seluruh 6 bab lengkap, tanpa placeholder `TODO` atau `TBD`.
2. Penjelasan konsep React (JSX, Props, Hooks, Controlled Component, Virtual DOM) disajikan dengan perbandingan nyata terhadap sintaks Blade dan jQuery.
3. Contoh kode yang dicantumkan bersumber langsung dan terverifikasi dari modul tiket (`tickets`) dan modul proyek (`projects`) di repositori ini.
4. Sintaks Wayfinder dan inisialisasi Echo pada dokumen `00`, `01`, `02`, dan `11` sudah terkoreksi dan konsisten dengan codebase.
5. Dokumen spesifikasi desain ini dikomit ke dalam repositori Git.
