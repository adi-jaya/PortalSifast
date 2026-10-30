# Portal Sifast

Portal administrasi internal **RS Aisyiyah Siti Fatimah** — aplikasi web terpadu untuk helpdesk IT, manajemen aset, kepegawaian, mutu, dan layanan operasional rumah sakit.

**Stack:** Laravel 12 · Inertia.js v2 · React · TypeScript · Tailwind CSS v4 · MySQL · Pest

---

## Daftar Isi

- [Gambaran Umum](#gambaran-umum)
- [Fitur](#fitur)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Membuat User Pertama (Admin)](#membuat-user-pertama-admin)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Konfigurasi Penting](#konfigurasi-penting)
- [Pengembangan & Testing](#pengembangan--testing)

---

## Gambaran Umum

Portal Sifast menghubungkan beberapa sistem operasional RS ke satu antarmuka web:

| Database | Fungsi |
|----------|--------|
| **MySQL (default)** | Data portal: user, tiket, aset portal, payroll, patroli, dll. |
| **MySQL `dbsimrs`** | Koneksi baca/tulis ke database SIMRS Khanza (pegawai, inventaris referensi, departemen, dll.) |

Aplikasi memakai **role-based access control**. Setiap modul bisa dibuka per user lewat flag akses di profil user (hanya admin yang mengatur).

### Role User

| Role | Keterangan |
|------|------------|
| `admin` | Akses penuh; kelola user, semua modul sensitif |
| `staff` | Teknisi / petugas unit; tiket, laporan, modul sesuai flag |
| `pemohon` | User biasa; buat & lacak tiket sendiri |

Login dilayani di **halaman utama** (`/`), bukan `/login`. URL `/login` otomatis diarahkan ke `/`.

---

## Fitur

### Dasar (semua user login)

- **Dashboard** — ringkasan tiket, statistik, notifikasi
- **Tiket / Helpdesk** — buat, lacak, komentar, lampiran, draf, papan kanban
- **Catatan Kerja** — catatan harian pribadi
- **Chat** — pesan antar user
- **Daftar Pegawai** — lookup data pegawai dari SIMRS
- **Profil & Pengaturan** — akun, 2FA, preferensi

### Ticketing (staff & admin)

- Assign / transfer departemen, tutup & konfirmasi tiket
- Biaya vendor, sparepart, rekan kolaborator
- Rekomendasi AI & generate dokumen penyelesaian
- Import / export CSV, laporan SLA, departemen, teknisi, aktivitas harian
- Notifikasi Telegram (opsional)
- **Laporan Darurat (Panic Button)** — laporan & respons darurat

### Manajemen User (admin saja)

- Daftar user, buat user baru, edit role & flag akses modul
- Sinkron user dari data pegawai SIMRS
- User online / presence

### Aset & Inventaris

- **Aset Portal** — CRUD aset, foto, dokumen, label QR, audit fisik, peminjaman, mutasi lokasi, sinkron dari SIMRS
- **Master Aset** — kategori, jenis, merk, distributor, ruang, katalog ASPAK & non-alkes, import CSV
- **Referensi Inventaris SIMRS** — baca data inventaris Khanza (admin/staff; disembunyikan dari sidebar default)
- Scan QR publik aset (`/q/{aset}`) tanpa login

### Monitoring & Infrastruktur IT

- **RS Agent** — monitoring PC/perangkat (heartbeat, metrik, remote command)
- Kategori monitor, kesehatan infrastruktur (Tianji)
- Hubungkan perangkat monitor ke inventaris/aset

### Kepegawaian & Payroll

- **Payroll** — import slip gaji, riwayat pegawai, cetak, audit log (akses terbatas)
- **Berkas Kepegawaian** — arsip dokumen pegawai, inbox scan, master jenis berkas

### Operasional

- **Patroli Security** — check-in titik patroli, template checklist, area & ruang, laporan (menu muncul jika user punya flag `can_access_patroli`; admin yang mengatur lewat Daftar User)
- **Driver / Checklist Kendaraan** — pemeriksaan kendaraan, master kendaraan & item checklist

### Mutu, Naskah & Website RS

- **SIMMUTU** — indikator mutu, realisasi, rekap per unit kerja
- **Tata Naskah** — alur dokumen dinas (buat, review, TTE)
- **Web Official** — kelola berita, kamar inap, promosi, poliklinik, rekanan, Instagram feed, RSS eksternal

### Integrasi

- **SSO SIKAT** — single sign-on ke/dari sistem surat menyurat legacy
- **API publik** — jadwal dokter, informasi RS, kritik & saran (untuk website/mobile)
- **Firebase** — push notification (mobile)
- **Sanctum** — token API untuk aplikasi eksternal (kepegawaian, agent)

---

## Persyaratan

- **PHP** ≥ 8.2 (disarankan 8.4)
- **Composer** 2.x
- **Node.js** ≥ 20 & **npm**
- **MySQL** / MariaDB (production)
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`

Untuk fitur lengkap (pegawai, inventaris referensi), siapkan koneksi ke database **SIMRS Khanza** (`dbsimrs`).

---

## Instalasi

### 1. Clone & dependency

```bash
git clone <url-repo> PortalSifast
cd PortalSifast

composer install
npm install
```

Atau jalankan skrip setup bawaan:

```bash
composer run setup
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` — minimal setelan berikut:

```env
APP_NAME="Portal Sifast"
APP_URL=http://portalsifast.test
APP_TIMEZONE=Asia/Jakarta

# Database portal (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portalsifast
DB_USERNAME=root
DB_PASSWORD=

# Database SIMRS Khanza (opsional, untuk pegawai/inventaris referensi)
DB_HOST_2=127.0.0.1
DB_PORT_2=3306
DB_DATABASE_2=sik
DB_USERNAME_2=root
DB_PASSWORD_2=
```

### 3. Migrasi database

```bash
php artisan migrate
```

### 4. Build frontend

```bash
npm run build
```

Untuk development aktif, gunakan `npm run dev` (lihat [Menjalankan Aplikasi](#menjalankan-aplikasi)).

### 5. Web server (Nginx / FlyEnv / Laragon)

Document root harus mengarah ke folder **`public/`**.

Untuk **Nginx**, pastikan rewrite Laravel aktif (jika tidak, `/dashboard` dll. akan 404):

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$query_string;
}
```

Contoh virtual host FlyEnv: root `C:/Project/php/PortalSifast/public`, include `enable-php-84.conf`, dan file rewrite berisi rule di atas.

### 6. Queue & scheduler (production)

```bash
php artisan queue:work
# atau supervisor/systemd untuk php artisan queue:listen

# Cron (setiap menit):
# * * * * * cd /path/to/PortalSifast && php artisan schedule:run >> /dev/null 2>&1
```

---

## Membuat User Pertama (Admin)

Registrasi publik (`/register`) **dinonaktifkan di production** demi keamanan. Akun staf dibuat oleh admin atau lewat command/seeder.

### Cara 1 — Artisan command (disarankan)

```bash
php artisan user:create-admin \
  --name="Administrator" \
  --email="admin@portalsifast.com" \
  --password="GantiPasswordKuat123!"
```

Command ini:
- Membuat user baru dengan role **admin** jika email belum ada
- **Mempromosikan** user existing menjadi admin jika email sudah terdaftar
- Menandai email sebagai terverifikasi

Setelah itu login di **`/`** (halaman utama) dengan email & password di atas.

### Cara 2 — Database seeder (lokal/testing)

```bash
php artisan db:seed --class=AdminUserSeeder
```

Default seeder:

| Field | Nilai |
|-------|-------|
| Email | `admin@example.com` |
| Password | `password` |
| Role | `admin` |

**Ganti password segera setelah login pertama.**

### Cara 3 — Admin buat user lewat portal

Setelah ada admin pertama:

1. Login sebagai admin
2. Buka **Daftar User** (`/users`)
3. **Tambah User** — isi data, pilih role (`admin` / `staff` / `pemohon`), atur flag modul

User juga bisa disinkron dari data pegawai SIMRS lewat form create user.

### Registrasi publik (hanya development)

Di `.env` lokal, set:

```env
ALLOW_PUBLIC_REGISTRATION=true
```

Lalu `php artisan config:clear`. User baru default role **`pemohon`**.

> **Production:** jangan aktifkan registrasi publik. Buat akun staf hanya lewat admin atau SSO SIKAT.

---

## Menjalankan Aplikasi

### Development (semua service sekaligus)

```bash
composer run dev
```

Menjalankan: PHP server, queue worker, log tail (Pail), dan Vite HMR.

### Manual

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 (opsional)
php artisan queue:listen
```

Buka `APP_URL` dari `.env` (contoh: `http://portalsifast.test` atau `http://127.0.0.1:8000`).

---

## Konfigurasi Penting

| Variabel `.env` | Keterangan |
|-----------------|------------|
| `ALLOW_PUBLIC_REGISTRATION` | `true` = buka `/register` (default off di production) |
| `AUTH_SUPERADMIN_EMAILS` | Email superadmin (lokal di `.env` saja — **jangan commit**). Dipakai untuk flag sensitif seperti payroll; Patroli diatur oleh role admin. |
| `PORTALSIFAST_SIKAT_SSO_SECRET` | Secret SSO integrasi SIKAT |
| `TELEGRAM_BOT_TOKEN` | Notifikasi tiket via Telegram |
| `AGENT_ENROLLMENT_KEY` | Key pendaftaran RS Agent monitoring |
| `SIMRS_INVENTARIS_ASSET_BASE_URL` | Base URL foto inventaris SIMRS |

Lihat `.env.example` untuk daftar lengkap.

---

## Pengembangan & Testing

```bash
# Format kode PHP
vendor/bin/pint --dirty

# Jalankan test
php artisan test

# Test file tertentu
php artisan test tests/Feature/Aset/AsetDistributorMasterPageTest.php

# Lint frontend
npm run lint
npm run types
```

### Sub-proyek terkait

| Folder | Keterangan |
|--------|------------|
| [`rs-agent/`](rs-agent/README.md) | Agen monitoring PC (RS Agent) |
| [`berkas-agent/`](berkas-agent/README.md) | Agen scan berkas kepegawaian |

---

## Keamanan (ringkas)

- Modul **Daftar User** hanya untuk role **admin**
- Field `role` dan flag akses modul tidak bisa diubah user biasa
- Referensi inventaris SIMRS: akses **admin/staff** saja
- Production: matikan registrasi publik, bootstrap admin lewat `user:create-admin`

---

## Lisensi

MIT — lihat file lisensi proyek.
