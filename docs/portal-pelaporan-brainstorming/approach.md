# Approach

Berdasarkan klarifikasi sebelumnya, permintaan ini tergolong Architectural karena mencakup pembangunan subsistem baru dari hulu ke hilir:

1. Backend & Database: Master Portal, konfigurasi selector form dinamis (JSON), manajemen kredensial terenkripsi (Akun Institusi & Akun Pribadi), mapping hak akses user-portal, dan endpoint secure
dispatch.
2. Frontend Portal Sifast: Halaman agregator Portal Pelaporan, deteksi status ekstensi browser, UI Master Portal (CRUD), dan UI Mapping Akses/Kredensial.
3. Custom Browser Extension (Manifest V3): Content script bridge SIMRS, background service worker penampung antrean kredensial sementara (in-memory), content script injektor web target (resilient
selector engine untuk CSS/XPath/SPA), serta packaging lokal (.zip) dan kesiapan Chrome Web Store.

### Perbandingan Pendekatan Arsitektur

Berikut adalah 3 opsi pendekatan integrasi antara SIMRS dan Browser Extension:

#### 1. Pendekatan 1: Secure One-Time Event Dispatch via Content Script Bridge (Rekomendasi)

* Cara Kerja:
    1. Pengguna mengklik tombol "Buka Portal" di Portal Sifast.
    2. Frontend SIMRS mengirim request ke backend (POST /api/portal/{id}/dispatch). Backend memvalidasi hak akses pengguna, mendekripsi password sementara, dan mengembalikan payload token/kredensial
    satu kali pakai (one-time transfer) dengan masa berlaku 30 detik.
    3. Halaman SIMRS memicu Custom DOM Event (window.dispatchEvent) yang ditangkap oleh content script ekstensi di domain SIMRS.
    4. Content script meneruskan payload ke Extension Service Worker (background). Service worker membuka tab website eksternal target (chrome.tabs.create) dan mengaitkan tabId baru dengan kredensial
    & aturan selector di memori RAM sementara.
    5. Saat website eksternal terbuka, content script di website tersebut meminta data ke service worker berdasarkan tabId-nya, mengeksekusi pengisian form (autofill), lalu service worker langsung
    menghapus kredensial dari memori (flush immediately).
* Kelebihan:
    * Paling Aman: Kredensial tidak pernah disimpan permanen di browser ekstensi (chrome.storage), tidak ada risiko bocor ke web target atau riwayat browser.
    * Sangat selaras dengan preferensi: ekstensi hanya aktif saat dibuka melalui portal SIMRS.
    * Tidak membebani browser dengan token autentikasi persisten di ekstensi.
* Kekurangan:
    * Membutuhkan perizinan host permissions ekstensi untuk domain SIMRS dan domain-domain website Kemenkes/BKKBN target.

#### 2. Pendekatan 2: Sesi Terhubung Terpusat (API Polling & Storage Sync)

* Cara Kerja:
    * Ekstensi menyimpan API Token SIMRS pengguna.
    * Kapan pun pengguna membuka website eksternal (baik via portal maupun ketik manual/bookmark), ekstensi mengenali URL domain target, menghubungi API SIMRS untuk meminta kredensial, lalu melakukan
    autofill.
* Kelebihan:
    * Bisa berjalan meskipun pengguna membuka website target langsung dari bookmark browser.
* Kekurangan:
    * Ekstensi harus mengelola login/token pengguna SIMRS sendiri. Pada komputer rumah sakit yang dipakai bergantian oleh banyak petugas (shared workstation), sangat rawan salah akun jika sesi
    ekstensi tidak sinkron dengan sesi SIMRS yang sedang aktif.

#### 3. Pendekatan 3: Relay Parameter URL Terenkripsi

