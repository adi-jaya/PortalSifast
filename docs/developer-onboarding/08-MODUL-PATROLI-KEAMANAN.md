# 🛡️ Modul 08: Modul Patroli Keamanan Digital

Modul Patroli Keamanan mendigitalkan seluruh aktivitas ronda satpam dan petugas keamanan rumah sakit menggunakan sistem **Check-in Titik QR Code** dan **Formulir Checklist Dinamis**.

---

## 1. Alur Kerja Patroli Satpam

```mermaid
flowchart TD
    Start[Satpam Memulai Shift Patroli] --> Scan[Pindai QR Code di Titik Ruangan / Pos]
    Scan --> Resolve[Sistem Memvalidasi Kode Ruang & Template Checklist]
    Resolve --> Form[Muncul Formulir Checklist di Smartphone]
    Form --> Inspect[Pemeriksaan Fisik: Pintu, Jendela, APAR, Kebersihan, Lampu]
    Inspect --> Decision{Ada Temuan Rusak / Bahaya?}
    Decision -- Ya --> Foto[Wajib Foto Temuan & Tulis Deskripsi Insiden]
    Decision -- Tidak --> Submit[Submit Check-in]
    Foto --> Submit
    Submit --> Save[(Tersimpan di Server & Terhubung ke Laporan Monitoring)]
```

---

## 2. Struktur Data Modul Patroli

```mermaid
erDiagram
    PATROLI_AREAS ||--o{ PATROLI_RUANGS : "membawahi"
    PATROLI_TEMPLATES ||--o{ PATROLI_TEMPLATE_ITEMS : "memiliki butir checklist"
    PATROLI_RUANGS ||--o{ PATROLI_CHECKINS : "lokasi checkin"
    USERS ||--o{ PATROLI_CHECKINS : "petugas patroli"
    PATROLI_CHECKINS ||--o{ PATROLI_CHECKIN_ITEMS : "berisi hasil pemeriksaan"
    PATROLI_TEMPLATE_ITEMS ||--o{ PATROLI_CHECKIN_ITEMS : "mengisi butir"
```

### Entitas Kunci:
1. **[`PatroliArea`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Models/PatroliArea.php):** Pengelompokan zona gedung (Lantai 1, Rawat Inap, Basement & Parkir, Farmasi & Gudang).
2. **[`PatroliRuang`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Models/PatroliRuang.php):** Titik fisik penempelan QR stiker. Memiliki fungsi cetak label otomatis dengan format SVG/PNG.
3. **[`PatroliTemplate`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Models/PatroliTemplate.php):** Paket pertanyaan checklist yang fleksibel dan dapat disesuaikan tanpa perlu mengubah struktur tabel (*schema-free questionnaire*).
4. **[`PatroliCheckin`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Models/PatroliCheckin.php):** Bukti kehadiran fisik petugas di lokasi tertentu lengkap dengan timestamp server.

---

## 3. Endpoint API Mobile Patroli

Prefix: `/api/sifast/patroli` (Sanctum Auth):
* `GET /me` — Informasi profil satpam & ringkasan shift hari ini.
* `GET /titik` — Daftar seluruh titik ruangan yang wajib dikunjungi.
* `POST /resolve-qr` — Menerjemahkan payload raw QR Code hasil kamera smartphone menjadi ID Ruang.
* `GET /scan/{ruang}` — Mengambil formulir checklist aktif untuk ruangan tersebut.
* `POST /checkin` — Menyimpan isian checklist, catatan, dan foto bukti pemeriksaan.
* `GET /laporan/export` — Ekspor rekapitulasi patroli bulanan ke format Excel/PDF untuk laporan Kepala Keamanan.
