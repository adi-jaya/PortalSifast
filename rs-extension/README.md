# SIFAST Portal Autofill Extension (Manifest V3)

Ekstensi browser resmi Rumah Sakit Aisyiyah Siti Fatimah Tulangan untuk pengisian otomatis (_autofill_) kredensial login pada portal pelaporan eksternal Kementerian Kesehatan RI dan BKKBN secara aman (_Zero-Persistence_).

---

## Fitur Utama

1. **Zero-Persistence In-Memory Queue:**
    - Kredensial tidak pernah disimpan di disk atau storage browser (`chrome.storage.local`, `localStorage`, `cookies`).
    - Kredensial dikirim satu kali (_one-time transfer_) dari SIMRS, ditampung di RAM Service Worker dengan batas waktu (TTL) 30 detik, dan **langsung dihapus seketika** (_auto-flush_) saat form login target selesai diisi.
2. **Framework-Resilient Synthetic Dispatcher:**
    - Mendukung website berbasis SPA modern (React, Vue, Angular) melalui bypass native setter descriptor (`setNativeValue`) dan dispatch event `input`, `change`, serta `blur`.
3. **Runtime Heuristic Scanner:**
    - Mampu mengisi form login secara otomatis meskipun website target tidak memiliki atribut `id` atau `name` (misalnya pada SIRS Online).
4. **Manual CAPTCHA Safety:**
    - Tidak pernah membypass CAPTCHA secara ilegal. Kursor otomatis diarahkan (_auto-focus_) ke bidang input CAPTCHA agar pengguna dapat langsung mengetik CAPTCHA.
5. **Admin Form Inspector:**
    - Popup ekstensi dilengkapi tombol 1-klik untuk memindai struktur form website pelaporan baru dan menghasilkan konfigurasi `form_config` JSON yang dapat langsung disalin ke menu Admin Portal SIMRS.

---

## Struktur Berkas

```
rs-extension/
├── manifest.json              # Konfigurasi Manifest V3
├── background.js              # Service Worker: RAM queue, 30s TTL, auto-flush
├── content-simrs.js           # Bridge deteksi ekstensi di SIMRS
├── content-autofill.js        # Engine autofill & heuristic scanner di portal target
├── popup/
│   ├── popup.html             # UI status ekstensi & Admin Inspector
│   ├── popup.css              # Styling popup
│   └── popup.js               # Logika pemindaian form 1-klik
├── icons/                     # Berkas icon (16px, 48px, 128px)
├── scripts/
│   └── generate-icons.js      # Generator icon PNG
└── tests/                     # Test suite otomatis (node:test)
```

---

## Cara Instalasi di Browser (Mode Pengembang)

1. Buka browser berbasis Chromium (Google Chrome, Microsoft Edge, Brave, atau Opera).
2. Akses halaman pengelolaan ekstensi:
    - Google Chrome: `chrome://extensions/`
    - Microsoft Edge: `edge://extensions/`
3. Aktifkan **Mode Pengembang** (_Developer Mode_) di pojok kanan atas.
4. Klik tombol **Muat yang belum dibongkar** (_Load unpacked_).
5. Pilih folder `rs-extension/` dari repositori ini.
6. Ekstensi **SIFAST Portal Autofill Assistant** akan muncul dalam daftar ekstensi yang aktif.

---

## Menjalankan Pengujian Otomatis

Ekstensi ini dilengkapi unit & integration test runner bawaan Node.js (`node:test`) tanpa dependensi tambahan:

```bash
npm run test:extension
```

Test suite mencakup:

- Validasi skema Manifest V3 & ketersediaan icon (`manifest-validation.test.js`).
- Antrean RAM, TTL 30 detik, dan auto-flush Service Worker (`background.test.js`).
- Handshake deteksi DOM pada portal SIMRS (`content-simrs.test.js`).
- Engine autofill, prototype setter, dan heuristic scanner (`content-autofill.test.js`).
- Logika ekstraksi selector pada Admin Form Inspector (`popup-inspector.test.js`).
- Simulasi alur penuh (_End-to-End_) dari peluncuran hingga pembersihan memori (`e2e-simulation.test.js`).
