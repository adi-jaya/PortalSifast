# Portal Pelaporan Brainstorming

Saya sedang mengembangkan fitur/halaman/menu portal di aplikasi SIMRS. Halaman ini akan digunakan sebagai agregator tautan ke berbagai platform/website eksternal. Di website eksternal tersebut pengguna harus login, dan saya ingin membuat Custom Browser Extension (Manifest V3) untuk mengisi form login (username & password) secara otomatis (autofill), sementara pengisian CAPTCHA (jika ada) tetap diselesaikan/dilakukan secara manual oleh pengguna.

Selain membuat halaman portal dan extension diperlukan juga hal-hal lain atau menu-menu lain misal:
1. Master Portal (nama sementara) untuk menagement website external seperti gambar, nama website, status aktif, input form login yang digunakan, dan data lain yang diperlukan. Mengenai input form login, setiap website kemungkinan akan berbeda-beda, misal ada yang menggunakan username atau email atau kode/nomor pengenal, dll.
2. Management atau mapping akses antara akses user portal sifast dan akses user website external. 

Contoh:
Misal, ada 10 user/petugas rumah sakit dan ada 5 website external dari KEMENKES.

User 1 -> Website 1-5 -> User memiliki akses ke semua website
User 2 -> Website 1-3 -> User memiliki akses ke website tertentu
User 3 -> Website 1 -> User memiliki akses ke satu webiste saja

Alur:
1. User login ke SIMRS
2. User membuka menu Portal Pelaporan
3. User meng-klik tautan ke website external. Belum diputusan bagaimana desainnya, misal apakah portal yang tampil hanya bisa dilihat oleh user yang memiliki akses ke protal tersebut atau semua portal tampil dan bisa di klik oleh user atau desain lainnya. Ada aspek user experience dan kemudahan implementasi yang masih perlu dipertimbangkan lagi.
4. Browser membuka halaman di tab baru
5. Input nilai di form login terinput otomatis (autofill) menggunakan extension yang dibuat

Alamat Website External:
1. SIRIKA -> https://siga-sirika.bkkbn.go.id/login
    Input Form Login:
    - ID Pengguna
        - name = email
        - id = c
    - Kata Sandi
        - name = password
        - id = password
2. SIGA -> https://newsiga-siga.kemendukbangga.go.id/#/login
    Input Form Login:
    - ID Pengguna
        - name = email
        - id = email
    - Kata Sandi
        - name = password
        - id = password
3. SIHA -> https://sihapims2.kemkes.go.id/login
    Input Form Login:
    - Username
        - name = username
        - id = username
    - Kata Kunci
        - name = password
        - id = password
4. MPDN -> https://mpdn.kemkes.go.id/masuk
    Input Form Login:
    - Username, Email atau Nomor HP
        - name = username
        - id = username
    - Password
        - name = password
        - id = password
5. SITB -> https://jatim.sitb.id/sitb2024/app
    Input Form Login:
    - Username
        - name = username
        - id =
    - Kata sandi
        - name = password
        - id = password
6. SIGIZI -> https://sigizikesga-stg.kemkes.go.id
    Input Form Login:
    - Username
        - name = username
        - id = username
    - Kata Kunci
        - name = password
        - id = password
7. SATU SEHAT -> https://satusehat.kemkes.go.id/platform/login
    Input Form Login:
    - Alamat Email
        - name = email
        - id = email
    - Kata sandi
        - name = password
        - id = password
8. https://mutufasyankes.kemkes.go.id/
    - IKP -> https://mutufasyankes.kemkes.go.id/halaman/dashboard
        Input Form Login:
        - Username
            - name = user
            - id = user
        - Password
            - name = pass
            - id = pass
    - PPRA -> https://mutufasyankes.kemkes.go.id/ppra/
        Input Form Login:
        - Kode Satker
            - name = username
            - id =
        - Password
            - name = password
            - id =
    - SIMAR (Pelaporan INM dan HAIs) -> https://mutufasyankes.kemkes.go.id/simar/
        Input Form Login:
        - Username
            - name = uname
            - id = uname
        - Password
            - name = pwd
            - id = pwd
    - SIRS Online -> https://akun-yankes.kemkes.go.id/
        Input Form Login:
        - Email (tidak memiliki attribute id dan name)
        - Password (tidak memiliki attribute id dan name)
        - Diketahui:
            Request URL: https://akun-yankes.kemkes.go.id/sso/v1/login
            Payload: {"email":"adijaya.djay47@gmail.com","password":"Lorem"}

Setiap website memiliki input yang berbeda beda bahkan ada yang tidak menyertakan attribut id dan name di element inputnya serta ada kemungkinan di kemudian hari element form loginnya berubah.

## [Clarification](/docs/portal-pelaporan-brainstorming/clarification.md)
## [Approach](/docs/portal-pelaporan-brainstorming/approach.md)
## [Bagian 1: Model Data dan Skema Database](/docs/portal-pelaporan-brainstorming/bagian-1-model-data-dan-skema-database.md)
## [Bagian 2: Form Login Selector Engine & Fallback Architecture](/docs/portal-pelaporan-brainstorming/bagian-2-form-login-selector-engine-dan-fallback-architecture.md)
## [Bagian 3: Arsitektur Ekstensi Browser & Secure One-Time Event Dispatch](/docs/portal-pelaporan-brainstorming/bagian-3-arsitektur-ekstensi-browser-dan-secure-one-time-event-dispatch.md)
## [Bagian 4: Antarmuka Pengguna (Frontend UI/UX)](/docs/portal-pelaporan-brainstorming/bagian-4-antarmuka-pengguna.md)