* Cara Kerja:
    * SIMRS membuka tab baru dengan menyisipkan parameter/hash unik terenkripsi pada URL target (misal: https://target-url/#sifast_ticket=xyz). Ekstensi di web target membaca hash tersebut, menukarnya
    ke SIMRS, lalu menghapus hash dari URL bar.
* Kelebihan:
    * Tidak memerlukan komunikasi antar-tab atau jembatan content script di domain SIMRS.
* Kekurangan:
    * Rentan Bentrok: Banyak aplikasi Kemenkes berbasis SPA menggunakan hash router bawaan (seperti SIGA dengan #/login), sehingga manipulasi hash bisa merusak routing aplikasi target.

### Rekomendasi

Kami sangat merekomendasikan Pendekatan 1 (Secure One-Time Event Dispatch via Bridge) karena memberikan tingkat keamanan standar perbankan/medis (kredensial hanya hidup beberapa detik di memori saat
tab dibuka), tidak menimbulkan bentrok URL pada aplikasi target, dan mencegah pertukaran akun yang tertinggal di komputer bersama (shared PC rumah sakit).

### Penjelasan mendalam mengenai maksud dari poin tersebut beserta konsekuensi dan solusinya:

**Menginterpretasikan Makna `host_permissions`**

`host_permissions` dalam Manifes V3 Ekstensi Chrome memberikan izin akses bagi ekstensi untuk berinteraksi dengan konten situs web tertentu. Akses ini memungkinkan pembacaan atau pengubahan konten halaman web, penyuntikan skrip (*script injection*), dan pencegatan permintaan jaringan (*intercepting network requests*). Pernyataan tersebut menunjukkan bahwa ekstensi memerlukan izin ini untuk domain SIMRS serta situs web Kemenkes/BKKBN lainnya.

**Menganalisis Implikasi `host_permissions`**

Ekstensi Chrome harus mendeklarasikan domain-domain spesifik yang ingin diajak berinteraksi di dalam berkas manifesnya. Deklarasi ini memungkinkan injeksi skrip, manipulasi DOM, dan pemantauan *event* (*event listening*) di dalam halaman web tersebut. Mendaftar domain secara eksplisit memastikan akses yang terkontrol, sementara izin karakter pengganti (*wildcard*) menawarkan fungsionalitas yang lebih luas tetapi kurang aman.

Kelemahan utamanya adalah daftar domain yang statis memerlukan pembaruan ekstensi untuk mendukung situs web baru. Tanpa pembaruan, ekstensi tidak dapat secara otomatis berinteraksi dengan situs web eksternal yang ditambahkan secara dinamis. Hal ini mendorong pertimbangan antara frekuensi pembaruan dan izin yang lebih luas, dengan menimbang keamanan terhadap kemudahan.

#### 1. Apa Itu Host Permissions di Chrome Extension (Manifest V3)?

Agar sebuah ekstensi browser dapat berinteraksi dengan halaman web (seperti membaca DOM, mencari elemen input form, dan mengisi username/password secara otomatis), Google Chrome menerapkan sistem
keamanan ketat.

Ekstensi tidak diizinkan menyentuh atau menjalankan script di sembarang website, kecuali domain website tersebut telah didaftarkan secara eksplisit di file konfigurasi ekstensi (manifest.json) pada
bagian host_permissions.

Contoh deklarasi di manifest.json:

```json
{
    "name": "Sifast Portal Autofill",
    "version": "1.0.0",
    "manifest_version": 3,
    "host_permissions": [
    "https://*.rsasf.co.id/*",                // Domain SIMRS Sifast (untuk jembatan sinyal)
    "https://siga-sirika.bkkbn.go.id/*",       // Web eksternal BKKBN
    "https://*.kemkes.go.id/*",                // Wildcard semua sub-domain Kemenkes
    "https://*.sitb.id/*"                      // Domain SITB
    ]
}
```

#### 2. Mengapa Hal Ini Menjadi "Kekurangan" atau Pertimbangan?

Ada 3 konsekuensi praktis yang perlu diperhatikan:

##### A. Penambahan Website Baru di Masa Depan Bersifat "Statis"

* Jika suatu saat rumah sakit Anda menambahkan website eksternal baru di menu Master Portal SIMRS (misalnya website BPJS Kesehatan https://dvlp.bpjs-kesehatan.go.id/* atau website Dinkes Kabupaten),
ekstensi tidak bisa langsung meng-autofill jika domain tersebut belum pernah didaftarkan di dalam manifest.json ekstensi.
* Akibatnya: Setiap ada platform baru dengan domain induk yang sama sekali berbeda, file ekstensi perlu di-update versinya (menambahkan domain baru ke manifest.json) dan dibagikan ulang ke pengguna.

##### B. Notifikasi Peringatan Keamanan saat Pemasangan (Install Warning)

* Saat petugas menginstall ekstensi (baik via Chrome Web Store maupun instalasi internal), Chrome akan memunculkan pop-up peringatan:
> "Ekstensi ini dapat membaca dan mengubah data Anda di situs web yang tercantum..."

* Di lingkungan rumah sakit, beberapa pengguna awam mungkin merasa ragu jika tidak diberi tahu sebelumnya bahwa izin ini wajar dan memang dibutuhkan agar ekstensi bisa mengisi form login.

##### C. Proses Verifikasi di Chrome Web Store (Jika Rilis Publik)

* Google menerapkan kebijakan Minimal Permissions. Jika Anda mengajukan ekstensi ke Chrome Web Store resmi, tim verifikasi Google akan meminta Justifikasi Izin (Permission Justification).
* Namun hal ini sangat mudah diselesaikan dengan mengisi penjelasan singkat saat submit: "Ekstensi internal rumah sakit untuk membantu staf mengisi kredensial pada portal pelaporan resmi pemerintah (Kemenkes & BKKBN)".

#### 3. Solusi dan Strategi Terbaik Menghadapinya

Untuk mengatasi kekurangan tersebut, ada dua strategi yang bisa kita terapkan:

1. Gunakan Wildcard Domain Induk Pemerintah:
Alih-alih mendaftarkan URL satu per satu secara kaku, kita gunakan wildcard:
    * https://*.kemkes.go.id/* (otomatis mencakup MPDN, SIHA, SIGIZI, SatuSehat, MutuFasyankes, Akun Yankes, dan layanan Kemenkes lainnya di masa depan)
    * https://*.bkkbn.go.id/* dan https://*.kemendukbangga.go.id/*
    * https://*.sitb.id/*
    Dengan cara ini, jika ada portal Kemenkes baru, Anda cukup menambahkannya di Master Portal SIMRS tanpa perlu mengubah/mengupdate file ekstensi!
2. Dukungan optional_host_permissions / Runtime Permission (Opsi Tambahan):
Jika di kemudian hari ada domain baru di luar dugaan, ekstensi bisa meminta izin tambahan secara langsung (on-the-fly) saat pengguna pertama kali mengklik portal tersebut, tanpa harus menghapus dan menginstal ulang ekstensi.

Dengan solusi wildcard di atas, kendala perizinan domain ini menjadi sangat minim dan terkontrol.

**Mengonfirmasi Langkah Desain Berikutnya**

Pengguna telah memilih "Pendekatan 1," sehingga fokus kini beralih ke penyajian desain. Langkah selanjutnya melibatkan pembagian desain ke dalam beberapa bagian berdasarkan kompleksitasnya. Hal ini akan memudahkan pemahaman dan pengelolaan selama proses presentasi.

**Mendefinisikan Tabel Data Awal**

Desain basis data dimulai dengan tabel `portals`, yang membutuhkan `id`, `name`, `slug`, `category`, dan `url` untuk mengategorikan portal pelaporan. Kolom `slug` harus unik untuk memastikan perutean (*routing*) yang tepat, dan `category` memungkinkan pengelompokan portal. Penyelidikan lebih lanjut diperlukan untuk menentukan atribut tambahan, seperti cara mengelola kredensial portal dan pemetaan pengguna, tetapi ini adalah langkah awal yang baik.