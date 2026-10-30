# Panduan Frontend React & Inertia serta Audit Onboarding — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use [superpowers:subagent-driven-development](file:///Users/adijaya/.gemini/config/plugins/superpowers/skills/subagent-driven-development/SKILL.md) (recommended) or [superpowers:executing-plans](file:///Users/adijaya/.gemini/config/plugins/superpowers/skills/executing-plans/SKILL.md) to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyusun modul dokumentasi onboarding baru `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` (6 bab komprehensif berstandar Dual-Audience) serta memutakhirkan 4 dokumen existing (`00`, `01`, `02`, `11`) agar bebas dari miskonsepsi sintaksis Wayfinder, konfigurasi Echo Reverb yang usang, dan 100% bebas dari tautan absolut lokal `file:///...`.

**Architecture:** Dokumentasi onboarding teknis berstandar *Dual-Layer Information Architecture* ("Skim or Deep Dive"). Level atas menyajikan ringkasan instan (*TL;DR Cheatsheet* dan *Under the Hood*) bagi developer senior, sedangkan level detail menyajikan analogi intuitif, diagram Mermaid, anotasi kode baris demi baris dari modul produksi nyata (`TicketController.php`, `ProjectController.php`, `echo.js`, `app.blade.php`), serta katalog gotchas penanganan error bagi developer junior.

**Tech Stack:** Laravel 12, Inertia.js v2, React 19, TypeScript 5.7, Tailwind CSS v4, Radix UI, Laravel Wayfinder, Laravel Reverb & Echo, GitHub Flavored Markdown (GFM).

**Spec:** [`docs/superpowers/specs/2026-09-03-frontend-onboarding-guide-design.md`](../specs/2026-09-03-frontend-onboarding-guide-design.md)

## Global Constraints

* **100% GitHub-Compatible Relative Links:** Dilarang keras menggunakan skema tautan absolut lokal `file:///...`. Seluruh tautan dokumen dan kode sumber wajib menggunakan relative Markdown path (`../../app/...`, `./01-...`, dsb.) agar dapat diklik langsung di web GitHub maupun editor lokal.
* **Akurasi Sintaks Kode Produksi:** Dilarang mencantumkan sintaks palsu Ziggy (`route(...)` global atau `import { route } from '@/wayfinder'`). Seluruh pemanggilan rute wajib menggunakan fungsi type-safe Laravel Wayfinder resmi (`import { show } from '@/routes/tickets'; show(ticket.id).url`).
* **Integritas Kode Nyata:** Seluruh cuplikan kode dan nomor baris wajib bersumber dari file aktif di repositori (`TicketController.php`, `ProjectController.php`, `resources/js/pages/projects/index.tsx`, `resources/js/components/confirm-dialog.tsx`, `resources/js/echo.js`, `resources/views/app.blade.php`, `resources/css/app.css`).
* **Non-Goals Guard:** Tidak memperkenalkan SSR (karena Portal Sifast murni Client-Side Rendered via Single Blade Shell), tidak mengintroduksi global state manager eksternal (Redux, Zustand, Pinia), dan tidak mengubah kode bisnis produksi.
* **Gaya Bahasa:** Bahasa Indonesia formal, profesional, dan mudah dipahami, dengan istilah teknis bahasa Inggris yang umum dipertahankan secara konsisten.
* **Callout GFM Resmi:** Menggunakan callout GFM standar: `> [!NOTE]`, `> [!TIP]`, `> [!IMPORTANT]`, `> [!WARNING]`.

---

## File Structure & Map

| Action | Target Path | Responsibility |
| :--- | :--- | :--- |
| **Modify** | `docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md` | Tambah React 19 Compiler, perjelas Tailwind v4 `@theme`, tegaskan Single Blade Shell, konversi tautan `file:///` ke relatif. |
| **Modify** | `docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md` | Koreksi sintaks rute Wayfinder (`@/routes/tickets`), perbaiki path direktori tree `routes/`, konversi tautan `file:///` ke relatif. |
| **Modify** | `docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md` | Perbarui konfigurasi Echo ke *Dual-Source Config Pattern* (`window.REVERB_CONFIG`), konversi tautan `file:///` ke relatif. |
| **Modify** | `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md` | Daftarkan modul `02b` pada tabel modul & jadwal belajar Hari ke-2, konversi seluruh tautan `file:///` ke relatif. |
| **Create** | `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` | Modul panduan baru berisi 6 bab komprehensif transisi Blade/jQuery ke React/Inertia untuk developer Laravel. |
| **Modify** | `docs/developer-onboarding/03-MODUL-HELPDESK-ITIL-TICKETING.md` s/d `09-MODUL-PAYROLL-...md` | Pembersihan menyeluruh sisa tautan `file:///` di dokumen existing lainnya menjadi tautan relatif. |

---

### Task 1: Audit & Synchronize Foundation Docs (`01` & `02`)

**Files:**
- Modify: `docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md:85-95,145-150`
- Modify: `docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md:60-65,68-72,104-125`
- Test: Skrip verifikasi tautan relatif & deteksi `file:///`

**Interfaces:**
- Consumes: `docs/superpowers/specs/2026-09-03-frontend-onboarding-guide-design.md` (Bagian 6.B & 6.C)
- Produces: Fondasi arsitektur dan sintaks rute Wayfinder yang akurat pada modul `01` dan `02`

- [ ] **Step 1: Write the verification test script for Task 1**

Jalankan perintah ini untuk memastikan sebelum perubahan masih terdapat tautan `file:///` dan sintaks rute lama di kedua berkas:

```bash
python3 -c "
import sys, re

errors = []
for path in ['docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md', 'docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md']:
    with open(path) as f:
        content = f.read()
    if 'file:///' in content:
        errors.append(f'{path} contains file:/// links')
    if 'import { route } from \'@/wayfinder\'' in content:
        errors.append(f'{path} contains fake Ziggy route import')

if not errors:
    print('Already clean!')
else:
    print('Verification test caught expected issues:\n' + '\n'.join(errors))
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it catches issues**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan error mendeteksi `file:///` dan `import { route } from '@/wayfinder'`.

- [ ] **Step 3: Update `01-ARSITEKTUR-DAN-TECH-STACK.md`**

Lakukan perubahan berikut pada `docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md`:
1. Di bagian Frontend Tech Stack (sekitar baris 85-95):
   * Tambahkan detail **React 19 Compiler (`babel-plugin-react-compiler`)**: Vite secara otomatis melakukan optimasi AST auto-memoization, sehingga pemanggilan manual `useMemo` dan `useCallback` tidak diperlukan pada 95% komponen UI.
   * Perjelas arsitektur **Tailwind CSS v4 (CSS-First)**: Tidak lagi memerlukan `tailwind.config.js`, seluruh token tema didefinisikan melalui `@theme` pada `resources/css/app.css`.
   * Tegaskan status **Single Blade Shell (`resources/views/app.blade.php`)**: Aplikasi murni *Client-Side Rendered (CSR)* yang di-mount melalui `@inertia` dan `@inertiaHead` tanpa arsitektur SSR.
