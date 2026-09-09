# Bagian 3: Arsitektur Ekstensi Browser & Secure One-Time Event Dispatch

**Melacak Komunikasi Ekstensi Peramban**

Arsitektur ini melibatkan ekstensi Manifes V3 yang memfasilitasi komunikasi antara ekstensi, *backend*, dan tab web. Menjelajahi struktur `manifest.json` adalah titik awal untuk memahami alur komunikasi ini, khususnya `manifest_version` dan mengidentifikasi bagaimana ekstensi tersebut dideklarasikan. Selanjutnya, memetakan siklus hidup *event* (*event lifecycle*) sangatlah krusial, dengan fokus pada pengiriman aman *event* sekali pakai antara komponen ekstensi dan halaman web target. Terakhir, alur *event* harus memastikan interaksi yang aman dengan PortalSifast.

**Menganalisis Mekanisme Deteksi Ekstensi**

Jembatan SIMRS (`content-simrs.js`) kemungkinan menerapkan metode untuk memberi sinyal keberadaannya ke aplikasi web SIMRS saat halaman dimuat. Hal ini melibatkan penyuntikan atribut DOM, atau pengiriman *window event*. Skrip ini juga akan merespons *ping event* dari halaman, mengirimkan kembali informasi versi ekstensi ke aplikasi SIMRS. Jika sinyal ini tidak diterima dalam batas waktu yang ditentukan, aplikasi SIMRS kemungkinan menampilkan pemberitahuan mengenai kondisi ekstensi yang tidak ditemukan.

Bagian ini mengatur bagaimana ekstensi browser (Manifest V3) berinteraksi dengan aman antara portal SIMRS dan website eksternal target.

[ Pengguna klik Portal di SIMRS ]
                >
                ▼
        (1) POST /portal/{id}/dispatch
                > Backend verifikasi akses & dekripsi kredensial
                ▼
        (2) Respons Kredensial ke Halaman SIMRS
                >
                ▼
        (3) CustomEvent: 'SIFAST_PORTAL_LAUNCH'
                >
                ▼
        (4) Content Script SIMRS (content-simrs.js)
                > chrome.runtime.sendMessage
                ▼
        (5) Background Service Worker (background.js)
            ├── Buka tab baru: chrome.tabs.create({ url }) -> Dapatkan targetTabId
            └── Simpan di RAM sementara: pendingTabs[targetTabId] (TTL: 30 detik)
                >
                ▼ (Tab Target Terbuka)
        (6) Content Script Target (content-autofill.js)
            ├── Tanya background: "Apakah ada kredensial untuk tab saya?"
            └── Background kirim kredensial & LANGSUNG HAPUS dari RAM (Flush)
                >
                ▼
        (7) Eksekusi Autofill (Config Selector / Heuristic Scanner)
            └── Fokus ke input CAPTCHA & tampilkan toast sukses

## 1. Komponen Ekstensi (Manifest V3)

* manifest.json:
    * manifest_version: 3
    * Perizinan: ["tabs", "scripting", "storage"]
    * Host permissions: Domain SIMRS (*.rsasf.co.id, localhost) dan wildcard instansi pemerintah (*.kemkes.go.id, *.bkkbn.go.id, *.sitb.id, dll.).
* content-simrs.js (Jembatan SIMRS):
    * Hanya berjalan di domain SIMRS Sifast.
    * Mengirimkan sinyal deteksi bahwa ekstensi aktif (document.documentElement.dataset.sifastExtension = "1.0.0").
    * Menangkap event klik portal dari web SIMRS dan meneruskannya ke Background Worker.
* background.js (Service Worker):
    * Mengatur pembukaan tab baru (chrome.tabs.create).
    * Mengelola antrean kredensial sementara berbasis tab.id di memori RAM (tidak disimpan di disk/storage).
    * Menghapus kredensial segera setelah diambil oleh tab target atau otomatis kedaluwarsa setelah 30 detik.
* content-autofill.js (Injektor Web Target):
    * Berjalan di domain website eksternal target.
    * Meminta kredensial untuk tabId-nya ke background worker.
    * Menjalankan Selector Engine (Rule-based) dan Runtime Heuristic Scanner untuk mengisi nilai input dan memicu event React/Vue.
* popup.html (Admin Inspector / Tool):
    * Berisi status koneksi ekstensi.
    * Memiliki fitur tombol "Scan Halaman Login Ini" untuk admin saat ingin mendaftarkan web baru.


## 2. Deteksi Status Ekstensi di Halaman SIMRS

* Saat halaman Portal Pelaporan dimuat, antarmuka React memeriksa apakah content-simrs.js aktif:
    * Jika Terdeteksi: Ditampilkan indikator hijau Ekstensi Aktif v1.0.0.
    * Jika Belum Terpasang: Ditampilkan banner informatif:
    > "Ekstensi Autofill Sifast belum terdeteksi. Silakan [Unduh Ekstensi] untuk mengaktifkan pengisian login otomatis. Anda tetap dapat membuka portal secara manual."

    * Jika belum terpasang dan pengguna mengklik kartu portal, web target tetap terbuka di tab baru secara manual (tanpa autofill) sehingga pekerjaan staf tidak terhenti.


## 3. Keamanan Tingkat Tinggi (Zero-Persistence)

1. Tidak Ada Penyimpanan Password di Ekstensi: Ekstensi tidak pernah menyimpan username/password di chrome.storage.local.
2. Kredensial Hanya Hidup Beberapa Detik: Begitu tab target mengambil kredensial, background worker langsung menghapusnya dari memori (pendingTabs.delete(tabId)).
3. Isolasi Berbasis tabId: Tab lain atau web lain tidak bisa meminta kredensial yang bukan miliknya.