# Clarification

**Memulai Proses Curah Pendapat**

Proses curah pendapat perlu diklasifikasikan untuk memahami upaya yang diperlukan. Fokusnya adalah memahami konteks, menyempurnakan gagasan inti, lalu mengembangkan sebuah desain. Langkah krusialnya melibatkan upaya meminta persetujuan sebelum implementasi.

**Mengklasifikasikan Arsitektur Subsistem**

Ini adalah proyek arsitektural, mengingat cakupan dan dampaknya: sebuah subsistem baru dengan banyak komponen. Rencana ini mencakup penjelajahan konteks yang ada, mengajukan pertanyaan klarifikasi, mengusulkan beberapa pendekatan, mendesain secara bertahap (per bagian), menulis dokumen desain, mendapatkan persetujuan pengguna di setiap tahapan, dan akhirnya bertransisi ke tahap implementasi.

**Menilai Kompleksitas Permintaan**

Menentukan tingkat pemrosesan yang tepat untuk permintaan ini sangatlah penting. Proyek `PortalSifast` menggunakan Laravel 12 dengan Fortify, Sanctum, dan Inertia React, yang menyediakan kerangka kerja untuk memahami kemampuan serta batasan-batasannya. Sesuai instruksi, permintaan ini akan dianalisis untuk memahami konteks dan merumuskan sebuah desain sebelum implementasi.

Ini terlihat bersifat arsitektural, jadi saya akan mengikuti proses arsitektur.

**Memulai Analisis Arsitektur**

Menyadari bahwa cakupan tugas ini sejalan dengan perubahan arsitektural; oleh karena itu, saya akan mengikuti proses yang telah ditentukan. Langkah awal melibatkan penjelajahan kontekstual melalui berkas proyek dan dokumentasi yang disediakan. Berfokus pada pemahaman tujuan dan batasan-batasan, saya akan melanjutkan dengan pertanyaan klarifikasi.

**Merumuskan Pertanyaan Klarifikasi**

Menyadari perlunya mengklarifikasi arsitektur kredensial untuk fitur baru ini. Mempertimbangkan alur kredensial yang aman dari `PortalSifast` ke ekstensi peramban, merumuskan pertanyaan untuk mengumpulkan informasi tentang jenis akun (bersama vs. individu) yang digunakan untuk masuk ke situs Kemenkes sangat penting bagi desain arsitektur. Tujuannya adalah memilih cara terbaik untuk penyimpanan kredensial.

**Memprioritaskan Pertanyaan Krusial Seputar Kredensial**

Pertanyaan inti berfokus pada bagaimana situs web eksternal Kemenkes/BKKBN menangani autentikasi pengguna (akun instansi bersama, akun individu, atau hibrida). Hal ini sangat mendasar, karena menentukan penyimpanan kredensial dan manajemen akses di dalam sistem. Selanjutnya, akan dipertimbangkan aspek pemetaan akses.

**Mengonfirmasi Klasifikasi Arsitektur**

Meninjau kembali klasifikasi permintaan; mengonfirmasi bahwa hal ini termasuk dalam kategori "Arsitektural" karena sifat menyeluruh dari subsistem baru tersebut. Ini mencakup komponen *backend*, *frontend*, dan ekstensi peramban. Dikonfirmasi rencana untuk mengikuti proses desain yang lengkap sebelum menulis kode apa pun.

**Merumuskan Pertanyaan Awal**

Pertanyaan klarifikasi bertujuan untuk menentukan kepemilikan dan pengelolaan kredensial. Opsi yang disediakan mencakup model pengelolaan kredensial bersama instansi, individu, atau hibrida untuk memahami cara menyimpan kredensial dan bagaimana kredensial tersebut akan digunakan oleh ekstensi peramban. Pertanyaan ini dirancang untuk mengumpulkan kebutuhan terkait pemetaan dan manajemen akses kredensial yang diperlukan.

```
? Bagaimana skema kepemilikan kredensial (username & password) untuk website-website eksternal tersebut di SIMRS?
> (Recommended) Mendukung keduanya: Kredensial bisa berupa Akun Institusi/Shared RS (dikelola admin) atau Akun Pribadi Petugas (diinput oleh masing-masing user/admin untuk user tersebut)
```

**Mengklarifikasi Opsi Pengelolaan Kredensial**

Pilihan pengguna mengindikasikan dukungan bagi kredensial institusional/bersama yang dikelola oleh seorang admin, maupun kredensial pribadi yang dimasukkan oleh masing-masing pengguna atau admin untuk para pengguna tersebut. Pendekatan kredensial ganda ini memerlukan klarifikasi lebih lanjut mengenai bagaimana integrasi situs web eksternal akan menangani berbagai jenis kredensial dalam alur kerjanya, khususnya dalam konteks poin 3 dari alur tersebut, yang berfokus pada interaksi pengguna dengan situs web eksternal.

**Mengevaluasi Pendekatan Tampilan Portal**

