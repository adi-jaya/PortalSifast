# 🛠️ Modul 12: Panduan Setup Lokal, Deployment, dan Troubleshooting

Dokumen ini adalah panduan praktis langkah demi langkah untuk menyiapkan lingkungan kerja pengembangan (*local development environment*) di komputer Anda hingga aplikasi siap dijalankan.

---

## 1. Prasyarat Sistem (*Prerequisites*)

Pastikan komputer kerja Anda telah terinstal:
* **PHP:** Versi `>= 8.2` (dengan ekstensi: `pdo_mysql`, `pdo_sqlite`, `bcmath`, `curl`, `gd`, `mbstring`, `openssl`, `zip`, `pcntl`).
* **Composer:** Versi `>= 2.6.x`.
* **Node.js:** Versi `>= 20.x LTS` & **npm** `>= 10.x`.
* **Database:** MySQL `8.0+` atau MariaDB `10.6+` (dapat juga menggunakan SQLite untuk unit testing).
* **Git:** Versi terbaru.

---

## 2. Langkah-demi-Langkah Instalasi Lokal

### Langkah 1: Kloning Repositori & Masuk Direktori
```bash
git clone <repository_url> PortalSifast
cd PortalSifast
```

### Langkah 2: Siapkan Konfigurasi Environment (`.env`)
Salin berkas contoh konfigurasi:
```bash
cp .env.example .env
```
Buka berkas `.env` dan sesuaikan pengaturan koneksi database utama:
```env
APP_NAME="Portal Sifast"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portal_sifast
DB_USERNAME=root
DB_PASSWORD=your_password

# Database SIMRS Khanza (Opsional di lokal / isi jika terhubung VPN RS)
DB_HOST_2=127.0.0.1
DB_PORT_2=3306
DB_DATABASE_2=db_khanza_simrs
DB_USERNAME_2=root
DB_PASSWORD_2=

# Laravel Reverb WebSockets
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=portal-sifast
REVERB_APP_KEY=portal-sifast-key
REVERB_APP_SECRET=portal-sifast-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Langkah 3: Install Dependensi PHP & Frontend
```bash
composer install
npm ci
```

> [!TIP]
> **Mengapa `npm ci` dan bukan `npm install`?**
> * `npm ci` (*Clean Install*) menginstal dependensi secara **100% deterministik** berdasarkan file [`package-lock.json`](../../package-lock.json) tanpa mengubah versi package, mencegah terjadinya *version drift* antar komputer developer dan server produksi.
> * Gunakan `npm ci` saat **setup awal / onboarding** dan pada **pipeline CI/CD & deployment server**.
> * Gunakan `npm install <nama-package>` hanya saat Anda secara eksplisit ingin menambahkan atau memperbarui library baru.

### Langkah 4: Generate Application Key & Storage Link
```bash
php artisan key:generate
php artisan storage:link
```

### Langkah 5: Jalankan Migrasi Database
```bash
php artisan migrate
```

---

## 3. Menjalankan Aplikasi di Lingkungan Lokal

Portal Sifast telah dilengkapi dengan skrip **Concurrently Runner** terpadu di `composer.json` yang akan menjalankan web server, queue listener, log tailer (Pail), dan Vite server dalam satu jendela terminal:

```bash
composer run dev
```

Perintah di atas secara otomatis menjalankan 4 proses paralel:
1. `php artisan serve` — HTTP Web Server di `http://localhost:8000`.
2. `php artisan queue:listen --tries=1 --timeout=0` — Pemroses antrean background.
3. `php artisan pail --timeout=0` — Log viewer interaktif.
4. `npm run dev` — Vite dev server & Tailwind CSS hot reload.

> [!TIP]
> **Menjalankan Server WebSocket Reverb:**
> Jika Anda menguji fitur chat real-time atau user presence, buka tab terminal baru dan jalankan:
> ```bash
> php artisan reverb:start --debug
> ```

---

## 4. Katalog Artisan Command Operasional

Berikut adalah perintah CLI kustom yang sering digunakan dalam operasional maupun cron job:

| Command | Fungsi |
| :--- | :--- |
| `php artisan token:generate` | Membuat Sanctum Bearer Token untuk akun integrasi API kepegawaian. |
| `php artisan tickets:work-nudge` | Mengirim notifikasi Telegram pengingat tiket tertunda ke teknisi. |
| `php artisan tickets:daily-it-report` | Mengirim ringkasan harian tiket IT ke grup Telegram koordinator. |
| `php artisan tickets:auto-close` | Menutup otomatis tiket resolved yang tidak direspon pemohon > 3 hari. |
| `php artisan check:pending-panic` | Memeriksa dan eskalasi ulang alarm Panic Button yang belum direspon. |
| `php artisan aset:sinkron-dari-simrs` | Mengimpor data inventaris baru dari database SIMRS Khanza ke modul Aset. |
| `php artisan users:sync-from-simrs` | Sinkronisasi profil NIK dan nomor handphone pegawai dari SIMRS. |
| `php artisan web-official:sync-instagram` | Refresh cache postingan Instagram feed website resmi RS. |
| `php artisan web-official:sync-rss` | Mengambil berita terbaru dari feed RSS Muhammadiyah. |
| `php artisan devices:mark-offline` | Menandai PC monitoring offline jika heartbeat terhenti > 5 menit. |
| `php artisan devices:prune-metrics` | Membersihkan histori metrik hardware lama (> 7 hari). |

---

## 5. Troubleshooting & FAQ

### 1. Error Koneksi Database SIMRS Khanza (`dbsimrs`)
* **Penyebab:** Di komputer lokal, Anda mungkin tidak terhubung ke jaringan intranet rumah sakit atau database SIMRS Khanza.
* **Solusi:** Di file `.env`, arahkan `DB_DATABASE_2` ke database lokal dummy atau biarkan tabel terkait kosong jika hanya mengembangkan modul yang tidak bergantung langsung ke data transaksi pasien SIMRS.

### 2. Rute Frontend Wayfinder Mismatch / TypeScript Error
* **Penyebab:** Anda baru saja menambahkan rute baru di `routes/web.php` namun TypeScript frontend belum mendeteksi.
* **Solusi:** Restart proses `npm run dev` atau jalankan:
  ```bash
  npx vite build --watch
  ```

### 3. Masalah Izin Berkas (*Permission Denied*) pada Folder Storage
* **Solusi (Linux/macOS):**
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```

### 4. WebSocket Reverb Gagal Terhubung (*Connection Refused*)
* Pastikan `php artisan reverb:start` sedang berjalan.
* Pastikan port `8080` tidak sedang digunakan oleh aplikasi lain.
* Periksa nilai `VITE_REVERB_HOST` dan `VITE_REVERB_PORT` pada file `.env` sudah sama persis dengan konfigurasi `REVERB_*`.
