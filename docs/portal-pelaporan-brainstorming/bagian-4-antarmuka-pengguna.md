# Bagian 4: Antarmuka Pengguna (Frontend UI/UX)

Antarmuka dibangun menggunakan stack yang sudah ada di proyek ini (Inertia.js + React + Tailwind CSS + Lucide Icons + Radix UI). Terdapat 3 halaman utama:
  
## 1. Halaman Utama Petugas: Portal Pelaporan (/portal-pelaporan)

Halaman agregator utama tempat staf rumah sakit mengakses seluruh portal eksternal:

* Header & Banner Status Ekstensi:
    * Jika Ekstensi Aktif: Muncul badge hijau ● Ekstensi Aktif v1.0.0 di pojok kanan atas.
    * Jika Belum Terpasang: Muncul banner informatif dengan tombol [Unduh & Panduan Pasang Ekstensi].
    * Fitur pencarian instan dan filter kategori (Semua, Kemenkes, BKKBN, Mutu & Akreditasi, dll.).
* Grid Kartu Website Eksternal:
Setiap kartu menampilkan:
    * Logo / Ikon portal dan nama portal resmi.
    * Tag Kategori dan deskripsi singkat.
    * Badge Status Akses:
        * Akun RS Aktif: Petugas memiliki akses via akun instansi.
        * Akun Personal Aktif: Petugas memiliki akun tersendiri.
        * Buka Manual: Petugas belum memiliki mapping kredensial (tombol tetap bisa diklik untuk membuka website secara manual).
    * Tombol Aksi:
        * Tombol utama: "Buka Portal" (jika ekstensi aktif dan ada mapping kredensial, otomatis memicu dispatch autofill).
        * Tombol titik tiga / opsi: "Atur Akun Pribadi" (jika portal mengizinkan akun personal, petugas dapat mengisi/memperbarui username & password miliknya sendiri).

## 2. Halaman Admin: Master Portal (/admin/portals)

Halaman khusus pengelola IT/Admin untuk mengatur daftar website:

* Tabel Manajemen Portal:
    * Menampilkan daftar website eksternal, URL tujuan, kategori, tipe akun (Shared/Personal/Both), jumlah staf yang di-mapping, serta switch status aktif.
* Form Tambah / Edit Portal:
    * Informasi Dasar: Nama website, URL target, kategori, ikon, deskripsi, urutan (sort order).
    * Konfigurasi Akun: Pilihan tipe akun (Shared RS, Personal Pegawai, atau Keduanya). Jika memilih Shared/Keduanya, tersedia form isian Username & Password RS (disimpan terenkripsi).
    * Konfigurasi Selector Login:
        * Switch opsi: "Gunakan Smart Heuristic Scanner (Rekomendasi)" untuk deteksi otomatis tanpa repot.
        * Opsi kustomisasi selector (CSS/XPath) dan editor JSON jika website memiliki kebutuhan struktur input yang spesifik.

## 3. Halaman Admin: Mapping Akses Petugas (/admin/portals/mapping)

Memudahkan admin dalam menghubungkan banyak pengguna dengan banyak website:

* 2 Mode Tampilan Fleksibel:
    1. Tampilan per Portal: Admin memilih 1 portal (misal: SITB), lalu mencentang daftar petugas/departemen yang diizinkan mengaksesnya.
    2. Tampilan per Petugas: Admin memilih 1 nama petugas, lalu mencentang portal apa saja yang boleh diakses petugas tersebut.
* Pengaturan Kredensial:
    * Menentukan apakah petugas menggunakan akun bersama RS (use_shared) atau kredensial khusus (personal).

## 4. Modal Panduan Instalasi Ekstensi

Jika pengguna mengklik link unduh ekstensi, muncul modal panduan ringkas 3 langkah:

1. Klik tombol "Unduh Paket Ekstensi (.zip)".
2. Buka chrome://extensions di browser Chrome/Edge dan aktifkan toggle Developer mode.
3. Klik tombol Load unpacked, pilih folder yang telah diekstrak, dan selesai.