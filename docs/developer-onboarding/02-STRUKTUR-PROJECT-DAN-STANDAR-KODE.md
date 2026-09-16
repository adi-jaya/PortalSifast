# 📂 Modul 02: Struktur Project, Standar Kode, dan Autentikasi/Otorisasi

Dokumen ini menjelaskan struktur direktori kode sumber, sistem Role-Based Access Control (RBAC) dengan *granular permission flags*, standar penulisan kode, serta integrasi rute type-safe antara Laravel dan React.

---

## 1. Peta Struktur Direktori

### 📁 Backend (`app/`)
```
app/
├── Actions/                 # Single-purpose action classes (Fortify auth, etc.)
├── Broadcasting/            # Custom broadcast channel authenticators
├── Console/Commands/        # Artisan CLI commands (sync SIMRS, auto-close tickets, aggregasi)
├── Events/                  # Domain events (MessageSent, TicketCreated, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Api/             # REST API Controllers (Sifast Kepegawaian, Mobile, IoT Agent)
│   │   ├── Integrations/    # SSO SIKAT Inbound & Redirect Controllers
│   │   ├── Settings/        # Konfigurasi sistem (Penyusutan aset, kategori monitoring)
│   │   ├── Tatanaskah/      # Controller modul regulasi & naskah dinas
│   │   ├── WebOfficial/     # CMS controller website resmi RS
│   │   └── *.php            # Controllers modul utama (Ticket, Aset, SIMMUTU, Patroli, dll.)
│   └── Middleware/          # Middleware proteksi modul granular
├── Listeners/               # Event listeners (presence tracking login/logout)
├── Models/                  # 95+ Eloquent models
├── Notifications/           # Telegram & Database notifications
├── Observers/               # Eloquent model observers (AsetObserver, dsb.)
├── Providers/               # Service providers (AppServiceProvider, FortifyServiceProvider)
└── Services/                # Lapisan logika bisnis (Domain Services)
    ├── Agent/               # Service daemon RS-Agent monitoring
    ├── Instagram/           # Integrasi Instagram Graph API
    ├── Inventaris/          # Qr generator & photo resolver SIMRS
    ├── Monitoring/          # Tianji metrics fetcher
    ├── Patroli/             # Layanan kalkulasi patroli satpam
    ├── Simrs/               # Service resolver data SIMRS Khanza
    ├── Tatanaskah/          # Logic transisi status & penomoran surat
    └── WebOfficial/         # Media storage & article services
```

### 📁 Frontend (`resources/js/`)
```
resources/js/
├── actions/                 # Inertia/form actions helper
├── components/              # Reusable UI component library (Shadcn/Radix/HeadlessUI)
│   ├── template-sidebar.tsx # Sidebar Desktop aktif (konsumsi portal-nav.ts)
│   ├── template-mobile-nav.tsx # Sidebar Mobile aktif (konsumsi portal-nav.ts)
│   ├── app-sidebar.tsx      # [LEGACY / DEPRECATED - Starter Kit Only, JANGAN DIEDIT]
│   ├── ui/                  # Atomic primitives (button, dialog, input, select, table)
│   └── ...
├── contexts/                # React Contexts (User Presence, Theme, Audio Alert)
├── hooks/                   # Custom React Hooks (useEcho, useUserPresence, useFilter)
├── layouts/                 # AppLayout, AppSidebarLayout, AuthLayout, SettingsLayout
├── lib/                     # Utilities (cn, formatters, date helpers)
│   └── portal-nav.ts        # SINGLE SOURCE OF TRUTH seluruh navigasi SIMRS (Desktop & Mobile)
├── pages/                   # Inertia Page Components (30+ sub-modul)
│   ├── aset/                # Manajemen Aset Portal
│   ├── emergency-reports/   # Command Center Panic Button
│   ├── patroli/             # Patroli Security & QR Scan
│   ├── payroll/             # Import & Approval Gaji
│   ├── simmutu/             # Sistem Informasi Manajemen Mutu
│   ├── tatanaskah/          # Naskah Dinas & Regulasi
│   ├── tickets/             # ITIL Helpdesk Ticketing Board & List
│   └── web-official/        # CMS Website RS
├── types/                   # TypeScript interface & type definitions
└── routes/                  # Auto-generated type-safe route functions dari Laravel
```

---

## 2. Model Autentikasi dan Otorisasi (RBAC + Granular Flags)