2. Di bagian koneksi `dbsimrs` (sekitar baris 147):
   * Ubah tautan model SIMRS:
     ```markdown
     * Contoh Model SIMRS: [`Pegawai`](../../app/Models/Pegawai.php), [`Dokter`](../../app/Models/Dokter.php), [`Inventaris`](../../app/Models/Inventaris.php), [`Departemen`](../../app/Models/Departemen.php).
     ```

- [ ] **Step 4: Update `02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`**

Lakukan perubahan berikut pada `docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`:
1. Di peta struktur folder frontend `resources/js/` (sekitar baris 63):
   * Ganti `wayfinder/` menjadi `routes/` (`Auto-generated type-safe route functions`).
2. Di bagian RBAC Model User (sekitar baris 70):
   * Ganti tautan model `User` menjadi relative path: [`User`](../../app/Models/User.php).
3. Di Bagian 3 "Integrasi Rute Type-Safe (Laravel Wayfinder)":
   * Hapus sintaks palsu Ziggy (`import { route } from '@/wayfinder'`).
   * Ganti dengan sintaks produksi Wayfinder yang benar:
     ```tsx
     import { show } from '@/routes/tickets';
     import { Link, router } from '@inertiajs/react';

     // 1. Menggunakan komponen Link dengan fungsi rute Wayfinder
     <Link href={show(ticket.id).url}>
         Lihat Detail Tiket #{ticket.ticket_number}
     </Link>

     // 2. Menggunakan Inertia Router untuk mutasi data
     router.post(show(ticket.id).url, {
         resolution_notes: notes,
     });
     ```
   * Berikan penjelasan bahwa setiap controller/rute Laravel menghasilkan modul TypeScript di `@/routes/<controller-path>` dengan method `.url` dan helper form.

- [ ] **Step 5: Run test to verify it passes**

Jalankan skrip verifikasi tautan relatif dan ketiadaan `file:///`:

```bash
python3 -c "
import sys, re, os

errors = []
files = ['docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md', 'docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md']

for path in files:
    with open(path) as f:
        content = f.read()
    if 'file:///' in content:
        errors.append(f'FAIL: {path} still has file:/// links')
    if '@/wayfinder' in content:
        errors.append(f'FAIL: {path} still has fake @/wayfinder import')
    
    links = re.findall(r'\[.*?\]\((.*?)\)', content)
    for l in links:
        if l.startswith('http') or l.startswith('#'):
            continue
        clean_target = l.split('#')[0]
        full_target = os.path.normpath(os.path.join(os.path.dirname(path), clean_target))
        if not os.path.exists(full_target):
            errors.append(f'FAIL: Broken link in {path}: {l} -> {full_target}')

if errors:
    print('\n'.join(errors))
    sys.exit(1)
else:
    print('SUCCESS: Both 01 and 02 are valid, relative, and syntax-accurate!')
"
```
Expected: PASS dengan output `SUCCESS: Both 01 and 02 are valid, relative, and syntax-accurate!`.

- [ ] **Step 6: Commit changes for Task 1**

```bash
git add docs/developer-onboarding/01-ARSITEKTUR-DAN-TECH-STACK.md docs/developer-onboarding/02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md
git commit -m "docs(onboarding): sync architecture and wayfinder route syntax in 01 and 02"
```

---

### Task 2: Audit & Synchronize Real-time & Index Docs (`11` & `00`)

**Files:**
- Modify: `docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md:30-52,55-75`
- Modify: `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md:37-66`
- Test: Skrip validasi integritas tautan modul `11` dan `00`

**Interfaces:**
- Consumes: `docs/superpowers/specs/2026-09-03-frontend-onboarding-guide-design.md` (Bagian 6.A & 6.D)
- Produces: Modul `11` dengan arsitektur Dual-Source Reverb Config yang benar dan modul `00` dengan indeks `02b` serta alur belajar yang sinkron.

- [ ] **Step 1: Write the failing verification test for Task 2**

```bash
python3 -c "
import sys

errors = []
for path in ['docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md', 'docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md']:
    with open(path) as f:
        content = f.read()
    if 'file:///' in content:
        errors.append(f'{path} contains file:/// links')

with open('docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md') as f:
    c00 = f.read()
if '02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md' not in c00:
    errors.append('00-INDEX does not list 02b module')

with open('docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md') as f:
    c11 = f.read()
if 'window.REVERB_CONFIG' not in c11:
    errors.append('11-REALTIME does not explain Dual-Source Config (window.REVERB_CONFIG)')

if not errors:
    print('Already clean!')
else:
    print('Verification test caught expected issues:\n' + '\n'.join(errors))
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it catches issues**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan error mendeteksi `file:///` pada berkas `11` dan `00`, modul `02b` belum terdaftar di `00`, dan `window.REVERB_CONFIG` belum dijelaskan di `11`.

- [ ] **Step 3: Update `11-REALTIME-WEBSOCKET-DAN-PRESENSI.md`**

Lakukan pembaruan pada `docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md`:
1. Ubah tautan `file:///` pada baris 31, 48, 49, dan 50 menjadi relative path:
   * `routes/channels.php` ➔ [`routes/channels.php`](../../routes/channels.php)
   * `SetUserOnlineOnLogin.php` ➔ [`SetUserOnlineOnLogin.php`](../../app/Listeners/SetUserOnlineOnLogin.php)
   * `SetUserOfflineOnLogout.php` ➔ [`SetUserOfflineOnLogout.php`](../../app/Listeners/SetUserOfflineOnLogout.php)
   * `UserPresenceService.php` ➔ [`UserPresenceService.php`](../../app/Services/UserPresenceService.php)
2. Perbarui Bagian 4 "Konfigurasi Client (`resources/js/echo.js`)":
   * Jelaskan arsitektur **Dual-Source Config Pattern**: Frontend memprioritaskan konfigurasi `window.REVERB_CONFIG` yang disuntikkan langsung oleh Blade shell ([`resources/views/app.blade.php`](../../resources/views/app.blade.php)).
   * Jelaskan alasannya: Menghindari masalah environment variable Vite (`import.meta.env`) yang sering tidak sinkron antara server local (HTTP/WS) dan server production (HTTPS/WSS).
   * Sertakan cuplikan kode nyata dari [`resources/js/echo.js`](../../resources/js/echo.js):
     ```javascript
     import Echo from 'laravel-echo';
     import Pusher from 'pusher-js';

     window.Pusher = Pusher;

     try {
         // Utamakan konfigurasi dari Blade Laravel agar host/port selalu cocok dengan .env server
         const fromLaravel = typeof window !== 'undefined' && window.REVERB_CONFIG;
         let wsHost = fromLaravel
             ? window.REVERB_CONFIG.host
             : (import.meta.env.VITE_REVERB_APP_HOST ?? import.meta.env.VITE_REVERB_HOST ?? window.location.hostname);

         if (typeof window !== 'undefined' && (wsHost === '0.0.0.0' || !wsHost)) {
             wsHost = window.location.hostname;
         }

         const wsPort = fromLaravel
             ? window.REVERB_CONFIG.port
             : (Number(import.meta.env.VITE_REVERB_APP_PORT ?? import.meta.env.VITE_REVERB_PORT) || 8080);
         const scheme = fromLaravel
             ? window.REVERB_CONFIG.scheme
             : (import.meta.env.VITE_REVERB_APP_SCHEME ?? import.meta.env.VITE_REVERB_SCHEME ?? 'http');
         const key = fromLaravel
             ? window.REVERB_CONFIG.key
             : (import.meta.env.VITE_REVERB_APP_KEY || 'production-key');

         const forceTLS = scheme === 'https';

         window.Echo = new Echo({
             broadcaster: 'reverb',
             key,
             wsHost,
             wsPort,
             wssPort: wsPort,
             forceTLS,
             enabledTransports: forceTLS ? ['wss'] : ['ws', 'wss'],
             disableStats: true,
             authEndpoint: '/broadcasting/auth',
         });
     } catch (error) {
         console.error('Failed to initialize Echo:', error);
     }
     ```

