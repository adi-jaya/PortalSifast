# Progressive Aset Create Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline) or subagent-driven-development. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the 3-step `/aset/create` wizard with a single progressive page: required fields above the fold, optional details in a closed collapse.

**Architecture:** UI-only rewrite of `resources/js/pages/aset/create.tsx`. Keep `StoreAsetRequest` + `BuatAsetBatch`. Reuse catalog/master select components. Auto-open detail collapse when detail-field validation errors exist.

**Tech Stack:** Laravel 12, Inertia React v2, Pest 4, existing SearchSelect / CreatableSearchSelect / Aspak & NonAlkes leaf selects.

**Spec:** `docs/superpowers/specs/2026-07-24-aset-create-progressive-design.md`

## Global Constraints

- Wajib simpan: kelas + leaf katalog + ruang + jumlah (≥1)
- Wizard 3-step dihapus; mode barang existing tidak dikembalikan
- Backend store contract unchanged
- menuPortal on selects retained

## Files

- Modify: `resources/js/pages/aset/create.tsx`
- Modify: `docs/superpowers/specs/2026-07-24-aset-create-progressive-design.md` (status → Approved)
- Test: `tests/Feature/Aset/AsetAspakCreateTest.php`, `tests/Feature/Aset/AsetNonAlkesTest.php` (regression run)
- Create: `docs/superpowers/plans/2026-07-24-aset-create-progressive.md` (this file)

---

### Task 1: Rewrite create.tsx to progressive layout

**Files:**
- Modify: `resources/js/pages/aset/create.tsx`

**Produces:** Single-page form with sticky Simpan; `detailOpen` state; `switchKelas` clears master FKs; auto-open details on detail errors.

- [x] **Step 1:** Remove `step` / `STEPS` / step rail / Lanjut-Kembali navigation
- [x] **Step 2:** Layout wajib: ModeCards → katalog → ringkas → ruang + jumlah (+ tahun_registrasi default year, server-required)
- [x] **Step 3:** Collapsible “Lengkapi detail” containing nama, merk/tipe, kategori/produsen, kalibrasi, harga/asal/tanggal/status, serial, distributor/AKL/foto, nested regulasi
- [x] **Step 4:** `canSubmit` = leaf (per kelas) + ruang + !processing; sticky footer Simpan + Batal
- [x] **Step 5:** On mount/errors change, if any detail-field error key present → `setDetailOpen(true)`
- [x] **Step 6:** `switchKelas` also clears merk/jenis/kategori/produsen

### Task 2: Verify + build

- [x] Run: `php artisan test tests/Feature/Aset/AsetAspakCreateTest.php tests/Feature/Aset/AsetNonAlkesTest.php tests/Feature/Aset/AsetMerkJenisCascadeTest.php`
- [x] Run: `npm run build`
- [x] Mark spec status Approved
