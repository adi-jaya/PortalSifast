# Bagian 1: Model Data & Skema Database

Untuk mendukung manajemen portal eksternal yang fleksibel (mendukung akun bersama RS maupun akun personal masing-masing petugas), kita merancang skema database berikut:

## 1. Tabel portals (Master Portal Eksternal)

Menyimpan profil website eksternal, konfigurasi form login, dan akun bersama (shared) tingkat rumah sakit jika ada:

* id (bigint, PK)
* name (varchar): Nama portal, misal "SIRS Online", "SITB", "MPDN".
* slug (varchar, unique): Identifier ramah URL.
* category (varchar): Kategori portal, misal "Kemenkes", "BKKBN", "Mutu & Akreditasi".
* url (varchar): URL tujuan login eksternal.
* icon_path (varchar, nullable): Logo/ikon portal untuk tampilan kartu di dashboard.
* description (text, nullable): Keterangan singkat fungsi portal.
* auth_type (enum: 'shared', 'personal', 'both'):
    * 'shared': Portal hanya menggunakan 1 akun instansi RS untuk semua petugas yang berhak.
    * 'personal': Setiap petugas wajib menggunakan kredensial akun pribadinya sendiri.
    * 'both': Fleksibel; bisa default akun RS, namun petugas juga dapat memakai akun personalnya.
* shared_username (varchar, nullable): Username akun bersama RS.
* shared_password (text, nullable): Password akun bersama RS (Terenkripsi menggunakan Crypt::encryptString Laravel).
* shared_extra_fields (json, nullable): Data field tambahan (misalnya Kode Fasyankes / Kode Satker).
* form_config (json): Konfigurasi selector form login (akan dibahas mendalam di Bagian 2).
* is_active (boolean, default true): Status aktif/nonaktif portal di SIMRS.
* sort_order (int, default 0): Urutan tampilan kartu portal di UI.
* timestamps

## 2. Tabel user_portal_credentials (Mapping Akses & Kredensial Petugas)

Menghubungkan user SIMRS dengan portal yang diizinkan dan menyimpan kredensial personal jika berlaku:

* id (bigint, PK)
* user_id (bigint, FK ke users.id, on delete cascade)
* portal_id (bigint, FK ke portals.id, on delete cascade)
* credential_type (enum: 'use_shared', 'personal'):
    * 'use_shared': Petugas menggunakan akun RS yang ada di tabel portals (petugas tidak perlu tahu password aslinya).
    * 'personal': Petugas menggunakan username & password miliknya sendiri untuk website tersebut.
* personal_username (varchar, nullable): Username pribadi petugas untuk portal tersebut.
* personal_password (text, nullable): Password pribadi petugas (Terenkripsi via Crypt::encryptString).
* personal_extra_fields (json, nullable): Nilai input tambahan khusus user tersebut (jika ada).
* is_active (boolean, default true): Apakah akses user ini ke portal tersebut sedang aktif.
* unique_index: (user_id, portal_id) (mencegah duplikasi mapping).
* timestamps

## 3. Hak Akses & Permission Pengguna

* Menambahkan kolom izin baru di tabel users:
    * can_manage_portal (boolean, default false)
* Pengguna dengan role = 'superadmin', role = 'admin', atau can_manage_portal = true berhak mengakses menu Master Portal dan mengatur mapping kredensial petugas lain.

**Menilai Implementasi Peran Pengguna**

Memperhatikan bahwa model `User` menerapkan metode-metode yang jelas (`isAdmin()`, `isSuperAdmin()`) untuk pemeriksaan peran berdasarkan atribut `role`. Menyimpulkan bahwa menggabungkan metode-metode yang sudah ada ini, seperti `$user->isAdmin() || $user->isSuperAdmin()`, menyediakan cara yang rapi untuk menangani berbagai tingkatan akses di dalam kode aplikasi. Pendekatan ini menghindari modifikasi pada tabel `users`, memastikan migrasi tetap terisolasi di dalam tabel-tabel portal yang baru.

**Mendesain Mesin Selektor Formulir yang Tangguh**

Menganalisis beragam struktur formulir masuk (*login form*) dengan atribut yang bervariasi (ID, name) serta perilaku SPA (*Single Page Application*). Menentukan bahwa mesin selektor yang fleksibel sangat krusial untuk menangani variasi-variasi ini, termasuk formulir tanpa atribut umum seperti ID atau name. Mendefinisikan sebuah struktur JSON di dalam `portals.form_config` untuk menampung selektor-selektor dan karakteristik formulir tersebut.

**Menerapkan Strategi Input Formulir**

Menentukan perlunya deteksi elemen yang tangguh, terutama pada aplikasi yang dinamis. Menerapkan pendekatan gabungan antara `MutationObserver` dan *polling* dengan batas waktu (*timeout*) untuk menunggu elemen muncul di dalam DOM, sebuah elemen kunci untuk SPA. Menerapkan strategi cadangan (*fallback*) untuk menangani kasus ketika selektor standar (ID, name) tidak ditemukan. Ini mencakup penggunaan XPath untuk pemilihan elemen relatif berdasarkan kedekatan label serta heuristik lainnya, sehingga meningkatkan kemampuan adaptasi.

**Keputusan**

Pengecekan hak akses untuk mengelola Master Portal dan Mapping Kredensial akan ditangani langsung di level kode (Policy / Gate / Middleware):

* Pengguna dengan role === 'admin' atau isSuperAdmin() otomatis memiliki hak kelola penuh.
* Hal ini menjaga tabel users tetap bersih dan migrasi database sepenuhnya terisolasi pada modul portal baru (portals dan user_portal_credentials).