Portal Sifast menggabungkan **Role Tingkat Tinggi** dengan **Flag Hak Akses Granular** pada model [`User`](../../app/Models/User.php):

### A. Role Utama (`role`)
1. `admin` — Administrator sistem / Koordinator IT / Manajemen Operasional.
2. `staff` — Teknisi IT, Tim Pemeliharaan Sarana (IPS-RS), Tim Medis / Petugas IGD / Driver.
3. `pemohon` — Pegawai umum rumah sakit yang membuat tiket perbaikan atau melihat informasi internal.
4. `service` — Akun integrasi sistem eksternal (misal: token sistem kepegawaian).

### B. Konsep Superadmin
Superadmin didefinisikan secara dinamis melalui environment variable `AUTH_SUPERADMIN_EMAILS` (comma-separated list).
* Method `User::isSuperAdmin()` memeriksa apakah email user terdaftar di daftar tersebut.
* Superadmin otomatis memiliki akses penuh ke modul Payroll, SIMMUTU, Patroli, Manajemen Pengguna, dan CMS Website.

### C. Granular Permission Flags pada Tabel `users`
Untuk memberikan fleksibilitas lintas departemen tanpa mengubah role utama:
* `can_access_payroll` — Mengakses data penggajian, import gaji, dan audit slip.
* `can_access_patroli` — Mengakses konfigurasi patroli satpam, template, dan rekap checklist.
* `can_manage_mutu` — Administrator Komite Mutu (kelola kategori & indikator mutu).
* `can_input_mutu` — PIC / Inputter capaian indikator mutu di unit kerja/departemen.
* `can_view_mutu_dashboard` — Melihat grafik capaian dan rekapitulasi mutu rumah sakit.
* `can_manage_web_official` — Pengelola konten artikel, promo, kamar inap, dan feedback web publik.
* `can_buat_dokumen`, `can_review_dokumen`, `can_approve_dokumen_mutu`, `can_tte_dokumen`, `can_manage_tatanaskah`, `can_konfirmasi_terima_dokumen` — Alur kerja tata naskah regulasi dinas.

### D. Middleware Otorisasi Utama
* `auth` & `verified` — Memastikan sesi web aktif dan terverifikasi.
* `payroll.access` — Memvalidasi `canAccessPayroll()`.
* `patroli.access` — Memvalidasi `canAccessPatroli()`.
* `simmutu.view`, `simmutu.manage`, `simmutu.input` — Menjaga akses berjenjang modul SIMMUTU.
* `webofficial.admin` — Proteksi CMS website.
* `auth:sanctum` — Otentikasi stateless Bearer Token untuk endpoint REST API kepegawaian & mobile.
* `auth.agent` — Otentikasi daemon `rs-agent` menggunakan enrollment key / device API key.

---

## 3. Integrasi Rute Type-Safe (Laravel Wayfinder)

Portal Sifast menggunakan paket `@laravel/vite-plugin-wayfinder` yang secara otomatis meng-generate fungsi rute TypeScript setiap kali ada rute Laravel baru di `routes/web.php` atau `routes/api.php`. Setiap controller atau kelompok rute Laravel menghasilkan modul TypeScript di `@/routes/<controller-path>` (contoh: `@/routes/tickets`) dengan method `.url` dan helper form type-safe.

### Cara Penggunaan di Frontend React:
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
* **Keuntungan:** Tidak ada typo URL, parameter URL divalidasi oleh TypeScript compiler, dan refactor endpoint backend akan langsung memicu error build jika frontend belum disesuaikan.

---

## 4. Standar dan Konvensi Penulisan Kode

### A. Format & Linting
* **PHP:** Jalankan `npm run lint` atau `vendor/bin/pint --parallel` sebelum commit. Semua kode mengikuti standar Laravel Pint.
* **TypeScript / React:** Jalankan `npm run format` (Prettier) dan `npm run lint` (ESLint 9).
* **Type Checking:** Jalankan `npm run types` (`tsc --noEmit`) untuk memastikan tidak ada kesalahan tipe data.

### B. Transaksi Database & Audit Trail
* Operasi data yang melibatkan lebih dari satu tabel **wajib** dibungkus dalam `DB::transaction(function () { ... })`.
* Gunakan model Audit Log khusus pada modul sensitif:
  * `ActivityLog` untuk aktivitas dashboard.
  * `PayrollAuditLog` untuk tracking setiap impor, approval, rollback, atau modifikasi data gaji.
  * `PanicAuditLog` untuk merekam kronologi respon darurat per detik.
