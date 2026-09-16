# AGENTS.md - Portal Sifast (RS Aisyiyah Siti Fatimah Tulangan)

This file contains architectural guidelines, development commands, and critical rules for developers and AI agents working on Portal Sifast.

---

## 🚨 GOLDEN RULES (CRITICAL ARCHITECTURAL CONSTRAINTS)

### 1. Navigasi Sidebar SIMRS (Single Source of Truth)
- ❌ **DILARANG KERAS** menambahkan, memodifikasi, atau mendaftarkan menu navigasi pada [`resources/js/components/app-sidebar.tsx`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/components/app-sidebar.tsx).
  - Berkas tersebut adalah artefak bawaan starter-kit yang **TIDAK PERNAH DIRENDER** pada tata letak aktif aplikasi SIMRS.
- ✅ **SELURUH NAVIGASI WAJIB DIDAFTARKAN DI [`resources/js/lib/portal-nav.ts`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/lib/portal-nav.ts)** (*Single Source of Truth*).
  - Layout aktif aplikasi ([`resources/js/layouts/app/app-sidebar-layout.tsx`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/layouts/app/app-sidebar-layout.tsx)) merender [`TemplateSidebar`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/components/template-sidebar.tsx) (desktop) dan [`TemplateMobileNav`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/components/template-mobile-nav.tsx) (mobile). Keduanya membaca konfigurasi navigasi terpusat dari `portal-nav.ts`.
  - Item menu tunggal/utama: daftarkan ke `mainNavItems`.
  - Modul bertingkat: daftarkan ke `moduleGroups` atau melalui dedicated group builder di `buildVisibleModuleGroups(permissions)`.
  - Selalu verifikasi perubahan navigasi dengan: `vendor/bin/pest tests/Feature/PortalNavParityTest.php`.

### 2. Styling & Desain UI (Tailwind CSS v4)
- **Tanpa `tailwind.config.js`**: Konfigurasi tema dan token visual ditulis murni di blok `@theme` pada [`resources/css/app.css`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/css/app.css).
- **Wajib gunakan helper `cn()`** dari [`resources/js/lib/utils.ts`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/resources/js/lib/utils.ts) untuk class merging / conditional classes.

### 3. Dual Database Connection (Khanza Read-Only)
- Koneksi `simrs` (Khanza) bersifat **STRICTLY READ-ONLY**. Dilarang melakukan operasi write/insert/update/delete atau migration ke database Khanza.
- Seluruh mutasi data dan pembuatan tabel baru HANYA diizinkan pada koneksi default `mysql` (Portal Sifast DB).

---

## 🛠️ Tech Stack Overview

- **Backend**: Laravel 12, PHP 8.2+, Pest 3, Fortify, Sanctum, Reverb (WebSocket).
- **Frontend**: React 19, Inertia.js v2, TypeScript 5+, Tailwind CSS v4, Radix UI Primitives.
- **Routing**: Laravel Wayfinder (`@laravel/vite-plugin-wayfinder` menghasilkan rute type-safe di `@/routes/*`).
- **Database**: Dual Connection — `mysql` (Portal Sifast DB) & `simrs` (Khanza Read-Only).

---

## ⚡ Developer Commands & RTK Usage

Proyek ini menggunakan **RTK (Rust Token Killer)** sebagai token-optimized CLI proxy:

```bash
# Meta RTK commands
rtk gain              # Show token savings analytics
rtk gain --history    # Show command usage history with savings
rtk discover          # Analyze history for missed opportunities
rtk proxy <cmd>       # Execute raw command without filtering (debugging)

# Testing (Pest)
vendor/bin/pest                                      # Run all tests
vendor/bin/pest tests/Feature/PortalNavParityTest.php # Parity test navigasi
vendor/bin/pest --filter=<TestName>                  # Run specific test

# Frontend Build & Verification
npm run dev           # Start Vite development server
npm run build         # Build production assets
npm run types         # Run TypeScript check (tsc --noEmit)
npm run lint          # Run ESLint check
npm run format        # Run Prettier code formatting

# Backend & Linting
vendor/bin/pint --dirty   # Format modified PHP files
php artisan wayfinder:generate # Regenerate type-safe route definitions
```

---

## 🔍 Git Auditing & Billing Rules

- **Audit all branches:** Always check all active and remote branches using `git log --all --no-merges` to ensure no unmerged work or active feature branches are missed.
- **Inspect actual code changes:** Verify file modifications using `git show --stat <commit_hash>` to determine true feature complexity rather than relying solely on commit message subjects.
- **Cost Estimation & Value-Based Billing:** Scale feature pricing relative to the overall project baseline contract size. Acknowledge AI Agent efficiency while protecting developer margins based on delivered value.

---

## ✅ Definition of Done (Verification Checklist)

Sebelum menandai pekerjaan selesai atau menyerahkan kode untuk review:
1. **Navigasi Paritas**: `vendor/bin/pest tests/Feature/PortalNavParityTest.php` (lulus tanpa regresi).
2. **Pengujian Domain**: `vendor/bin/pest --filter=<FeatureTest>` (lulus seluruh pengujian unit/feature terkait).
3. **Kompilasi Frontend**: `npm run build` (lulus tanpa error kompilasi Vite/TypeScript).
4. **Format & Gaya Kode**: `vendor/bin/pint --dirty` (untuk PHP) & `npm run format` (untuk frontend).
5. **Route Wayfinder**: `php artisan wayfinder:generate` (jika menambahkan rute Laravel baru).
