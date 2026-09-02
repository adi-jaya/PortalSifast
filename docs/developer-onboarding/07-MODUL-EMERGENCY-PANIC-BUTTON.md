# 🚨 Modul 07: Sistem Tanggap Darurat dan Panic Button

Modul Tanggap Darurat (*Emergency Response & Panic Tracking*) adalah modul kritis di Portal Sifast yang digunakan untuk penanganan respon cepat insiden medis dan keselamatan (Code Blue, Code Red, Code Black, dsb).

---

## 1. Alur Kerja Tanggap Darurat End-to-End

```mermaid
sequenceDiagram
    autonumber
    actor Pelapor as Pegawai / Pelapor
    participant Portal as Portal Sifast Core
    participant FCM as Firebase (FCM)
    actor Petugas as Petugas Tanggap (IGD/Driver/Satpam)
    participant Dashboard as Command Center Dashboard

    Pelapor->>Portal: Trigger Panic Button (Tipe Insiden + Lokasi GPS / Ruang)
    Portal->>Portal: Simpan EmergencyReport (Status: Open)
    Portal->>FCM: Broadcast High-Priority Push Notification ke Petugas
    Portal->>Dashboard: Broadcast Event via WebSocket Reverb (Alarm Bunyi & Pin Peta)
    FCM->>Petugas: Notifikasi Suara Keras di Handphone Petugas
    Petugas->>Portal: POST /emergency/reports/{id}/accept (Ambil Tugas)
    Dashboard->>Dashboard: Update Status "Petugas Dalam Perjalanan"
    loop Setiap 5 Detik
        Petugas->>Portal: POST /api/sifast/officer/location (Update Koordinat GPS)
        Portal->>Dashboard: Hitung Jarak (Haversine Formula) & Gerakkan Marker Petugas
    end
    Petugas->>Portal: POST /emergency/reports/{id}/arrived (Tiba di Lokasi)
    Petugas->>Portal: PATCH /emergency/reports/{id}/respond (Insiden Selesai / Resolved)
```

---

## 2. Struktur Data dan Perhitungan Jarak (Haversine)

```mermaid
erDiagram
    EMERGENCY_REPORTS ||--o{ PANIC_AUDIT_LOGS : "kronologi insiden"
    USERS ||--o{ EMERGENCY_REPORTS : "dilaporkan oleh"
    USERS ||--o{ EMERGENCY_REPORTS : "ditangani oleh (responder)"
    USERS ||--o{ OFFICER_LOCATIONS : "posisi real-time"
    USERS ||--o{ FCM_DEVICE_TOKENS : "device notification"
```

### A. Algoritma Haversine (`HaversineService.php`)
Sistem menghitung jarak garis lurus (*great-circle distance*) antara koordinat GPS petugas dan lokasi kejadian secara presisi di server:

$$d = 2r \arcsin \left( \sqrt{\sin^2\left(\frac{\Delta \phi}{2}\right) + \cos(\phi_1) \cos(\phi_2) \sin^2\left(\frac{\Delta \lambda}{2}\right)} \right)$$

Hasil kalkulasi jarak (dalam meter atau kilometer) dikirimkan ke frontend Command Center untuk memprediksi *Estimated Time of Arrival (ETA)*.

---

## 3. Komponen Kunci Backend

* **[`EmergencyFcmService.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Services/EmergencyFcmService.php):**
  * Bertanggung jawab mengirim notifikasi multi-target ke perangkat yang terdaftar di tabel `fcm_device_tokens`.
  * Menggunakan channel *Emergency Alert* dengan volume penuh dan vibration pattern panjang.
* **[`CheckPendingPanicCommand.php`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/app/Console/Commands/CheckPendingPanicCommand.php):**
  * Dijalankan secara otomatis oleh scheduler.
  * Memeriksa apakah ada laporan darurat berstatus `open` yang belum direspon lebih dari 2 menit, lalu memicu notifikasi eskalasi ulang.
* **Throttle Khusus Officer:**
  * Endpoint `POST /api/sifast/officer/location` dilindungi middleware `throttle:20,1` (maksimal 20 request per menit per petugas) agar tracking lokasi setiap ~5 detik berjalan lancar tanpa terblokir rate limiter standar.