- [ ] **Step 4: Update `00-INDEX-DAN-PANDUAN-MEMBACA.md`**

Lakukan pembaruan pada `docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md`:
1. Pada tabel modul dokumentasi (baris 37-51):
   * Tambahkan baris baru untuk modul `02b`:
     ```markdown
     | **02b** | [`02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`](./02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md) | Panduan Transisi Frontend: Blade/jQuery ke React 19/Inertia v2, Wayfinder Routing, Form Management, Tailwind v4, Reverb Real-Time, & Debugging. |
     ```
   * Konversi seluruh tautan `file:///...` pada kolom dokumen menjadi tautan relatif dokumen lokal (`./01-ARSITEKTUR-DAN-TECH-STACK.md`, `./02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`, dst.).
2. Pada Bagian "Rekomendasi Urutan Belajar Developer Baru" (baris 54-66):
   * Perbarui urutan belajar:
     * **Hari ke-1 (Fondasi & Lingkungan Kerja):** Baca [`01-ARSITEKTUR-DAN-TECH-STACK.md`](./01-ARSITEKTUR-DAN-TECH-STACK.md) dan [`02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`](./02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md), lalu praktikkan instalasi di [`12-PANDUAN-SETUP-LOKAL-DAN-DEPLOYMENT.md`](./12-PANDUAN-SETUP-LOKAL-DAN-DEPLOYMENT.md).
     * **Hari ke-2 (Transisi Frontend & Modul Operasional IT):** Baca modul [`02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`](./02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md) untuk menguasai arsitektur React & Inertia, lalu pahami alur kerja ITIL pada [`03-MODUL-HELPDESK-ITIL-TICKETING.md`](./03-MODUL-HELPDESK-ITIL-TICKETING.md) dan pengelolaan aset pada [`04-MODUL-ASET-DAN-INVENTARIS.md`](./04-MODUL-ASET-DAN-INVENTARIS.md).
     * **Hari ke-3 (Modul Mutu, Naskah & Darurat):** Baca [`05`](./05-MODUL-SIMMUTU.md), [`06`](./06-MODUL-TATA-NASKAH-REGULASI.md), dan [`07`](./07-MODUL-EMERGENCY-PANIC-BUTTON.md).
     * **Hari ke-4 (Keamanan, Payroll, CMS & Real-time):** Baca [`08`](./08-MODUL-PATROLI-KEAMANAN.md), [`09`](./09-MODUL-PAYROLL-DAN-MONITORING-INFRASTRUKTUR.md), [`10`](./10-MODUL-WEB-OFFICIAL-DAN-CMS.md), dan [`11`](./11-REALTIME-WEBSOCKET-DAN-PRESENSI.md).
   * Pastikan seluruh tautan jadwal belajar menggunakan relative path (`./...`).

- [ ] **Step 5: Run test to verify it passes**

Jalankan skrip verifikasi:

```bash
python3 -c "
import sys, re, os

errors = []
files = ['docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md', 'docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md']

for path in files:
    with open(path) as f:
        content = f.read()
    if 'file:///' in content:
        errors.append(f'FAIL: {path} still has file:/// links')
    
    links = re.findall(r'\[.*?\]\((.*?)\)', content)
    for l in links:
        if l.startswith('http') or l.startswith('#'):
            continue
        clean_target = l.split('#')[0]
        # Skip 02b if it is not created yet
        if '02b-' in clean_target:
            continue
        full_target = os.path.normpath(os.path.join(os.path.dirname(path), clean_target))
        if not os.path.exists(full_target):
            errors.append(f'FAIL: Broken link in {path}: {l} -> {full_target}')

if errors:
    print('\n'.join(errors))
    sys.exit(1)
else:
    print('SUCCESS: Both 11 and 00 are updated, relative, and verified!')
"
```
Expected: PASS dengan output `SUCCESS: Both 11 and 00 are updated, relative, and verified!`.

- [ ] **Step 6: Commit changes for Task 2**

```bash
git add docs/developer-onboarding/11-REALTIME-WEBSOCKET-DAN-PRESENSI.md docs/developer-onboarding/00-INDEX-DAN-PANDUAN-MEMBACA.md
git commit -m "docs(onboarding): sync reverb dual-source echo config in 11 and index 02b in 00"
```

---

### Task 3: Create Modul 02b — Header, Bab 1: Pergeseran Paradigma & Kamus Padanan

**Files:**
- Create: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi kelengkapan Bab 1 & tautan relatif

**Interfaces:**
- Consumes: `docs/superpowers/specs/2026-09-03-frontend-onboarding-guide-design.md` (Bagian 1, 5 - Bab 1)
- Produces: Berkas modul `02b` dengan metadata, pengantar dual-audience, dan Bab 1 lengkap (TL;DR Matrix, Single Blade Shell, React Hooks, Konsep Inti React, dan Under the Hood Inertia XHR).

- [ ] **Step 1: Write the failing verification test for Task 3**

```bash
python3 -c "
import os, sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
if not os.path.exists(target):
    print(f'FAIL: {target} does not exist yet.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md does not exist yet.`.

- [ ] **Step 3: Create `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` with Header and Bab 1**

Tulis berkas baru `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` dengan isi lengkap:
* **Header & Metadata:**
  * Judul: `# ⚛️ Modul 02b: Panduan Frontend React 19 & Inertia.js v2 untuk Developer Laravel (Transisi dari Blade & jQuery)`
  * Badge & Metadata: Target Pembaca (Junior & Senior), Status Dokumen, Tech Stack (`React 19`, `Inertia v2`, `TypeScript`, `Tailwind v4`, `Radix UI`, `Wayfinder`).
  * Filosofi Desain Dual-Audience ("Skim or Deep Dive"): Penjelasan cara membaca dokumen bagi Senior (fokus TL;DR, Under the Hood) vs Junior (fokus analogi, step-by-step, Gotchas).
  * Mermaid Roadmap Bab (Diagram 6 Bab sesuai spek).