Mempertimbangkan berbagai opsi tampilan untuk halaman "Portal Pelaporan" guna mengakomodasi pengguna dengan tingkat akses yang berbeda-beda. Tiga pendekatan utama awalnya sedang ditinjau, masing-masing dengan implikasi UX (pengalaman pengguna) dan implementasi: menampilkan semua portal (dinonaktifkan untuk akses terbatas), hanya menampilkan portal yang diizinkan, atau menampilkan semua dengan permintaan akses sesuai kebutuhan (*on-demand*). Kebutuhan akan keseimbangan antara kemudahan penemuan (*discoverability*) dan kekacauan antarmuka (*interface clutter*) adalah hal yang penting. Pengalaman pengguna dalam lingkungan rumah sakit memerlukan pertimbangan, di mana tampilan yang terfokus mungkin merupakan yang paling efektif.

```
? Untuk halaman utama 'Portal Pelaporan', bagaimana perilaku tampilan daftar website eksternal bagi user yang tidak memiliki akses ke portal tertentu?
> Tampilkan semua portal tanpa terkunci: Siapapun bisa membuka web target, namun autofill extension hanya akan berjalan jika user memiliki mapping kredensial yang valid
```

**Memeriksa Komunikasi Ekstensi Kustom**

Keputusan untuk mengizinkan akses terbuka ke target web namun membatasi pengisian otomatis (*autofill*) berdasarkan kredensial yang valid mengharuskan pemeriksaan yang cermat terhadap komunikasi ekstensi peramban kustom dengan SIMRS PortalSifast. Pilihan desain ini mengimplikasikan perlunya menganalisis bagaimana ekstensi mengautentikasi dan mengambil pemetaan pengguna. Alur berikutnya akan menentukan bagaimana ekstensi berinteraksi dengan target web.

**Mengevaluasi Pengambilan Kredensial yang Aman**

Dua mekanisme utama dipertimbangkan untuk pengambilan yang aman: *handshake* langsung yang diinisiasi dari SIMRS melalui skrip konten (*content script*) dan komunikasi *background worker*, yang meningkatkan keamanan dengan meminimalkan interaksi API langsung dengan situs eksternal, serta panggilan API langsung dari ekstensi. Metode pertama tampaknya lebih disukai karena hanya mengirimkan kredensial sesuai permintaan (*on-demand*) saat sebuah tautan di SIMRS diklik, dan mencegah situs target berinteraksi langsung dengan API. Berfokus pada mekanisme pertama untuk memastikan keamanan kredensial adalah hal yang terpenting.

**Menganalisis Interaksi API Ekstensi**

Ekstensi dapat menanyakan langsung ke *backend* menggunakan token sesi (*session token*) atau token API, untuk menangani variasi navigasi. Sebagai alternatif, pendekatan hibrida dapat memanfaatkan tiket sekali pakai (*one-time ticket*) dari SIMRS, dengan mekanisme cadangan (*fallback*) pada ekstensi menggunakan sesi atau token tersebut. Mempertimbangkan beragamnya struktur input formulir, terutama yang tanpa atribut `id` atau `name`, menekankan perlunya selektor CSS yang fleksibel di dalam Master Portal. Pertimbangan ini memastikan sistem dapat mengakomodasi struktur elemen formulir yang bervariasi dan berpotensi berubah, khususnya dalam kasus ketika struktur formulir situs eksternal tidak konsisten atau tidak terdefinisi dengan baik.

```
? Bagaimana preferensi Anda terkait pemicu (trigger) autofill oleh Browser Extension saat membuka website eksternal?
> (Recommended) On-Click dari Portal Sifast (Secure Dispatch): Ekstensi hanya mengisi saat user meng-klik portal dari SIMRS. SIMRS mengirimkan sinyal/kredensial one-time ke ekstensi untuk tab baru tersebut (lebih aman dan terkontrol)
```

**Menganalisis Pola Peluncuran yang Aman**

Preferensi pengguna menguraikan sebuah alur kerja yang aman: SIMRS memicu aktivasi ekstensi hanya saat pengguna melakukan klik. SIMRS kemudian secara aman meneruskan sinyal/kredensial sekali pakai ke ekstensi untuk tab baru tersebut. Pendekatan ini memprioritaskan keamanan dan kendali dengan mengaitkan secara ketat peluncuran portal ke tindakan pengguna di dalam lingkungan SIMRS.

**Mendefinisikan Selektor Kolom yang Tangguh**

Selektor CSS standar akan menjadi sangat penting. Mempertimbangkan potensi terjadinya perubahan dinamis, selektor yang lebih tangguh sangat dibutuhkan. Manfaatkan atribut seperti `type`, `placeholder`, dan kemungkinan struktur DOM (misalnya, hubungan elemen induk) untuk menargetkan kolom secara akurat.

Strategi cadangan (*fallback*) akan menjadi penting. Jika atribut langsung `id` atau `name` tidak ada, atribut lain di dalam elemen penampung kolom tersebut perlu dipertimbangkan. Master portal harus memungkinkan admin untuk merangkai berbagai jenis selektor, memberikan fleksibilitas untuk berbagai struktur formulir yang bervariasi.

