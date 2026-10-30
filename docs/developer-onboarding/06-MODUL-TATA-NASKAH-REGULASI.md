# 📜 Modul 06: Tata Naskah Dinas dan Regulasi Rumah Sakit

Modul Tata Naskah mengelola standarisasi pembuatan, verifikasi berjenjang, penomoran otomatis, penandatanganan, dan distribusi dokumen regulasi internal RS (Kebijakan, Pedoman, Panduan, SPO, dan Surat Keputusan).

---

## 1. Siklus Hidup dan State Transition Naskah Dinas

```mermaid
stateDiagram-v2
    [*] --> Draft : Pembuat Mengajukan Naskah (can_buat_dokumen)
    Draft --> Review : Diajukan untuk Telaah Dokumen
    Review --> Revision : Ada Catatan Perbaikan
    Revision --> Review : Draft Diperbaiki
    Review --> ApprovedMutu : Disetujui Komite Mutu (can_approve_dokumen_mutu)
    ApprovedMutu --> Signed : Ditandatangani TTE Direktur (can_tte_dokumen)
    Signed --> Terbit : Nomor Naskah Diterbitkan & Didistribusikan
    Terbit --> Diperbarui : Terbit Versi Dokumen Baru
    Terbit --> Dicabut : Regulasi Dinyatakan Tidak Berlaku
```

---

## 2. Struktur Data dan Versi Dokumen

```mermaid
erDiagram
    DOKUMENS ||--o{ VERSI_DOKUMENS : "memiliki riwayat versi"
    DOKUMENS ||--o{ DISTRIBUSI_DOKUMENS : "didistribusikan ke"
    DOKUMENS ||--o{ META_REGULASIS : "mengacu regulasi induk"
    
    KODE_UNIT_KLASIFIKASIS ||--o{ DOKUMENS : "klasifikasi unit"
    KODE_SIFAT_NASKAHS ||--o{ DOKUMENS : "sifat naskah"
    COUNTER_NOMOR_DOKUMENS ||--o{ DOKUMENS : "menentukan nomor urut"
```

### A. Fitur Multi-Versi (`VersiDokumen`)
* Setiap kali dokumen SPO atau Regulasi mengalami revisi berkala, sistem tidak menimpa dokumen lama.
* Sistem membuat entitas `VersiDokumen` baru (misal: Rev 00 -> Rev 01) lengkap dengan riwayat tanggal revisi dan catatan perubahan (*change log*).
* File PDF fisik tersimpan di disk aman (`storage/app/tatanaskah/`) dan hanya dapat diunduh melalui controller berautentikasi.

### B. Otomasi Penomoran Surat Dinamis
Format penomoran naskah dinas mengikuti standar tata naskah Muhammadiyah / RS:
$$\text{Nomor} = \text{[Urutan] / [Sifat] / [Unit Klasifikasi] / RS-ASF / [Bulan Romawi] / [Tahun]}$$
* Dihitung secara *atomic* menggunakan tabel [`CounterNomorDokumen`](../../app/Models/CounterNomorDokumen.php) untuk mencegah duplikasi nomor meskipun dibuat secara bersamaan (*race condition*).

---

## 3. Integrasi Single Sign-On (SSO) SIKAT

Portal Sifast terhubung erat dengan **SIKAT** (Sistem Informasi Kearsipan & Surat Menyurat Terpadu) melalui protokol SSO berbasis HMAC-SHA256:

```mermaid
sequenceDiagram
    autonumber
    actor User as Pegawai
    participant Portal as Portal Sifast
    participant SIKAT as SIKAT Surat Menyurat

    User->>Portal: Klik Link "Buka SIKAT"
    Portal->>Portal: Generate SSO Token (HMAC-SHA256 + NIK + Timestamp + Secret)
    Portal->>SIKAT: Redirect ke SIKAT URL + ?sso_token=...
    SIKAT->>SIKAT: Validasi Hash Signature & Expiry (TTL 90 detik)
    SIKAT->>User: Set Session Login SIKAT (Tanpa Input Password Ulang)
```

### File Kunci Integrasi SSO:
* [`SikatSsoTokenService.php`](../../app/Services/SikatSsoTokenService.php) — Generator dan validator token.
* [`SikatInboundSsoController.php`](../../app/Http/Controllers/Integrations/SikatInboundSsoController.php) — Menerima login SSO masuk dari SIKAT ke Portal.
* [`SikatSsoRedirectController.php`](../../app/Http/Controllers/Integrations/SikatSsoRedirectController.php) — Mengarahkan pengguna dari Portal ke SIKAT.