* **Bab 1: Pergeseran Paradigma & Kamus Padanan (Blade/jQuery vs React 19/Inertia v2)**:
  1. **TL;DR Matrix Padanan Cepat (Bagi Senior):**
     * Tabel komparasi lengkap: `@if`/`@else` vs Ternary/`&&`, `@foreach` vs `.map()`, `@csrf` vs otomatis XSRF cookie, `$('#id').val()` vs `useState`/`useForm`, `$('#id').html()` vs reaktif render, `route('name')` Ziggy vs `import { action } from '@/routes/...'` Wayfinder.
     * Callout `> [!TIP]` untuk developer senior mengenai mental model state-driven vs DOM-driven.
  2. **Arsitektur Single Blade Shell (`resources/views/app.blade.php`):**
     * Bedah berkas [`resources/views/app.blade.php`](../../resources/views/app.blade.php): Mengapa hanya ada satu file Blade di seluruh web portal.
     * Peran direktif `@inertiaHead` dan `@inertia` yang merender root container `<div id="app" data-page="...">`.
     * Mengapa Portal Sifast murni *Client-Side Rendered (CSR)* dan mengapa SSR tidak diperlukan untuk aplikasi portal intranet rumah sakit.
  3. **Anatomi & Intuisi React Hooks (Bagi Junior):**
     * Analogi "Colokan Listrik" yang menghubungkan fungsi biasa ke siklus hidup browser.
     * `useState`: State reaktif vs variabel biasa atau input DOM.
     * `useEffect`: Pengganti `$(document).ready()`, lifecycle mount/unmount, dan pentingnya fungsi *cleanup* (`return () => ...`) untuk mencegah memory leak.
     * `useForm` (Inertia): Form state management otomatis dengan penanganan `data`, `setData`, `post`, `processing`, dan `errors`.
     * `usePage` (Inertia): Akses data global shared props (`auth.user`, `flash`, `errors`) tanpa *prop drilling*.
     * Custom Hooks Portal Sifast: `useEcho`, `useUserPresence`, `useAppearance`.
  4. **Konsep Inti React yang Wajib Dikuasai:**
     * Aturan JSX/TSX: Atribut `className`, `htmlFor`, self-closing tag wajib (`<input />`, `<img />`), Fragment `<> ... </>`.
     * Komponen & Props: Mengapa Props bersifat *Read-Only (Immutable)* dan tidak boleh dimutasi langsung.
     * Controlled vs Uncontrolled Components: Mengapa input teks "membeku" jika tidak dipasangkan dengan `onChange={e => setData('field', e.target.value)}`.
     * Lifting State Up: Mengoper data dari anak ke induk melalui callback props.
     * Atribut wajib `key` pada `.map()`: Bagaimana Virtual DOM Diffing bekerja menggunakan identitas unik `key={item.id}` alih-alih index array.
     * React Context sebagai bus state global ringan.
     * TypeScript dasar untuk komponen: Definisi antarmuka `type Props = { ... }`.
  5. **Under the Hood: Protokol Navigasi Inertia (Bagi Senior):**
     * Cara kerja navigasi tanpa reload: Request XHR dengan header HTTP `X-Inertia: true`.
     * Respon backend berupa JSON payload berisi nama komponen dan props.
     * Partial Reloads via opsi `only: ['tickets']` untuk menghemat bandwidth.
     * Mekanisme sinkronisasi history browser via `pushState` vs `replaceState`.

- [ ] **Step 4: Run test to verify Task 3**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_strings = [
    '# ⚛️ Modul 02b:',
    'Bab 1: Pergeseran Paradigma & Kamus Padanan',
    'TL;DR Matrix',
    'Single Blade Shell',
    'React Hooks',
    'Controlled vs Uncontrolled',
    'X-Inertia',
]

missing = [s for s in required_strings if s not in content]
if missing:
    print('FAIL: Missing sections in Bab 1:', missing)
    sys.exit(1)

if 'file:///' in content:
    print('FAIL: Detected file:/// links in 02b!')
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in 02b Bab 1:', broken)
    sys.exit(1)

print('SUCCESS: Modul 02b Bab 1 is complete, relative, and link-verified!')
"
```
Expected: PASS dengan output `SUCCESS: Modul 02b Bab 1 is complete, relative, and link-verified!`.

- [ ] **Step 5: Commit changes for Task 3**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 1 paradigm shift and syntax cheatsheet"
```

---

### Task 4: Modul 02b — Bab 2: Bedah Kasus Nyata Modul Tiket ITIL

**Files:**
- Modify: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi akurasi baris kode `TicketController.php` & `resources/js/pages/tickets/`

**Interfaces:**
- Consumes: `app/Http/Controllers/TicketController.php:83-94`, `resources/js/pages/tickets/index.tsx:100-140`, `resources/js/pages/tickets/create.tsx:76-97`
- Produces: Bab 2 komprehensif pada `02b` yang menganalisis alur data produksi modul tiket dari Controller ke React Props, live filtering, form kompleks, dan rute Wayfinder.

- [ ] **Step 1: Write the failing verification test for Task 4**

```bash
python3 -c "
import sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

if 'Bab 2: Bedah Kasus Nyata Modul Tiket ITIL' not in content:
    print('FAIL: Bab 2 not yet implemented in 02b.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: Bab 2 not yet implemented in 02b.`.

- [ ] **Step 3: Implement Bab 2 in `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`**

