# Bagian 2: Form Login Selector Engine & Fallback Architecture

Tantangan utama pada website-website target (Kemenkes & BKKBN) adalah variasi struktur HTML: ada yang berbasis SPA (React/Vue seperti SIRS Online & SIGA #/login), ada yang tanpa atribut id/name (SIRS Online), dan ada yang menggunakan nama atribut berbeda (uname, c, user, dsb.).

Untuk mengatasi ini secara tangguh tanpa hardcode, kita merancang Selector Engine Berjenjang (Fallback Cascade) yang dikonfigurasi melalui kolom form_config (JSON) di Master Portal:

## 1. Struktur JSON form_config di Master Portal

Setiap portal memiliki konfigurasi fleksibel seperti contoh berikut:

```json
{
  "is_spa": true,
  "wait_timeout_ms": 10000,
  "username_field": {
    "selectors": [
      "#email",
      "#c",
      "input[name='email']",
      "input[name='username']",
      "input[name='uname']",
      "input[name='user']",
      "input[type='email']",
      "input[placeholder*='email' i]",
      "input[placeholder*='pengguna' i]"
    ]
  },
  "password_field": {
    "selectors": [
      "#password",
      "#pass",
      "input[name='password']",
      "input[name='pwd']",
      "input[type='password']"
    ]
  },
  "extra_fields": [
    {
      "key": "kode_satker",
      "selectors": ["#satker", "input[name='username']"]
    }
  ],
  "auto_submit": false
}
```

## 2. Mekanisme Resolusi Selector pada Ekstensi

Saat halaman web target dimuat, content script ekstensi menjalankan logika:

1. SPA Element Watcher (MutationObserver):
    * Web seperti SIRS Online dan SIGA merender form login secara asinkron via JavaScript (React/Vue).
    * Ekstensi menggunakan MutationObserver yang memantau kemunculan elemen form hingga batas waktu (wait_timeout_ms, default 10 detik).
2. Pencarian Berjenjang (Cascade Resolution):
    * Ekstensi mencoba selector pertama. Jika elemen belum ditemukan atau tersembunyi (display: none), ekstensi otomatis mencoba selector berikutnya dalam daftar.
    * Kasus SIRS Online (tanpa id & tanpa name): Engine akan berhasil mencocokkan elemen berdasarkan atribut fungsional: input[type='email'] untuk email dan input[type='password'] untuk password.
    * Mendukung sintaks XPath jika sewaktu-waktu ada form yang hanya bisa diidentifikasi dari teks label di sebelahnya (misal: //label[contains(text(),'Email')]/following::input[1]).
3. Pemicu React/Vue Synthetic Event Dispatcher:
    * Framework modern seperti React/Vue sering kali tidak mendeteksi perubahan jika kita hanya mengisi input.value = "xxx".
    * Ekstensi akan memanggil native prototype setter dan menembakkan event input dan change bawaan browser secara berurutan, sehingga state internal form di website target langsung terisi dan tombol "Masuk" menjadi aktif.
4. Perlakuan CAPTCHA & Submit:
    * Sesuai instruksi Anda, auto_submit default bernilai false.
    * Setelah username dan password terisi, kursor fokus otomatis diarahkan ke input CAPTCHA (jika ada), dan ekstensi menampilkan indikator/toast kecil: "Kredensial Sifast terisi otomatis. Silakan selesaikan CAPTCHA dan klik Masuk."

## Mekanisme Scanning Dua Disi

**Mengeksplorasi Metode Pemindaian Halaman Masuk**

Menganalisis pertanyaan tersebut, "pemindaian halaman masuk (*scanning halaman login*)" kemungkinan besar merujuk pada metode untuk memeriksa halaman masuk. Dua pendekatan utama mengemuka: memanfaatkan ekstensi peramban dengan alat inspektor (*inspector tool*), atau memanfaatkan fungsionalitas pemindaian halaman dalam mode pengembangan/admin (*development/admin mode*). Pendekatan-pendekatan ini tampak paling menjanjikan.

**Mendesain Deteksi Otomatis Halaman Masuk**

Tujuannya adalah mengotomatiskan identifikasi formulir masuk, menghindari inspeksi manual. Pendekatan berbasis ekstensi memungkinkan admin untuk memicu pemindaian, yang menganalisis DOM halaman untuk mengidentifikasi kolom input dan kemungkinan perannya (nama pengguna/kata sandi). Hal ini harus menghasilkan selektor dan, paling tidak, sebuah konfigurasi untuk portal tersebut.

Pemindai heuristik cadangan (*fallback heuristic scanner*) di dalam skrip konten (*content script*) diperlukan untuk menangani situs web dinamis atau konfigurasi admin yang belum lengkap. Pemindai ini harus mengidentifikasi kolom nama pengguna dan kata sandi berdasarkan tipe serta kedekatannya di dalam formulir, memberikan pemilihan otomatis yang memadai.

Kita dapat menerapkan mekanisme scanning pada dua sisi:
  
### 1. Runtime Heuristic Scanner (Smart Auto-Detection saat Autofill Berjalan)

Alih-alih hanya mengandalkan selector statis yang rentan rusak jika web eksternal mengupdate tampilan, ekstensi memiliki algoritma pemindaian cerdas (heuristic scan) secara otomatis:

* Deteksi Bidang Password:
Ekstensi memindai seluruh halaman untuk mencari elemen input[type="password"] yang sedang terlihat (visible). Hampir 99.9% website login di dunia pasti menggunakan type="password".
* Deteksi Bidang Username / Email (Proximity & Semantic Scan):
Setelah bidang password ditemukan, pemindai mencari input sebelum password tersebut dengan aturan probabilitas:
    1. Input yang berada dalam <form> yang sama, tepat 1 tingkat di atas input password.
    2. Input dengan tipe type="email" atau type="text".
    3. Input yang memiliki atribut autocomplete="username", kata kunci pada name/id/placeholder (seperti email, user, pengguna, login, id, c).
    4. Input yang memiliki <label> terdekat bertuliskan "Email", "Username", "ID Pengguna", dll.


> Keuntungan: Bahkan jika website target (seperti SIRS Online) tidak memiliki atribut id maupun name, atau jika mereka tiba-tiba mendesain ulang websitenya, Runtime Scanner tetap bisa mengenali dan mengisi form secara otomatis tanpa perlu update config!

### 2. Admin Form Inspector / Scanner (Alat Bantu di Ekstensi untuk Admin)

Agar Admin/IT rumah sakit tidak perlu repot membuka Inspect Element (DevTools) secara manual saat mendaftarkan website baru di Master Portal:

* Cara Kerja:
    1. Admin membuka halaman login website eksternal target di browser.
    2. Admin mengklik ikon Ekstensi Sifast di pojok browser, lalu menekan tombol "Scan Halaman Login Ini".
    3. Ekstensi memindai DOM halaman tersebut, mendeteksi elemen input yang ada, dan otomatis menyusun rekomendasi selector (CSS & XPath).
    4. Ekstensi menampilkan hasil:
        * Bidang Username terdeteksi: input[type="email"]
        * Bidang Password terdeteksi: input[type="password"]
    5. Tersedia tombol "Salin Konfigurasi JSON" atau langsung kirim ke tab Master Portal yang sedang terbuka.


## Kesimpulan Pembaruan Desain Selector:

Dengan menggabungkan Rule-based Config + Heuristic Scanner:

1. Ekstensi mencoba mencocokkan konfigurasi spesifik di Master Portal terlebih dahulu.
2. Jika konfigurasi kosong atau selector tidak ditemukan di halaman, Heuristic Scanner otomatis mengambil alih untuk memindai struktur form di layar.