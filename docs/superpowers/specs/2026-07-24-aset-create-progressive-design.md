# Desain: Tambah Aset — Progressive Single Page (multi-ruang)

**Date:** 2026-07-24  
**Status:** Approved · Implemented (multi-ruang per unit)  
**Stack:** Laravel 12 + Inertia React (PortalSifast)  
**Halaman:** `/aset/create`

---

## Overview

Alur **bikin aset baru**: satu halaman progresif. Detail barang shared di `aset_barang`; ruang + nomor seri per unit di `aset`.

---

## Keputusan terkunci

| Topik | Keputusan |
|-------|-----------|
| Identifikasi | Katalog ASPAK / non-alkes (bikin baru) |
| Detail barang | Shared: nama, merk → tipe, kategori, produsen, harga, distributor, foto |
| Jumlah | Satu master barang, N unit |
| Penempatan | **Satu ruang picker per unit** (+ serial opsional) |
| Wajib simpan | Kelas + leaf katalog + ruang tiap unit |
| Wizard 3-step | Dihapus |
| Backend | `aset_ruang_id_list` + fallback `aset_ruang_id` tunggal |

---

## Alur UI

1. Kelas + katalog  
2. Detail barang (muncul setelah katalog)  
3. Jumlah unit + tahun registrasi  
4. N baris unit: ruang * · no. seri  
5. Simpan  

---

## Validasi

- Client: Simpan aktif jika katalog + setiap unit punya ruang.  
- Server create: `aset_ruang_id_list` panjang ≥ jumlah; tiap slot filled; atau expand dari `aset_ruang_id` tunggal.  
- Update: tetap `aset_ruang_id` wajib.

---

## Acceptance

1. Dua unit, dua ruang berbeda → satu `aset_barang`, dua `aset` dengan ruang/serial/kode sesuai.  
2. Single `aset_ruang_id` (tanpa list) tetap membuat batch di ruang yang sama.  
3. List ruang lebih pendek dari jumlah → error validasi.