```
? Bagaimana Anda ingin mengelola konfigurasi selector form login di Master Portal agar fleksibel menghadapi website yang tidak punya id/name atau berubah di kemudian hari?
> (Recommended) Format JSON / Dynamic Fields di Master Portal: Mendukung multi-strategi (CSS selector, name, id, XPath, placeholder, type) dengan delay/wait-for-element untuk web berbasis SPA (React/Vue)
```

**Memahami Konfigurasi Masuk yang Fleksibel**

Konfigurasi ini mendukung beberapa strategi untuk mengidentifikasi kolom masuk (*login fields*). Admin menentukan selektor kolom dan pola URL. Hal ini memungkinkan penanganan yang fleksibel untuk berbagai aplikasi web yang berbeda. Konfigurasi ini memanfaatkan fungsionalitas penungguan elemen (*wait-for-element*). Ini memastikan ketersediaan kolom sebelum mencoba berinteraksi dengan elemen pada Aplikasi Halaman Tunggal (*Single Page Applications* / SPA).

**Menerapkan Logika Deteksi Ekstensi**

Perlu menentukan cara menangani kasus ketika ekstensi peramban tidak ada atau tidak aktif. Merancang mekanisme untuk mendeteksi status ekstensi sangatlah krusial. Mempertimbangkan penggunaan tag meta, variabel global jendela (*window global*), atau ping untuk membangun komunikasi.

Jika ekstensi tidak terdeteksi, spanduk atau peringatan yang ramah pengguna harus ditampilkan, menyediakan jalur yang mudah bagi pengguna untuk memasang ekstensi tersebut. Bahkan tanpa ekstensi tersebut, tautan portal harus tetap berfungsi. Jika terdeteksi, tampilkan status "Aktif" beserta informasi versinya.

```
? Bagaimana penanganan di halaman Portal Sifast jika browser pengguna belum memasang atau menonaktifkan Browser Extension?
> (Recommended) Ya, tampilkan deteksi status ekstensi: Jika belum terpasang, tampilkan panduan download/install ekstensi, namun link web eksternal tetap bisa dibuka secara manual
```

**Merangkum Elemen-Elemen Arsitektural Utama**

Sistem mendukung kredensial institusi maupun pribadi. UX portal menampilkan semua opsi eksternal tanpa pembatasan langsung, dengan pengisian otomatis (*autofill*) yang hanya diaktifkan untuk pemetaan kredensial yang telah tervalidasi. Pemicunya berasal dari mekanisme pengiriman yang aman saat pengguna mengeklik tautan portal.

```
? Bagaimana rencana distribusi ekstensi browser ini kepada petugas/komputer rumah sakit?
> Siapkan untuk keduanya: Source code extension siap di-pack lokal (.zip) untuk testing internal SIMRS dan memiliki struktur siap rilis ke Chrome Web Store
```

**Meninjau Langkah-Langkah Pendekatan Arsitektural**

Eksplorasi konteks proyek telah selesai. Alat bantu visual tidak segera diperlukan saat ini; fokusnya kini beralih pada pengusulan pendekatan arsitektural dan pembagian desain ke dalam beberapa bagian. Pertanyaan klarifikasi mengenai model kredensial dan UX tampilan portal telah dijawab.

**Mengevaluasi Pendekatan Integrasi Portal yang Aman**

Mempertimbangkan tujuan utama yaitu transfer kredensial yang aman, arsitektur harus mencegah paparan langsung. Pendekatan 1, yang menggunakan pengiriman peristiwa DOM (*DOM event dispatch*) sekali pakai dari *frontend* setelah otorisasi *backend* dan pembuatan *payload*, tampak menjanjikan. Pendekatan ini meminimalkan paparan kredensial dan memusatkan kendali pada *backend*.

Pendekatan kedua mungkin melibatkan komunikasi ekstensi secara langsung, tetapi menimbulkan risiko kebocoran kredensial jika tidak diamankan dengan sangat teliti. Opsi ketiga, yang berfokus pada sesi sisi server (*server-side session*), akan menjadi kurang aman dan rumit, sehingga merupakan opsi yang paling tidak disukai. Saya akan menyempurnakan pendekatan yang dipilih lebih lanjut.

**Merinci Implementasi Pendekatan Satu**

Arsitektur yang dipilih memanfaatkan penanganan peristiwa skrip konten (*content script's event handling*) untuk mengelola transfer kredensial secara aman. Skrip latar belakang (*background script*) milik ekstensi membuka situs web target di tab baru dan mengorelasikannya dengan kredensial yang tertunda. Skrip konten kemudian mengambil kredensial dari skrip latar belakang, mengisi kolom**Merinci Implementasi Pendekatan Pertama**

Arsitektur yang dipilih memanfaatkan penanganan *event* (*event handling*) pada *content script* untuk mengelola transfer kredensial secara aman. *Background script* pada ekstensi membuka situs web target di tab baru dan mengorelasikannya dengan kredensial yang sedang tertunda (*pending credentials*). *Content script* kemudian mengambil kredensial dari *background script*, mengisi bidang formulir (*form fields*), serta menyimulasikan *input event* pengguna demi kompatibilitas yang kuat. Kredensial langsung dihapus segera setelah digunakan.