Tambahkan Bab 2 ke dalam `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` mencakup:
1. **Peta Alur Data Controller ke React:**
   * Cuplikan baris nyata [`TicketController.php`](../../app/Http/Controllers/TicketController.php#L83-L94):
     ```php
     return Inertia::render('tickets/index', [
         'tickets' => $tickets,
         'statuses' => TicketStatus::active()->ordered()->get(),
         'priorities' => TicketPriority::active()->ordered()->get(),
         'tags' => TicketTag::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
         'filters' => $request->only(['status', 'priority', 'department', 'assignee', 'search', 'tag', 'category', 'subcategory', 'project', 'created_from', 'created_to', 'closed_from', 'closed_to', 'resolved_only', 'include_closed', 'draft']),
         'categories' => $this->getCategoriesForTicketList($user),
         'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
         'canExport' => $user->isAdmin() || $user->isStaff(),
         'canDelete' => $tickets->getCollection()->contains(fn (Ticket $ticket) => $ticket->can_delete),
     ]);
     ```
   * Bedah bagaimana setiap kunci array di backend secara otomatis diterima sebagai parameter destructuring pada komponen frontend [`resources/js/pages/tickets/index.tsx`](../../resources/js/pages/tickets/index.tsx#L100-L110):
     ```tsx
     export default function TicketsIndex({
         tickets,
         statuses,
         priorities,
         tags = [],
         categories = [],
         filters,
         projects = [],
         canExport = false,
         canDelete = false,
     }: Props) { ... }
     ```
   * Diagram Mermaid alur data: Browser Request ➔ Controller Eloquent Query ➔ Inertia::render Payload ➔ React Props ➔ DOM Render.
2. **Live Search & Filter Tanpa Reload (`preserveState: true`):**
   * Analisis mendalam fungsi `applyFilters` di [`resources/js/pages/tickets/index.tsx`](../../resources/js/pages/tickets/index.tsx#L130-L139):
     ```tsx
     const applyFilters = useCallback(
         (newFilters: Partial<TicketFilters>) => {
             router.get(
                 '/tickets',
                 { ...filters, ...newFilters },
                 { preserveState: true, replace: true }
             );
         },
         [filters]
     );
     ```
   * Penjelasan mengapa `preserveState: true` krusial: mencegah scroll window melompat ke atas dan menjaga fokus kursor pengguna saat mengetik di input pencarian.
   * Penjelasan opsi `replace: true` agar histori browser tidak dipenuhi entri URL setiap pergantian huruf pencarian.
3. **Bedah Form Kompleks ([`resources/js/pages/tickets/create.tsx`](../../resources/js/pages/tickets/create.tsx)):**
   * Analisis pemanggilan `useForm` dengan 17 properti state (baris 76-97) termasuk file upload `attachments: [] as File[]`.
   * Integrasi dinamis kategori dan subkategori menggunakan `useEffect` yang menyaring daftar subkategori saat `data.ticket_category_id` berubah.
   * Penanganan pesan kesalahan validasi server menggunakan komponen reusable `<InputError message={errors.title} />`.
4. **Wayfinder Routing Aktual pada Modul Tiket:**
   * Contoh impor fungsi rute: `import { index, create, show } from '@/routes/tickets';`.
   * Contoh pembuatan URL dinamis: `show(ticket.id).url` atau `show(ticket).url`.
   * Komparasi mengapa pendekatan ini jauh lebih unggul daripada hardcoded string `'/tickets/' + ticket.id` atau Ziggy `route('tickets.show', ticket.id)`.

- [ ] **Step 4: Run test to verify Task 4**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_strings = [
    'Bab 2: Bedah Kasus Nyata Modul Tiket ITIL',
    'TicketController.php',
    'Inertia::render(\'tickets/index\'',
    'preserveState: true',
    'applyFilters',
    'useForm',
    '@/routes/tickets',
]

missing = [s for s in required_strings if s not in content]
if missing:
    print('FAIL: Missing items in Bab 2:', missing)
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in Bab 2:', broken)
    sys.exit(1)

print('SUCCESS: Modul 02b Bab 2 is complete, accurate, and link-verified!')
"
```
Expected: PASS dengan output `SUCCESS: Modul 02b Bab 2 is complete, accurate, and link-verified!`.

- [ ] **Step 5: Commit changes for Task 4**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 2 case study of itil tickets module"
```

---

### Task 5: Modul 02b — Bab 3: Tutorial Hands-on CRUD Step-by-Step Modul Projects & Modernisasi ConfirmDialog

**Files:**
- Modify: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi tutorial CRUD & referensi `ProjectController.php`, `ConfirmDialog`

**Interfaces:**
- Consumes: `app/Http/Controllers/ProjectController.php`, `resources/js/pages/projects/index.tsx`, `resources/js/pages/projects/create.tsx`, `resources/js/components/confirm-dialog.tsx`
- Produces: Bab 3 lengkap pada `02b` yang menyajikan panduan step-by-step membangun fitur CRUD berbasis modul proyek riil dan refactoring konfirmasi hapus dari `confirm()` ke `ConfirmDialog`.

- [ ] **Step 1: Write the failing verification test for Task 5**

```bash
python3 -c "
import sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

if 'Bab 3: Tutorial Hands-on CRUD Step-by-Step' not in content:
    print('FAIL: Bab 3 not yet implemented in 02b.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: Bab 3 not yet implemented in 02b.`.

- [ ] **Step 3: Implement Bab 3 in `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`**

Tambahkan Bab 3 ke dalam `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` mencakup 5 langkah praktis:
1. **Langkah 1: Backend Controller ([`ProjectController.php`](../../app/Http/Controllers/ProjectController.php)):**
   * Penjelasan method `index` (paginasi `withQueryString()`), `create` (passing `statusOptions`), `store` (validasi FormRequest & redirect with flash), dan `destroy` (melepas relasi tiket lalu delete).
2. **Langkah 2: Definisi TypeScript Terstruktur:**
   * Menuliskan antarmuka type yang disiplin:
     ```tsx
     export type ProjectItem = {
         id: number;
         name: string;
         description: string | null;
         status: 'planning' | 'in_progress' | 'completed' | 'on_hold';
         tickets_count: number;
         created_by?: { id: number; name: string };
         created_at: string;
         updated_at: string;
     };

     export type PaginatedProjects = {
         data: ProjectItem[];
         current_page: number;
         last_page: number;
         total: number;
         links: { url: string | null; label: string; active: boolean }[];
     };

     type Props = {
         projects: PaginatedProjects;
         filters: { q: string; status: string | null };
     };
     ```
3. **Langkah 3: Membangun Halaman Index ([`resources/js/pages/projects/index.tsx`](../../resources/js/pages/projects/index.tsx)):**
   * Menggunakan wrapper `<AppLayout breadcrumbs={...}>`.
   * Menampilkan input filter pencarian dengan auto-submit / onDebounce.
   * Render tabel data dengan `.map((item) => <tr key={item.id}>...</tr>)`.
4. **Langkah 3b: Studi Kasus Refactoring Modernisasi Konfirmasi Hapus:**
   * Tunjukkan cara lama di [`resources/js/pages/projects/index.tsx:201`](../../resources/js/pages/projects/index.tsx#L201-L203):
     ```tsx
     // ❌ CARA LAMA (Kurang Elegan - Dialog Browser Bawaan):
     onClick={() => {
         if (confirm('Hapus rencana ini? Tiket yang terhubung tidak dihapus, hanya dilepas dari project.')) {
             router.delete(`/projects/${item.id}`);
         }
     }}
     ```
   * Bandingkan dengan refactoring modern menggunakan [`ConfirmDialog`](../../resources/js/components/confirm-dialog.tsx) (`@/components/confirm-dialog`):
     ```tsx
     // ✅ CARA MODERN (Accessible Radix UI Dialog):
     const [deleteTarget, setDeleteTarget] = useState<ProjectItem | null>(null);

     // Di dalam baris tabel:
     <Button variant="ghost" size="icon" onClick={() => setDeleteTarget(item)}>
         <Trash2 className="h-4 w-4 text-destructive" />
     </Button>

     // Komponen dialog di akhir JSX:
     <ConfirmDialog
         open={!!deleteTarget}
         onOpenChange={(open) => !open && setDeleteTarget(null)}
         title="Hapus Rencana Kerja?"
         description={`Apakah Anda yakin ingin menghapus "${deleteTarget?.name}"? Tiket terkait tidak akan dihapus, melainkan dilepas dari proyek ini.`}
         confirmLabel="Ya, Hapus"
         cancelLabel="Batal"
         variant="destructive"
         onConfirm={() => {
             if (deleteTarget) {
                 router.delete(`/projects/${deleteTarget.id}`);
             }
         }}
     />
     ```
5. **Langkah 4: Halaman Form Create ([`resources/js/pages/projects/create.tsx`](../../resources/js/pages/projects/create.tsx)):**
   * Formulir controlled menggunakan `const { data, setData, post, processing, errors } = useForm({ name: '', description: '', status: 'planning' })`.
   * Integrasi `<Input>`, `<Textarea>`, dan `<Select>`.
   * Menampilkan `<InputError message={errors.name} />`.
   * Disable tombol submit saat `processing` untuk mencegah double-post.
6. **Langkah 5: Menangkap Flash Notification:**
   * Cara menangkap `usePage().props.flash` di komponen layout atau hook untuk menampilkan toast notifikasi sukses pasca-redirect controller.

- [ ] **Step 4: Run test to verify Task 5**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_strings = [
    'Bab 3: Tutorial Hands-on CRUD Step-by-Step',
    'ProjectController.php',
    'ProjectItem',
    'ConfirmDialog',
    'deleteTarget',
    'useForm',
    'usePage().props.flash',
]

missing = [s for s in required_strings if s not in content]
if missing:
    print('FAIL: Missing items in Bab 3:', missing)
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in Bab 3:', broken)
    sys.exit(1)

print('SUCCESS: Modul 02b Bab 3 is complete, accurate, and link-verified!')
"
```
Expected: PASS dengan output `SUCCESS: Modul 02b Bab 3 is complete, accurate, and link-verified!`.

- [ ] **Step 5: Commit changes for Task 5**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 3 hands-on crud tutorial and confirm-dialog"
```

---

### Task 6: Modul 02b — Bab 4: Arsitektur Styling & Desain Antarmuka (Tailwind v4 & Radix)

**Files:**
- Modify: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi referensi `resources/css/app.css` & `resources/js/components/ui/`

**Interfaces:**
- Consumes: `resources/css/app.css:1-60`, `resources/js/lib/utils.ts`, `resources/js/components/ui/`
- Produces: Bab 4 komprehensif pada `02b` yang mendokumentasikan arsitektur Tailwind v4 CSS-first, token `@theme`, custom variant dark mode, komponen Radix UI, dan utility `cn()`.

- [ ] **Step 1: Write the failing verification test for Task 6**

```bash
python3 -c "
import sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

if 'Bab 4: Arsitektur Styling & Desain Antarmuka' not in content:
    print('FAIL: Bab 4 not yet implemented in 02b.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: Bab 4 not yet implemented in 02b.`.

- [ ] **Step 3: Implement Bab 4 in `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`**

Tambahkan Bab 4 ke dalam `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` mencakup:
1. **Tailwind CSS v4 (CSS-First Architecture):**
   * Mengapa file `tailwind.config.js` tidak ada: Tailwind v4 berpindah ke pendekatan *CSS-first* murni di mana konfigurasi dilakukan di file CSS induk.
   * Bedah berkas [`resources/css/app.css`](../../resources/css/app.css):
     * Impor inti: `@import 'tailwindcss';` dan `@import 'tw-animate-css';`.
     * Peta token tema di dalam blok `@theme`:
       * Font utama: `--font-sans: Inter, ui-sans-serif, ...;`.
       * Skala heading: `--text-h1: 3rem` hingga `--text-h6: 1.25rem`.
       * Skala display: `--text-display-sm` hingga `--text-display-2xl`.
2. **Mekanisme Dark Mode:**
   * Penjelasan directif varian kustom: `@custom-variant dark (&:is(.dark *));`.
   * Integrasi dengan script deteksi tema bawaan di [`resources/views/app.blade.php`](../../resources/views/app.blade.php) yang menambahkan class `.dark` pada elemen `<html>` sebelum React me-mount DOM (mencegah *flash of unstyled content*).
3. **Komponen Primitif UI (`resources/js/components/ui/`):**
   * Menjelaskan filosofi headless UI berbasis Radix UI Primitives: `Button`, `Input`, `Dialog`, `DropdownMenu`, `Badge`, `Select`, `Textarea`.
   * Accessible out-of-the-box (navigasi keyboard, screen reader focus, ARIA role otomatis).
4. **Helper Utility `cn()` (`clsx` + `tailwind-merge`):**
   * Analisis berkas [`resources/js/lib/utils.ts`](../../resources/js/lib/utils.ts):
     ```typescript
     import { type ClassValue, clsx } from 'clsx';
     import { twMerge } from 'tailwind-merge';

     export function cn(...inputs: ClassValue[]) {
         return twMerge(clsx(inputs));
     }
     ```
   * Mengapa developer **wajib** menggunakan `cn()` saat menggabungkan class: Menghindari benturan spesifisitas CSS (misal: menggabungkan `p-4` dengan `p-6`, `twMerge` memastikan class terakhir yang menang secara cerdas).

- [ ] **Step 4: Run test to verify Task 6**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_strings = [
    'Bab 4: Arsitektur Styling & Desain Antarmuka',
    'Tailwind CSS v4',
    '@theme',
    '--font-sans',
    '@custom-variant dark',
    'Radix UI',
    'cn(',
    'twMerge',
]

missing = [s for s in required_strings if s not in content]
if missing:
    print('FAIL: Missing items in Bab 4:', missing)
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in Bab 4:', broken)
    sys.exit(1)

print('SUCCESS: Modul 02b Bab 4 is complete, accurate, and link-verified!')
"
```
Expected: PASS dengan output `SUCCESS: Modul 02b Bab 4 is complete, accurate, and link-verified!`.

- [ ] **Step 5: Commit changes for Task 6**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 4 styling architecture and ui primitives"
```

---

### Task 7: Modul 02b — Bab 5: Reaktivitas Real-Time & WebSockets (Reverb & Echo)

**Files:**
- Modify: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi referensi `resources/js/echo.js` & `resources/js/hooks/`

**Interfaces:**
- Consumes: `resources/js/echo.js`, `resources/views/app.blade.php:49-79`, `routes/channels.php`
- Produces: Bab 5 komprehensif pada `02b` yang mendokumentasikan integrasi real-time Reverb, Dual-Source Config Pattern, dan cara berlangganan channel di React.

- [ ] **Step 1: Write the failing verification test for Task 7**

```bash
python3 -c "
import sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

if 'Bab 5: Reaktivitas Real-Time & WebSockets' not in content:
    print('FAIL: Bab 5 not yet implemented in 02b.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: Bab 5 not yet implemented in 02b.`.

- [ ] **Step 3: Implement Bab 5 in `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`**

Tambahkan Bab 5 ke dalam `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` mencakup:
1. **Arsitektur Integrasi Reverb & Echo di React:**
   * Diagram Mermaid siklus hidup penyiaran event: Laravel Event `ShouldBroadcast` ➔ Reverb WebSocket Daemon (port 8080) ➔ Browser Client `window.Echo` ➔ React Hook State Update.
2. **Dual-Source Config Pattern Tangguh ([`resources/js/echo.js`](../../resources/js/echo.js)):**
   * Mengapa kode konvensional (hanya membaca `import.meta.env`) rapuh saat di-deploy ke server staging/production dengan HTTPS dan proxy WSS.
   * Bedah mekanisme injeksi konfigurasi dari Blade shell [`resources/views/app.blade.php`](../../resources/views/app.blade.php#L49-L79) ke `window.REVERB_CONFIG`.
   * Bagaimana [`resources/js/echo.js`](../../resources/js/echo.js) memprioritaskan `window.REVERB_CONFIG` lalu fallback ke `import.meta.env`.
3. **Jenis-Jenis Channel & Contoh Praktis di React:**
   * Channel Publik: Digunakan untuk broadcast umum / *system-wide notices*.
   * Private Channel (`private-chat.{id}`): Memerlukan otentikasi Sanctum/Session via endpoint `/broadcasting/auth`.
   * Presence Channel (`presence-online-users`): Membedah hook custom `useUserPresence` untuk melacak siapa saja staf/admin yang sedang online secara real-time.
   * Pola *cleanup* penting: Memastikan komponen melakukan `Echo.leaveChannel(...)` saat komponen unmount untuk mencegah *duplicate listeners*.

- [ ] **Step 4: Run test to verify Task 7**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_strings = [
    'Bab 5: Reaktivitas Real-Time & WebSockets',
    'Dual-Source Config Pattern',
    'window.REVERB_CONFIG',
    'echo.js',
    'presence-online-users',
    'leaveChannel',
]

missing = [s for s in required_strings if s not in content]
if missing:
    print('FAIL: Missing items in Bab 5:', missing)
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in Bab 5:', broken)
    sys.exit(1)

print('SUCCESS: Modul 02b Bab 5 is complete, accurate, and link-verified!')
"
```
Expected: PASS dengan output `SUCCESS: Modul 02b Bab 5 is complete, accurate, and link-verified!`.

- [ ] **Step 5: Commit changes for Task 7**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 5 realtime websockets and dual-source reverb config"
```

---

### Task 8: Modul 02b — Bab 6: Anti-Patterns, Gotchas & Debugging Toolkit

**Files:**
- Modify: `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`
- Test: Skrip verifikasi kelengkapan seluruh 6 bab dan struktur dokumen `02b`

**Interfaces:**
- Consumes: `vite.config.ts:14-18`, `package.json:19`, `docs/superpowers/specs/2026-09-03-frontend-onboarding-guide-design.md` (Bagian 5 - Bab 6)
- Produces: Bab 6 lengkap pada `02b` yang merinci anti-patterns larangan keras, katalog gotchas junior, catatan performa compiler bagi senior, dan toolkit debugging.

- [ ] **Step 1: Write the failing verification test for Task 8**

```bash
python3 -c "
import sys

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

if 'Bab 6: Anti-Patterns, Gotchas & Debugging Toolkit' not in content:
    print('FAIL: Bab 6 not yet implemented in 02b.')
    sys.exit(1)
"
```

- [ ] **Step 2: Run test to verify it fails**

Run: Eksekusi skrip python di atas
Expected: Exit code 1 dengan pesan `FAIL: Bab 6 not yet implemented in 02b.`.

- [ ] **Step 3: Implement Bab 6 in `02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md`**

Tambahkan Bab 6 dan bagian penutup ke dalam `docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md` mencakup:
1. **Daftar Larangan Keras bagi Developer Transisi jQuery:**
   * ❌ Dilarang keras memakai `document.getElementById` atau jQuery `$('#id')` untuk membaca atau memanipulasi DOM. Wajib gunakan *state-driven UI*.
   * ❌ Dilarang melakukan mutasi state langsung (misal: `data.title = 'baru'`). Wajib gunakan `setData('title', 'baru')`.
   * ❌ Dilarang hardcode URL string (misal: `'/tickets/' + id`). Wajib gunakan Wayfinder function.
2. **Katalog Gotchas & Solusi Cepat (Penyelamat Developer Junior):**
   * *Gotcha 1:* "Input form tidak bisa diketik / beku" ➔ Terjadi karena komponen controlled tidak dipasangi handler `onChange`. Solusi: pasang `onChange={e => setData('field', e.target.value)}`.
   * *Gotcha 2:* "Error *Objects are not valid as a React child*" ➔ Terjadi saat merender objek langsung `{user}` alih-alih properti teks `{user.name}`.
   * *Gotcha 3:* "Layar putih kosong (*White Screen of Death*)" ➔ Cara mendiagnosa via Tab Console browser, mengecek TypeError `cannot read property of undefined` saat mengakses relasi Eloquent yang bernilai null.
3. **Catatan Performa untuk Developer Senior (React 19 Compiler):**
   * Mengulas konfigurasi `babel-plugin-react-compiler` pada [`vite.config.ts`](../../vite.config.ts#L14-L18).
   * Menjelaskan bahwa React 19 Compiler secara otomatis melakukan AST memoization pada level build.
   * Developer tidak perlu lagi membebani kode dengan manual `useMemo` dan `useCallback` untuk 95% komponen, kecuali untuk komputasi berat yang sangat spesifik (*escape hatch*).
4. **Toolkit & Trik Debugging Efisien:**
   * Cara inspeksi payload JSON Inertia via Network Tab browser (`Filter: Fetch/XHR`, periksa tab *Response* atau *Preview* untuk melihat props yang dikirim dari controller).
   * Menggunakan browser extension *React Developer Tools* (melihat pohon komponen dan props).
   * Panduan shortcut Artisan dan npm untuk verifikasi kode harian (`npm run types`, `npm run lint`, `npm run build`).
5. **Bagian Penutup & Navigasi Silang Dokumen:**
   * Tautan kembali ke modul panduan lain: [`00-INDEX`](./00-INDEX-DAN-PANDUAN-MEMBACA.md), [`01-ARSITEKTUR`](./01-ARSITEKTUR-DAN-TECH-STACK.md), [`02-STRUKTUR`](./02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md), [`03-TIKET`](./03-MODUL-HELPDESK-ITIL-TICKETING.md).

- [ ] **Step 4: Run test to verify Task 8**

```bash
python3 -c "
import os, sys, re

target = 'docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md'
with open(target) as f:
    content = f.read()

required_chapters = [
    'Bab 1: Pergeseran Paradigma & Kamus Padanan',
    'Bab 2: Bedah Kasus Nyata Modul Tiket ITIL',
    'Bab 3: Tutorial Hands-on CRUD Step-by-Step',
    'Bab 4: Arsitektur Styling & Desain Antarmuka',
    'Bab 5: Reaktivitas Real-Time & WebSockets',
    'Bab 6: Anti-Patterns, Gotchas & Debugging Toolkit',
    'React 19 Compiler',
    'White Screen of Death',
]

missing = [s for s in required_chapters if s not in content]
if missing:
    print('FAIL: Missing sections in 02b:', missing)
    sys.exit(1)

# Verify no file:///
if 'file:///' in content:
    print('FAIL: Detected file:/// links in 02b!')
    sys.exit(1)

# Check all relative links
links = re.findall(r'\[.*?\]\((.*?)\)', content)
broken = []
for l in links:
    if l.startswith('http') or l.startswith('#'):
        continue
    clean_target = l.split('#')[0]
    full_target = os.path.normpath(os.path.join(os.path.dirname(target), clean_target))
    if not os.path.exists(full_target):
        broken.append((l, full_target))

if broken:
    print('FAIL: Broken links in 02b:', broken)
    sys.exit(1)

print('SUCCESS: All 6 chapters of Modul 02b are complete, verified, and pristine!')
"
```
Expected: PASS dengan output `SUCCESS: All 6 chapters of Modul 02b are complete, verified, and pristine!`.

- [ ] **Step 5: Commit changes for Task 8**

```bash
git add docs/developer-onboarding/02b-PANDUAN-FRONTEND-REACT-INERTIA-UNTUK-LARAVEL-DEV.md
git commit -m "docs(onboarding): add module 02b chapter 6 anti-patterns gotchas and debugging toolkit"
```

---

### Task 9: Global Link Audit, Repository Integrity Check & Final Verification

**Files:**
- Modify: `docs/developer-onboarding/03-MODUL-HELPDESK-ITIL-TICKETING.md`, `docs/developer-onboarding/04-MODUL-ASET-DAN-INVENTARIS.md`, `docs/developer-onboarding/05-MODUL-SIMMUTU.md`, `docs/developer-onboarding/06-MODUL-TATA-NASKAH-REGULASI.md`, `docs/developer-onboarding/07-MODUL-EMERGENCY-PANIC-BUTTON.md`, `docs/developer-onboarding/08-MODUL-PATROLI-KEAMANAN.md`, `docs/developer-onboarding/09-MODUL-PAYROLL-DAN-MONITORING-INFRASTRUKTUR.md` (pembersihan menyeluruh tautan `file:///` menjadi tautan relatif).
- Test: Skrip audit menyeluruh ketiadaan `file:///`, validasi tautan relatif, serta `npm run types`, `npm run lint`, dan `npm run build`.

**Interfaces:**
- Consumes: Seluruh file Markdown di `docs/developer-onboarding/`
- Produces: Ekosistem onboarding yang 100% bebas dari `file:///`, seluruh tautan relatif valid, dan build frontend diverifikasi aman.

- [ ] **Step 1: Write and run the global audit script to locate remaining `file:///` links**

```bash
python3 -c "
import glob, re

md_files = glob.glob('docs/developer-onboarding/*.md')
found = 0
for f in sorted(md_files):
    with open(f) as fp:
        cnt = fp.read()
    matches = re.findall(r'file:///[^\)\s]+', cnt)
    if matches:
        print(f'{f}: {len(matches)} file:/// links')
        found += len(matches)

print(f'Total remaining file:/// links: {found}')
"
```
Expected: Mendeteksi sisa tautan `file:///` di dokumen `03` s/d `09`.

- [ ] **Step 2: Clean up remaining `file:///` links in `03` through `09`**

Konversikan seluruh tautan `file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/...` yang ditemukan pada modul `03`, `04`, `05`, `06`, `07`, `08`, dan `09` menjadi tautan relatif standar repositori (`../../app/...`).
Contoh:
* `file:///Users/.../app/Models/TicketType.php` ➔ `../../app/Models/TicketType.php`
* `file:///Users/.../app/Models/PatroliArea.php` ➔ `../../app/Models/PatroliArea.php`
* `file:///Users/.../app/Services/EmergencyFcmService.php` ➔ `../../app/Services/EmergencyFcmService.php`

- [ ] **Step 3: Run comprehensive verification script on all onboarding documents**

```bash
python3 -c "
import glob, re, os, sys

md_files = glob.glob('docs/developer-onboarding/*.md')
errors = []

for f in sorted(md_files):
    with open(f) as fp:
        cnt = fp.read()
    
    # 1. Ensure zero file:///
    f_matches = re.findall(r'file:///[^\)\s]+', cnt)
    if f_matches:
        errors.append(f'{f}: {len(f_matches)} file:/// links remaining!')

    # 2. Check all relative markdown links
    links = re.findall(r'\[.*?\]\((.*?)\)', cnt)
    for l in links:
        if l.startswith('http') or l.startswith('#'):
            continue
        clean = l.split('#')[0]
        full_target = os.path.normpath(os.path.join(os.path.dirname(f), clean))
        if not os.path.exists(full_target):
            errors.append(f'{f}: Broken link -> {l} (resolved: {full_target})')

if errors:
    print('AUDIT FAILED:\n' + '\n'.join(errors))
    sys.exit(1)
else:
    print(f'AUDIT PASSED: All {len(md_files)} onboarding documents are 100% relative, free of file:///, and perfectly resolved!')
"
```
Expected: PASS dengan output `AUDIT PASSED: All 14 onboarding documents are 100% relative, free of file:///, and perfectly resolved!`.

- [ ] **Step 4: Run frontend verification suite**

Pastikan integritas kode TypeScript dan build sistem tidak mengalami regresi:

```bash
npm run types
npm run lint
npm run build
```
Expected: Seluruh perintah selesai dengan kode keluar 0 (sukses).

- [ ] **Step 5: Commit changes for Task 9**

```bash
git add docs/developer-onboarding/
git commit -m "docs(onboarding): eliminate all local file links across onboarding docs and verify link integrity"
```

---

## Plan Self-Review & Verification

1. **Spec coverage check:**
   * Modul baru `02b` dengan 6 bab lengkap? ➔ Dicakup secara modular pada Task 3, 4, 5, 6, 7, 8.
   * Modul existing `00`, `01`, `02`, `11` diaudit dan diperbarui? ➔ Dicakup pada Task 1 dan Task 2.
   * Penghapusan miskonsepsi sintaks Ziggy dan koreksi ke Wayfinder? ➔ Dicakup pada Task 1, 4, dan 8.
   * Koreksi Dual-Source Echo Reverb (`window.REVERB_CONFIG`)? ➔ Dicakup pada Task 2 dan 7.
   * Penghapusan 100% tautan `file:///` di seluruh repositori onboarding? ➔ Dicakup pada Task 1, 2, 3, 4, 5, 6, 7, 8, dan 9.
   * Contoh kode nyata dari `TicketController.php`, `ProjectController.php`, `ConfirmDialog`, `echo.js`, `app.blade.php`? ➔ Dicakup pada Task 3, 4, 5, 6, 7.
2. **Placeholder scan:**
   * Bebas dari "TBD", "TODO", "implement later", dan deskripsi mengambang. Setiap langkah dilengkapi skrip verifikasi, path file spesifik, dan instruksi penulisan kode nyata.
3. **Type consistency:**
   * Seluruh penamaan tipe (`ProjectItem`, `PaginatedProjects`, `TicketFilters`, `Props`) konsisten antara Task 4 dan 5 dan sesuai dengan skema TypeScript proyek aktif.
