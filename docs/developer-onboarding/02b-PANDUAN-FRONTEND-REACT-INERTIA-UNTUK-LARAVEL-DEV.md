# ⚛️ Modul 02b: Panduan Frontend React 19 & Inertia.js v2 untuk Developer Laravel (Transisi dari Blade & jQuery)

Dokumen ini merupakan panduan komprehensif bagi developer Laravel di lingkungan RS Aisyiyah Siti Fatimah Tulangan (Sifast) untuk memahami dan menguasai arsitektur frontend modern berbasis **React 19**, **Inertia.js v2**, **TypeScript**, **Tailwind CSS v4**, **Radix UI Primitives**, dan **Laravel Wayfinder**.

Modul ini dirancang khusus untuk menjembatani pergeseran paradigma dari pola monolitik tradisional (Blade templates, jQuery `$('#id')`, manual AJAX, dan routing Ziggy `route()`) menuju arsitektur modern berbasis state reaktif yang cepat, terstruktur, dan type-safe.

---

## Metadata & Target Pembaca

| Entitas | Rincian |
| :--- | :--- |
| **Kode Dokumen** | `MOD-02B-FE-REACT-INERTIA` |
| **Versi Dokumen** | 1.1.0 (Tahap Fondasi, Bab 1 & Bab 2 Bedah Kasus Lengkap) |
| **Status Dokumen** | Produksi Aktif / Terverifikasi |
| **Tanggal Pembaruan** | 2026-09-03 |
| **Tech Stack Utama** | React 19, Inertia.js v2, TypeScript 5+, Tailwind CSS v4, Radix UI Primitives, Laravel Wayfinder |
| **Target Pembaca** | **Junior Developer** (memerlukan analogi intuitif, panduan langkah-demi-langkah, dan solusi gotchas)<br/>**Senior Developer** (memerlukan matriks padanan cepat, arsitektur under-the-hood, dan rationale desain) |
| **Kaitan Modul Terkait** | [`00-INDEX-DAN-PANDUAN-MEMBACA.md`](./00-INDEX-DAN-PANDUAN-MEMBACA.md)<br/>[`01-ARSITEKTUR-DAN-TECH-STACK.md`](./01-ARSITEKTUR-DAN-TECH-STACK.md)<br/>[`02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md`](./02-STRUKTUR-PROJECT-DAN-STANDAR-KODE.md)<br/>[`11-REALTIME-WEBSOCKET-DAN-PRESENSI.md`](./11-REALTIME-WEBSOCKET-DAN-PRESENSI.md) |

---

## 🧭 Filosofi Desain Dual-Audience ("Skim or Deep Dive")

Untuk melayani pengembang dengan latar belakang pengalaman yang beragam, seluruh modul ini mengadopsi prinsip **Dual-Layer Information Architecture**:

```mermaid
graph TD
    User([Developer Masuk ke Dokumen]) --> Split{Pilih Pendekatan Belajar}
    
    Split -->|Senior / Berpengalaman Laravel| FastTrack["🚀 Fast-Track (Skim & Implement)<br/>1. Baca TL;DR Quick Reference Table<br/>2. Pahami Under the Hood & Protocol XHR<br/>3. Copy production-grade snippet Wayfinder & useForm"]
    Split -->|Junior / Transisi dari Blade| Guided["🌱 Guided Path (Deep Dive & Master)<br/>1. Pahami analogi dunia nyata (Colokan Hooks)<br/>2. Ikuti diagram alur siklus hidup komponen<br/>3. Pelajari katalog Gotchas agar tidak terjebak"]

    FastTrack --> Production["✅ Kode Bersih, Efisien, Bebas Antipattern"]
    Guided --> Production
```

* **Bagi Developer Senior (Fast-Track / Skim-Friendly):**
  * Di setiap bab disediakan **TL;DR Matrix** berisi komparasi sintaks langsung. Anda dapat membaca tabel dalam 1-2 menit dan langsung mengetahui padanan fitur tanpa membaca narasi panjang.
  * Dilengkapi callout **"Under the Hood & Architectural Rationale"**: Mengapa Wayfinder menggantikan Ziggy, bagaimana protokol `X-Inertia` menangani partial reloads dan browser history, serta mengapa React 19 Compiler mengotomatisasi memoization tanpa manual `useMemo`.
* **Bagi Developer Junior (Guided / Intuitive-Friendly):**
  * Disertai **analogi dunia nyata** yang menjembatani kebiasaan lama di Blade dan jQuery.
  * **Diagram alur visual (Mermaid)** untuk memahami siklus request-response dan perpindahan state.
  * Anotasi kode yang jelas pada studi kasus nyata di repositori Sifast.
  * Bagian khusus **"Gotchas & Jebakan Pemula"** yang merinci pesan error umum (seperti *"Objects are not valid as a React child"*, input form membeku, atau layar putih kosong) beserta solusinya.

---

## 🗺️ Peta Alur Modul (Roadmap 6 Bab)

Modul ini tersusun ke dalam 6 bab bertahap yang membimbing Anda dari pemahaman mental model hingga debugging tingkat lanjut:

```mermaid
graph TD
    subgraph Modul02b ["02b: Panduan Frontend React 19 & Inertia v2"]
        direction TB
        B1["Bab 1: Pergeseran Paradigma & Kamus Padanan<br/><i>(Mental Model Shift, Hooks, & Konsep Inti React)</i>"]
        B2["Bab 2: Bedah Kasus Nyata Modul Tiket<br/><i>(Alur Data Controller -> React Props -> Live Filter)</i>"]
        B3["Bab 3: Tutorial Hands-on CRUD Step-by-Step<br/><i>(Studi Kasus Modul Projects dari Nol & Refactor ConfirmDialog)</i>"]
        B4["Bab 4: Arsitektur Styling & Komponen UI<br/><i>(Tailwind v4 @theme, Radix Primitives, Helper cn)</i>"]
        B5["Bab 5: Reaktivitas Real-Time & WebSockets<br/><i>(Reverb, Echo, Presence Tracking, Dual-Source Config)</i>"]
        B6["Bab 6: Anti-Patterns, Gotchas & Debugging Toolkit<br/><i>(Error Catalog Pemula & React 19 Compiler Insights)</i>"]
        
        B1 --> B2 --> B3 --> B4 --> B5 --> B6
    end
```

---

## Bab 1: Pergeseran Paradigma & Kamus Padanan (Blade/jQuery vs React 19/Inertia v2)

Perbedaan terbesar antara pengembangan Laravel Blade konvensional dan React/Inertia terletak pada **siapa yang bertanggung jawab merender HTML dan mengelola state**:
1. **Dunia Blade & jQuery:** Server membuat HTML secara lengkap pada setiap request. Jika ada perubahan dinamis di browser, jQuery mencari elemen DOM menggunakan selector (`$('#id')`), lalu memanipulasi teks, class, atau atribut secara imperatif (*Imperative DOM-Driven*).
2. **Dunia React & Inertia:** Server hanya mengirimkan data mentah (JSON props), sedangkan browser merender UI menggunakan komponen React. Tampilan antarmuka adalah representasi langsung dari data state saat ini (*Declarative State-Driven*):
   $$\text{UI} = f(\text{state})$$
   Anda tidak pernah menyentuh DOM secara langsung. Anda hanya mengubah state, dan React secara otomatis memperbarui bagian DOM yang terdampak.

---

### 1.1 TL;DR Matrix Padanan Cepat (Bagi Senior)

Tabel berikut merangkum padanan langsung fitur-fitur yang biasa Anda gunakan di Blade dan jQuery dengan padanannya di React 19 dan Inertia.js v2 di Portal Sifast:

| Fitur / Kebutuhan | Pola Lama (Blade & jQuery) | Pola Modern (React 19 & Inertia v2 di Sifast) | Keterangan & Prinsip Desain |
| :--- | :--- | :--- | :--- |
| **Kondisional Render** | `@if($isAdmin)`<br/>`  <span>Admin</span>`<br/>`@else`<br/>`  <span>Staff</span>`<br/>`@endif` | `{isAdmin ? (`<br/>`  <span>Admin</span>`<br/>`) : (`<br/>`  <span>Staff</span>`<br/>`)}`<br/><br/>*Atau jika tanpa else:*<br/>`{isAdmin && <span>Admin</span>}` | Ekspresi JavaScript murni di dalam kurung kurawal `{}` JSX. Hindari ternary bersarang yang terlalu dalam. |
| **Perulangan Daftar** | `@foreach($tickets as $item)`<br/>`  <div>{{ $item->title }}</div>`<br/>`@endforeach` | `{tickets.map((item) => (`<br/>`  <div key={item.id}>{item.title}</div>`<br/>`))}` | Wajib menyertakan atribut `key={item.id}` unik pada elemen terluar untuk efisiensi diffing Virtual DOM. |
| **Proteksi CSRF Form** | `<form method="POST">`<br/>`  @csrf`<br/>`  ...`<br/>`</form>` | *Otomatis (Zero Config)*<br/>Inertia & Axios membaca cookie `XSRF-TOKEN` dan menyertakannya di header HTTP `X-XSRF-TOKEN`. | Tidak perlu tag `<input type="hidden" name="_token">` manual di form React. |
| **Membaca Nilai Input** | `const val = $('#title').val();` | `const { data } = useForm({ title: '' });`<br/>`console.log(data.title);` | Komponen terkontrol (*Controlled Component*). Nilai input selalu dicerminkan oleh state. |
| **Mengubah Nilai Input** | `$('#title').val('Komputer Rusak');` | `setData('title', 'Komputer Rusak');` | Mengubah state memicu sinkronisasi otomatis ke atribut `value` input HTML. |
| **Memperbarui Konten DOM** | `$('#status-badge').html('`<span class="badge">Selesai</span>`');` | `const [status, setStatus] = useState('open');`<br/>`...`<br/>`<Badge>{status}</Badge>` | UI dideklarasikan sekali. Tampilan berubah reaktif saat `setStatus('closed')` dipanggil. |
| **Menampilkan Validasi Error** | `@error('title')`<br/>`  <span class="text-red-500">{{ $message }}</span>`<br/>`@enderror` | `<InputError message={errors.title} />`<br/>*(dari hook `useForm` Inertia)* | Validasi server dari `FormRequest` Laravel dipetakan langsung ke objek `errors`. |
| **URL Routing Helper** | `route('tickets.show', $ticket->id)` *(Ziggy global)* | `import { show } from '@/routes/tickets';`<br/>`<Link href={show(ticket.id).url}>Detail</Link>` | **Laravel Wayfinder**: Route helper modular, type-safe saat kompilasi TypeScript, dan tree-shakeable. |
| **Navigasi Antar Halaman** | `<a href="/tickets" class="btn">Daftar Tiket</a>` | `import { Link } from '@inertiajs/react';`<br/>`<Link href="/tickets" className="btn">Daftar Tiket</Link>` | `<Link>` mencegat klik browser untuk melakukan request XHR tanpa reload penuh (*Single Page Application*). |
| **Flash Session Message** | `@if(session('success'))`<br/>`  <div class="alert">{{ session('success') }}</div>`<br/>`@endif` | `const { flash } = usePage().props;`<br/>`{flash.success && <Alert>{flash.success}</Alert>}` | Shared props dari middleware `HandleInertiaRequests` dapat diakses di mana saja via `usePage()`. |
| **Autentikasi User** | `Auth::user()->name`<br/>`@auth ... @endauth` | `const { auth } = usePage().props;`<br/>`<span>{auth.user.name}</span>` | Data user login tersedia secara global di seluruh komponen halaman React. |

> [!TIP]
> **Mental Model Shift untuk Developer Senior:**
> Lupakan siklus *"Cari elemen di DOM lalu ubah propertinya"* ala jQuery. Dalam React, Anda tidak pernah memerintahkan browser untuk *"menambahkan class `hidden` ke tombol A"* atau *"mengganti teks elemen `#total`"*. Sebaliknya, tentukan kondisi data:
> ```tsx
> const [isLoading, setIsLoading] = useState(false);
> // Tampilan secara deklaratif merespon isLoading:
> <Button disabled={isLoading}>
>   {isLoading ? 'Menyimpan...' : 'Simpan Tiket'}
> </Button>
> ```
> Saat `setIsLoading(true)` dipanggil, React mengevaluasi ulang JSX dan memperbarui DOM secara presisi dan efisien.

---

### 1.2 Arsitektur Single Blade Shell (`resources/views/app.blade.php`)

Salah satu kejutan terbesar bagi pengembang Laravel yang baru berpindah ke Inertia adalah: **Mengapa folder `resources/views/` hampir kosong?**

Di Portal Sifast, seluruh antarmuka web dilayani oleh satu-satunya file Blade induk: [`resources/views/app.blade.php`](../../resources/views/app.blade.php).

#### 1. Bedah Anatomi `app.blade.php`

Berikut adalah struktur lengkap file shell induk di Portal Sifast:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Deteksi preferensi dark mode sistem sebelum render utama agar tidak berkedip (FOUC) --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';
                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Style inline untuk background dasar agar seirama dengan tema --}}
        <style>
            html { background: #F8FAFC; }
            html.dark { background: #0F172A; }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Tipografi Inter Utama --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">

        {{-- Konfigurasi Reverb/WebSocket dari Backend --}}
        @if(config('broadcasting.default') === 'reverb')
        @php
            $reverbHost = config('broadcasting.connections.reverb.options.client_host')
                ?? request()->getHost();
            $reverbHost = str_contains($reverbHost, ':') ? explode(':', $reverbHost)[0] : $reverbHost;

            $reverbPort = config('broadcasting.connections.reverb.options.client_port')
                ?? (int) config('broadcasting.connections.reverb.options.port', 8080);

            $reverbScheme = config('broadcasting.connections.reverb.options.client_scheme')
                ?? (config('broadcasting.connections.reverb.options.scheme') ?? 'http');

            if (request()->secure()) {
                $reverbScheme = 'https';
            }

            $reverbConfig = [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => $reverbHost,
                'port' => (int) $reverbPort,
                'scheme' => $reverbScheme,
            ];
        @endphp
        <script>
            window.REVERB_CONFIG = @json($reverbConfig);
        </script>
        @else
        <script>window.REVERB_CONFIG = null;</script>
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
```

#### 2. Peran Direktif `@inertiaHead` dan `@inertia`

* **`@inertiaHead`:** Direktif ini bertindak sebagai gerbang dinamis untuk elemen `<head>`. Ketika halaman React menentukan judul via komponen `<Head title="Daftar Tiket ITIL" />`, Inertia menyinkronkan tag `<title>` dan `<meta>` browser tanpa memerlukan full page reload.
* **`@inertia`:** Direktif ini merender sebuah elemen penampung root di dalam `<body>`:
  ```html
  <div id="app" data-page="{&quot;component&quot;:&quot;tickets/index&quot;,&quot;props&quot;:{...},&quot;url&quot;:&quot;/tickets&quot;,&quot;version&quot;:&quot;...&quot;}"></div>
  ```
  Elemen ini memuat seluruh payload JSON awal yang disiapkan oleh controller Laravel.

#### 3. Titik Temu di Client: [`resources/js/app.tsx`](../../resources/js/app.tsx)

Di sisi frontend, React 19 mengambil alih elemen `#app` melalui inisialisasi Inertia:

```tsx
// Cuplikan dari resources/js/app.tsx
createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#0A9E8F', // Warna teal khas identitas RS Siti Fatimah
    },
});
```

`resolvePageComponent` secara dinamis mencocokkan string nama komponen yang dikirim dari controller Laravel (misal: `Inertia::render('tickets/index')`) dengan berkas fisik di `resources/js/pages/tickets/index.tsx`.

#### 4. Mengapa Portal Sifast Murni CSR dan Tanpa SSR?

Portal Sifast memilih arsitektur **Client-Side Rendered (CSR)** murni tanpa Server-Side Rendering (SSR) berbasis pertimbangan arsitektur dan operasional nyata rumah sakit:

1. **Latensi Intranet Rumah Sakit yang Sangat Rendah:** Seluruh unit kerja (IGD, Rawat Inap, Farmasi, Radiologi, IT) mengakses portal melalui jaringan intranet lokal (LAN/WLAN) atau VPN dedicated dengan latensi `< 5ms`.
2. **Tanpa Kebutuhan SEO Publik:** Portal Sifast adalah sistem internal rumah sakit yang berada di balik gerbang otentikasi (2FA Fortify & role permissions). Tidak ada mesin pencari publik (Googlebot) yang perlu mengindeks data operasional tiket, aset, atau rekam medis internal.
3. **Penyederhanaan Infrastruktur & Keandalan Operasional:** Mengaktifkan SSR mengharuskan tim IT mengelola daemon runtime Node.js tambahan di server produksi di samping PHP-FPM dan Nginx. Tanpa SSR, container deployment menjadi sangat ramping, deterministik, dan minim titik kegagalan (*single point of failure*).
4. **Efisiensi Caching Browser Karyawan:** Bundle JavaScript dan CSS dikompilasi oleh Vite dengan *content-hashing*. Setelah browser staf mengunduh bundle sekali di awal shift kerja, navigasi antar ratusan modul rumah sakit berlangsung instan karena hanya mentransfer payload JSON ringan.

---

### 1.3 Anatomi & Intuisi React Hooks (Bagi Junior)

Bagi pengembang yang terbiasa menulis kode PHP prosedural atau script jQuery linier, konsep *React Hooks* seringkali terasa membingungkan di awal.

> [!NOTE]
> **Analogi "Colokan Listrik":**
> Bayangkan komponen React Anda (`function TicketCard()`) adalah sebuah ruangan kosong tanpa listrik. Fungsi tersebut dieksekusi dari baris pertama hingga terakhir setiap kali komponen ditampilkan, lalu selesai.
> 
> **React Hooks (`use...`)** adalah colokan listrik (*plug & socket*) di dinding ruangan tersebut. Melalui kabel colokan ini, komponen Anda dapat terhubung ke fasilitas internal browser dan React engine:
> * Ingin punya memori ingatan yang tidak hilang saat ruangan dibersihkan? Tancapkan colokan `useState`.
> * Ingin menyalakan lampu saat staf memasuki ruangan dan mematikannya saat staf keluar? Tancapkan colokan `useEffect`.
> * Ingin mengirim formulir tiket tanpa reload halaman? Tancapkan colokan `useForm`.
> * Ingin mengetahui siapa staf yang sedang login saat ini? Tancapkan colokan `usePage`.

```
┌─────────────────────────────────────────────────────────────┐
│                    Komponen React (Fungsi)                  │
│                                                             │
│   useState ───🔌───> [Mesin State Reaktif React]            │
│   useEffect ──🔌───> [Siklus Hidup Browser & Cleanup]       │
│   useForm ────🔌───> [Manajemen Input & Validasi Inertia]   │
│   usePage ────🔌───> [Shared Props Global dari Laravel]     │
│                                                             │
│   Return: JSX Tampilan Deklaratif                           │
└─────────────────────────────────────────────────────────────┘
```

Berikut adalah hook-hook esensial yang digunakan setiap hari di Portal Sifast:

#### 1. `useState`: State Reaktif vs Variabel Biasa

Di jQuery, Anda menyimpan data sementara di atribut HTML (`<div data-id="10">`) atau variabel global `let counter = 0;`. Namun di React, mengubah variabel biasa **tidak akan memicu pembaruan layar**.

```tsx
import { useState } from 'react';

export function CounterTiket() {
    // ❌ SALAH: Mengubah variabel biasa tidak meng-update tampilan
    // let hitungan = 0;
    // const tambah = () => { hitungan++; };

    // ✅ BENAR: Gunakan useState
    // [nilai_saat_ini, fungsi_pengubah] = useState(nilai_awal);
    const [hitungan, setHitungan] = useState<number>(0);

    return (
        <div className="p-4 border rounded-lg">
            <p>Total Tiket Disetujui: <strong className="text-teal-600">{hitungan}</strong></p>
            <button 
                onClick={() => setHitungan(hitungan + 1)}
                className="px-3 py-1.5 bg-teal-600 text-white rounded hover:bg-teal-700"
            >
                Tambah Tiket
            </button>
        </div>
    );
}
```

Setiap kali `setHitungan` dipanggil dengan nilai baru, React secara otomatis memanggil ulang fungsi komponen dan memperbarui angka di layar secara instan.

#### 2. `useEffect`: Pengganti `$(document).ready()` dan Manajemen Siklus Hidup

Dalam jQuery, kita menulis:
```javascript
$(document).ready(function() {
    console.log('Halaman selesai dimuat!');
    const timer = setInterval(fetchUpdate, 5000);
});
```

Di React, operasi efek samping (*side effects*) seperti inisialisasi library pihak ketiga, timer interval, atau listener browser ditangani oleh `useEffect`:

```tsx
import { useEffect, useState } from 'react';

export function RealtimeClock() {
    const [waktu, setWaktu] = useState<string>(new Date().toLocaleTimeString());

    useEffect(() => {
        // 1. Eksekusi saat komponen pertama kali terpasang ke layar (Mount)
        console.log('Jam dinding dipasang ke layar');
        
        const timerId = setInterval(() => {
            setWaktu(new Date().toLocaleTimeString());
        }, 1000);

        // 2. Fungsi Pembersihan (Cleanup Function)
        // Wajib ada untuk mencegah memory leak saat user berpindah halaman!
        return () => {
            console.log('Komponen dilepas dari layar, menghentikan timer');
            clearInterval(timerId);
        };
    }, []); // <-- Dependency array kosong [] artinya hanya berjalan sekali saat mount

    return <span>Waktu Operasional: {waktu}</span>;
}
```

> [!WARNING]
> **Pentingnya Cleanup Function:**
> Jika Anda memasang event listener (`window.addEventListener`), timer (`setInterval`), atau koneksi WebSocket tanpa mengembalikan fungsi pembersih (`return () => ...`), proses tersebut akan terus berjalan di latar belakang meskipun user sudah berpindah halaman. Hal ini akan menyebabkan kebocoran memori (*memory leak*) yang memperlambat browser staf rumah sakit.

#### 3. `useForm` (Inertia.js): Manajemen Form Otomatis

Hook `useForm` dari `@inertiajs/react` adalah tulang punggung seluruh formulir transaksi di Portal Sifast (tiket ITIL, mutasi aset, penilaian mutu, dan presensi).

```tsx
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export function FormBuatTiket() {
    // Inisialisasi useForm dengan field dan nilai awal
    const { data, setData, post, processing, errors, reset } = useForm({
        judul: '',
        deskripsi: '',
        prioritas: 'medium',
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault(); // Mencegah reload halaman standar browser
        
        post('/tickets', {
            onSuccess: () => {
                reset(); // Bersihkan form jika server memvalidasi sukses
                alert('Tiket berhasil dibuat!');
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4 max-w-md">
            <div>
                <label className="block text-sm font-medium">Judul Gangguan</label>
                <input
                    type="text"
                    value={data.judul}
                    onChange={(e) => setData('judul', e.target.value)}
                    className="w-full border p-2 rounded"
                    placeholder="Contoh: Printer IGD macet"
                />
                {/* Tampilkan error validasi Laravel secara otomatis */}
                {errors.judul && (
                    <p className="text-sm text-red-600 mt-1">{errors.judul}</p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing} // Otomatis mencegah double-submit saat loading
                className="bg-teal-600 text-white px-4 py-2 rounded disabled:opacity-50"
            >
                {processing ? 'Menyimpan ke Server...' : 'Kirim Tiket'}
            </button>
        </form>
    );
}
```

Keunggulan utama `useForm`:
* Menangani state input ganda secara otomatis melalui helper `setData`.
* Melacak properti boolean `processing` untuk menonaktifkan tombol submit secara otomatis saat request sedang berjalan.
* Menerima payload error validasi HTTP 422 dari Laravel `FormRequest` dan memetakannya langsung ke objek `errors`.

#### 4. `usePage` (Inertia.js): Akses Global Shared Props

Di Blade, Anda dapat memanggil `Auth::user()` atau `session('status')` di sembarang view. Di React, data global tersebut disalurkan oleh backend via middleware `HandleInertiaRequests` dan dapat diakses dari komponen mana pun menggunakan `usePage()`:

```tsx
import { usePage } from '@inertiajs/react';

type SharedAuthProps = {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    flash: {
        success?: string;
        error?: string;
    };
};

export function UserBadgeHeader() {
    // Akses langsung data auth dan flash tanpa perlu mengoper props dari parent
    const { auth, flash } = usePage<SharedAuthProps>().props;

    return (
        <header className="flex justify-between items-center p-4 bg-white border-b">
            <div>
                <h1 className="font-semibold text-gray-800">Portal RS Siti Fatimah</h1>
                {flash.success && (
                    <span className="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">
                        {flash.success}
                    </span>
                )}
            </div>
            <div className="text-sm text-gray-600">
                Login sebagai: <strong className="text-gray-900">{auth.user.name}</strong> ({auth.user.role})
            </div>
        </header>
    );
}
```

#### 5. Custom Hooks Unggulan Portal Sifast

Selain hooks bawaan React dan Inertia, Portal Sifast menyediakan serangkaian custom hooks yang dapat langsung digunakan dalam pengembangan fitur:

1. **`useEcho` (via `@laravel/echo-react`):**
   Hook reaktif untuk berlangganan channel dan event WebSocket Reverb.
2. **`useUserPresence` ([`resources/js/hooks/use-user-presence.ts`](../../resources/js/hooks/use-user-presence.ts)):**
   Melacak daftar dokter dan staf rumah sakit yang sedang aktif secara online melalui presence channel `presence.users`.
3. **`useAppearance` ([`resources/js/hooks/use-appearance.tsx`](../../resources/js/hooks/use-appearance.tsx)):**
   Mengatur tema antarmuka (*System*, *Light*, *Dark*), menyinkronkan status cookie server, serta menambahkan class `.dark` pada tag `<html>`.
4. **`useWebSocketStatus` ([`resources/js/hooks/use-websocket-status.ts`](../../resources/js/hooks/use-websocket-status.ts)):**
   Menyediakan indikator status koneksi socket real-time (*connecting*, *connected*, *disconnected*, *unavailable*) untuk status bar sistem.

---

### 1.4 Konsep Inti React yang Wajib Dikuasai

Sebelum membangun halaman interaktif, pastikan Anda memahami 7 pilar fundamental React dan TypeScript berikut:

#### 1. Aturan Sintaks JSX / TSX
JSX adalah sintaks perluasan JavaScript yang menyerupai HTML namun berjalan sepenuhnya di bawah aturan JavaScript:
* **`className` alih-alih `class`:** Karena kata `class` adalah kata kunci bawaan JavaScript (*reserved keyword*).
* **`htmlFor` alih-alih `for`:** Digunakan pada elemen `<label htmlFor="nik">`.
* **Wajib Self-Closing:** Tag tanpa penutup wajib diakhiri dengan garis miring penutup (`<input />`, `<img />`, `<hr />`, `<br />`). Lupa menambahkan `/` akan menyebabkan error kompilasi Vite.
* **Root Tunggal atau Fragment (`<> ... </>`):** Sebuah komponen harus mengembalikan satu elemen induk. Gunakan *React Fragment* `<> ... </>` jika Anda tidak ingin menambahkan tag pembungkus `<div>` yang tidak perlu ke DOM:
  ```tsx
  return (
      <>
          <Header />
          <MainContent />
          <Footer />
      </>
  );
  ```

#### 2. Komponen & Props (Sifat Read-Only / Immutability)
Props adalah argumen yang dikirim dari komponen induk ke komponen anak. **Props bersifat Read-Only (Immutable) dan dilarang keras dimutasi secara langsung.**

```tsx
type Props = {
    nomorTiket: string;
    status: 'open' | 'closed';
};

export function BadgeTiket({ nomorTiket, status }: Props) {
    // ❌ DILARANG KERAS: Mutasi props langsung
    // status = 'closed';

    return (
        <span className={status === 'open' ? 'text-amber-600' : 'text-emerald-600'}>
            #{nomorTiket} ({status.toUpperCase()})
        </span>
    );
}
```

React mengandalkan perbandingan referensi memori (*shallow comparison*) untuk mendeteksi perubahan data. Memodifikasi props secara langsung akan merusak algoritma deteksi render React.

#### 3. Controlled vs Uncontrolled Components (Misteri Input "Membeku")
Salah satu error paling sering dialami developer yang beralih dari Blade/jQuery adalah **input teks yang membeku (tidak bisa diketik sama sekali)**:

```tsx
// ⚠️ KASUS JEBAKAN: Input tidak bisa diketik
export function BrokenInput() {
    const [judul, setJudul] = useState('Printer Rusak');

    // Karena value dikunci ke variabel judul TANPA onChange handler,
    // browser menolak ketikan user baru karena React selalu merender ulang 'Printer Rusak'
    return <input type="text" value={judul} />;
}

// ✅ SOLUSI: Selalu pasangkan value dengan onChange
export function WorkingInput() {
    const [judul, setJudul] = useState('Printer Rusak');

    return (
        <input
            type="text"
            value={judul}
            onChange={(e) => setJudul(e.target.value)}
            className="border p-1 rounded"
        />
    );
}
```

Pada *Controlled Component*, elemen formulir HTML tidak menyimpan nilainya sendiri di dalam DOM internal, melainkan mendelegasikan nilai dan pembaruannya secara penuh kepada state React.

#### 4. Lifting State Up (Koordinasi Data Antar Komponen)
Jika dua komponen bersaudara membutuhkan data yang sama (misalnya komponen `PencarianTiket` dan komponen `TabelTiket`), pindahkan state ke komponen induk (*parent*), lalu oper nilai state ke bawah sebagai props dan oper fungsi pengubahnya sebagai callback:

```tsx
// Komponen Induk: Mengelola State Utama
export function ModulTiketParent() {
    const [kataKunci, setKataKunci] = useState('');

    return (
        <div className="space-y-4">
            <SearchBar value={kataKunci} onSearchChange={setKataKunci} />
            <TicketList keyword={kataKunci} />
        </div>
    );
}

// Komponen Anak 1: Input Pencarian
function SearchBar({ value, onSearchChange }: { value: string; onSearchChange: (v: string) => void }) {
    return (
        <input 
            type="text" 
            value={value} 
            onChange={(e) => onSearchChange(e.target.value)} 
            placeholder="Cari ID tiket..." 
            className="border p-2 rounded w-full"
        />
    );
}

// Komponen Anak 2: Tabel Daftar
function TicketList({ keyword }: { keyword: string }) {
    return <div>Menampilkan hasil pencarian untuk: <strong>{keyword || 'Semua'}</strong></div>;
}
```

#### 5. Atribut Wajib `key` pada `.map()` & Virtual DOM Diffing
Saat menampilkan array data menggunakan `.map()`, React mewajibkan setiap elemen memiliki atribut `key` unik:

```tsx
type ItemAset = { id: number; namaBarang: string };

export function DaftarAset({ items }: { items: ItemAset[] }) {
    return (
        <ul>
            {/* ✅ BENAR: Menggunakan ID unik database */}
            {items.map((aset) => (
                <li key={aset.id} className="py-1 border-b">
                    {aset.namaBarang}
                </li>
            ))}
        </ul>
    );
}
```

> [!CAUTION]
> **Jangan Gunakan Index Array sebagai Key (`key={index}`):**
> Jika urutan item berubah (misalnya akibat sorting atau penghapusan item di tengah tabel), React yang menggunakan `key={index}` akan keliru mengasosiasikan state komponen anak dengan posisi baris lama. Hal ini dapat menimbulkan bug fatal seperti teks input tertukar antar baris data. Selalu gunakan identifier unik persisten dari database seperti `item.id`.

#### 6. React Context sebagai Bus Data Global Ringan
React Context memungkinkan kita mendistribusikan data ke seluruh cabang komponen di bawahnya tanpa harus mengoper props secara manual di setiap tingkatan (*prop drilling*). Contoh konkret di Portal Sifast adalah `PresenceContext` yang mendistribusikan status real-time user ke navbar, sidebar, dan jendela chat.

#### 7. TypeScript Dasar untuk Komponen React
Di Portal Sifast, seluruh kode frontend ditulis menggunakan **TypeScript** untuk menjamin keamanan tipe (*type safety*) dan mencegah runtime error saat produksi.

```tsx
// 1. Definisikan antarmuka Props secara eksplisit
type StatusTiket = 'open' | 'in_progress' | 'resolved';

type TiketItemProps = {
    id: number;
    judul: string;
    prioritas: 'low' | 'medium' | 'high';
    status: StatusTiket;
    onUpdateStatus?: (id: number, statusBaru: StatusTiket) => void; // Optional callback
};

// 2. Pasangkan tipe pada parameter komponen
export function TiketRow({ id, judul, prioritas, status, onUpdateStatus }: TiketItemProps) {
    return (
        <div className="flex items-center justify-between p-3 border-b">
            <span>#{id} - {judul}</span>
            <span className="text-xs px-2 py-1 rounded bg-slate-100">{prioritas}</span>
        </div>
    );
}
```

Dengan TypeScript, IDE Anda (VS Code / Cursor / Windsurf) akan memberikan auto-completion cerdas dan segera menandai garis merah jika Anda salah mengetikkan nama field atau mengoper tipe data yang tidak sesuai.

---

### 1.5 Under the Hood: Protokol Navigasi Inertia (Bagi Senior)

Bagi pengembang berpengalaman, memahami mekanisme kerja protokol Inertia di balik layar (*under the hood*) sangat penting untuk mengoptimalkan performa dan memecahkan kendala perutean tingkat lanjut.

#### 1. Cara Kerja Navigasi Tanpa Reload

Inertia bukanlah framework SPA konvensional seperti Vue SPA atau React SPA murni yang membutuhkan API REST endpoints terpisah (`/api/v1/...`). Sebaliknya, Inertia adalah **protokol adaptor** yang menghubungkan routing Laravel yang sudah ada dengan antarmuka React.

Berikut diagram urutan navigasi request Inertia:

```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna (Browser)
    participant Link as Inertia Client (<Link>)
    participant Middleware as Laravel HandleInertiaRequests
    participant Controller as Laravel TicketController
    participant React as React 19 Root

    User->>Link: Klik <Link href="/tickets">
    Note over Link: Cegat event default browser (e.preventDefault)
    Link->>Middleware: HTTP GET /tickets (Header: X-Inertia: true)
    
    alt Request Inertia Pertama (Full Page Load)
        Middleware->>Controller: Eksekusi index()
        Controller-->>Middleware: Inertia::render('tickets/index', $props)
        Middleware-->>User: HTML Lengkap app.blade.php + data-page JSON
        User->>React: Bootstrapping createInertiaApp()
    else Navigasi Inertia Berikutnya (AJAX / XHR)
        Middleware->>Controller: Eksekusi index()
        Controller-->>Middleware: Inertia::render('tickets/index', $props)
        Middleware-->>Link: HTTP 200 JSON Payload ({ component, props, url, version })
        Note over Link: Periksa kecocokan version aset Vite
        Link->>React: Tukar komponen halaman aktif & serahkan props baru
        Link->>User: Update URL browser via window.history.pushState
    end
```

#### 2. Anatomi Payload JSON Inertia
Saat navigasi XHR terjadi, server Laravel tidak mengirimkan kode HTML. Server mengembalikan payload JSON terstruktur seperti berikut:

```json
{
  "component": "tickets/index",
  "props": {
    "auth": {
      "user": { "id": 1, "name": "Dr. Siti Aminah", "role": "medis" }
    },
    "tickets": {
      "data": [
        { "id": 101, "title": "Permintaan Kalibrasi Tensi Digital", "status": "open" }
      ],
      "current_page": 1,
      "total": 1
    },
    "filters": { "search": "", "status": "open" },
    "flash": { "success": null, "error": null }
  },
  "url": "/tickets",
  "version": "4f5b8a9c2d1e0f3a"
}
```

Client Inertia membaca field `component`, memuat file `resources/js/pages/tickets/index.tsx`, lalu merender komponen tersebut dengan menyuntikkan isi `props`.

#### 3. Partial Reloads via Opsi `only: [...]`

Pada halaman dengan data yang besar (misalnya tabel tiket dengan 50 baris beserta metadata statistik di sidebar), melakukan reload seluruh data server hanya karena user mengganti filter kategori akan membuang bandwidth dan siklus CPU database.

Inertia menyediakan fitur **Partial Reload**:

```tsx
import { router } from '@inertiajs/react';

function refreshDaftarTiketSaja() {
    router.reload({
        only: ['tickets'], // Hanya minta prop 'tickets', abaikan statistik & user props
    });
}
```

Saat perintah ini dijalankan, browser mengirimkan dua header HTTP tambahan:
* `X-Inertia-Partial-Data: tickets`
* `X-Inertia-Partial-Component: tickets/index`

Di backend Laravel, jika Anda membungkus query menggunakan closure atau `Inertia::lazy()`, query statistik lain yang berat **tidak akan dieksekusi sama sekali oleh database**:

```php
// Backend TicketController.php
return Inertia::render('tickets/index', [
    // Dieksekusi hanya jika diminta pada partial reload
    'tickets' => Ticket::filter($request)->paginate(15),
    
    // TIDAK dieksekusi jika request menyertakan only: ['tickets']
    'statistikBerat' => fn () => LaporanStatistikService::hitungTahunan(),
]);
```

#### 4. Sinkronisasi History Browser: `pushState` vs `replaceState`

Inertia memanfaatkan HTML5 History API untuk memperbarui address bar browser tanpa memicu refresh halaman:
* **`pushState` (Default):** Menambahkan entri baru ke riwayat browser. Digunakan saat user berpindah halaman atau membuka form baru (`/tickets` $\rightarrow$ `/tickets/create`). Tombol *Back* di browser akan membawa user kembali ke `/tickets`.
* **`replaceState` (`replace: true`):** Menimpa entri URL saat ini di riwayat browser tanpa menambah tumpukan riwayat baru. Sangat krusial digunakan pada **Live Search / Filter**:
  ```tsx
  router.get('/tickets', { search: query }, {
      preserveState: true,
      replace: true, // Tidak mengotori tombol 'Back' browser
  });
  ```
  Dengan opsi `replace: true`, user tidak perlu menekan tombol *Back* sebanyak 20 kali hanya untuk membatalkan 20 karakter yang mereka ketikkan di kotak pencarian.

---

## Bab 2: Bedah Kasus Nyata Modul Tiket ITIL (Tahap 1: Analisis)

Modul Tiket ITIL (*Information Technology Infrastructure Library*) di Portal Sifast merupakan salah satu modul operasional paling aktif dan kompleks di RS Aisyiyah Siti Fatimah Tulangan. Modul ini melayani pencatatan insiden perangkat keras medis, gangguan jaringan intranet antar-instalasi, perbaikan printer kasir/farmasi, hingga permohonan pengembangan modul baru SIMRS oleh para kepala unit rumah sakit.

Pada bab ini, kita akan membedah arsitektur produksi nyata dari modul tiket ini: mulai dari bagaimana controller Laravel mengalirkan data ke komponen React tanpa endpoint REST terpisah, bagaimana fitur live search dan filter multi-kriteria berjalan instan tanpa reload, bagaimana form kompleks dengan 17 state field dan upload file dikelola dengan `useForm`, hingga bagaimana Laravel Wayfinder memberikan routing yang aman (*type-safe*).

---

### 2.1 Peta Alur Data Controller ke React (End-to-End Data Flow)

Dalam arsitektur monolitik Blade klasik, controller menyiapkan data lalu merendernya ke view HTML via `view('tickets.index', compact('tickets', ...))`. Di Inertia.js v2, pola tersebut dipertahankan hampir 100% identik dari sisi mental model controller, namun alih-alih merender file HTML di server, method `Inertia::render()` mengubah payload PHP menjadi respons JSON terstruktur yang diserahkan ke komponen React.

#### 1. Titik Tolak Backend: [`TicketController.php`](../../app/Http/Controllers/TicketController.php#L83-L94)

Perhatikan method `index()` pada controller utama modul tiket di Portal Sifast:

```php
// Cuplikan nyata dari app/Http/Controllers/TicketController.php:83-94
return Inertia::render('tickets/index', [
    'tickets' => $tickets,
    'statuses' => TicketStatus::active()->ordered()->get(),
    'priorities' => TicketPriority::active()->ordered()->get(),
    'tags' => TicketTag::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
    'filters' => $request->only(['status', 'priority', 'department', 'assignee', 'search', 'tag', 'category', 'subcategory', 'project', 'created_from', 'created_to', 'closed_from', 'closed_to', 'resolved_only', 'include_closed', 'draft']),
    'categories' => $this->getCategoriesForTicketList($user),
    'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
    'canExport' => $user->isAdmin() || $user->isStaff(),
    'canDelete' => $tickets->getCollection()->contains(fn (Ticket $ticket) => $ticket->can_delete),
]);
```

Mari kita bedah peranan dan transformasi setiap kunci array yang dikirimkan oleh backend:

| Kunci Array Backend | Tipe Data PHP / Laravel | Peranan & Transformasi di Frontend React |
| :--- | :--- | :--- |
| `'tickets'` | `LengthAwarePaginator` | Menyuplai objek paginasi utama tabel tiket (`data: Ticket[]`, `current_page`, `last_page`, `links`, `total`). Koleksi telah di-enrich dengan nama departemen pemohon dan flag otorisasi `can_delete`. |
| `'statuses'` | `Collection<TicketStatus>` | Daftar master status tiket (Open, In Progress, Pending, Resolved, Closed) yang aktif dan berurutan untuk mengisi opsi dropdown filter status. |
| `'priorities'` | `Collection<TicketPriority>` | Daftar tingkat prioritas (Low, Medium, High, Urgent) dengan metadata warna hex/badge untuk menandai tingkat kegentingan gangguan rumah sakit. |
| `'tags'` | `Collection<TicketTag>` | Daftar tag label ringan (`['id', 'name', 'slug']`) untuk memfilter insiden spesifik seperti *#printer*, *#jaringan*, *#bpjs*, atau *#farmasi*. |
| `'filters'` | `array` | Nilai parameter query string saat ini dari HTTP request. Memastikan input pencarian dan dropdown filter di UI tetap sinkron (*persisted*) dengan URL address bar. |
| `'categories'` | `Collection<TicketCategory>` | Daftar kategori beserta relasi `subcategories`, telah disaring sesuai batasan departemen staf yang sedang login (`dep_id`). |
| `'projects'` | `Collection<Project>` | Daftar inisiatif strategis rumah sakit untuk mengelompokkan tiket penugasan proyek TI. |
| `'canExport'` | `bool` | Flag otorisasi UI: hanya user dengan role Admin atau Staff IT/IPS yang diperbolehkan melihat tombol *Export CSV*. |
| `'canDelete'` | `bool` | Flag boolean dinamis: bernilai `true` jika koleksi tiket yang sedang ditampilkan di halaman aktif mengandung setidaknya satu tiket yang boleh dihapus oleh user saat ini. |

#### 2. Titik Temu Frontend: [`resources/js/pages/tickets/index.tsx`](../../resources/js/pages/tickets/index.tsx#L100-L110)

Di sisi frontend React, perhatikan bagaimana komponen `TicketsIndex` menerima data dari Laravel melalui mekanisme **Parameter Destructuring**:

```tsx
// Cuplikan dari resources/js/pages/tickets/index.tsx:46-56 (Definisi Props)
type Props = {
    tickets: PaginatedTickets;
    statuses: TicketStatus[];
    priorities: TicketPriority[];
    tags: TicketTag[];
    categories: TicketCategory[];
    filters: TicketFilters;
    projects?: ProjectOption[];
    canExport?: boolean;
    canDelete?: boolean;
};

// Cuplikan dari resources/js/pages/tickets/index.tsx:100-110 (Komponen Utama)
export default function TicketsIndex({
    tickets,
    statuses,
    priorities,
    tags = [],
    categories = [],
    filters,
    projects = [],
    canExport = false,
    canDelete = false,
}: Props) {
    // Komponen langsung siap merender tabel, dropdown, dan toolbar!
    // ...
}
```

> [!NOTE]
> **Mengapa Ini Merupakan "Game Changer" bagi Laravel Developer?**
> Perhatikan bahwa di React kita **TIDAK PERLU**:
> 1. Menulis `useEffect` manual untuk melakukan `axios.get('/api/tickets')`.
> 2. Mengelola state loading awal (`const [isLoading, setIsLoading] = useState(true)`).
> 3. Mengkhawatirkan token autentikasi Bearer API yang kadaluarsa atau CORS error.
> 
> Seluruh data yang disiapkan oleh method `index()` di `TicketController.php` disuntikkan secara instan ke props komponen `TicketsIndex`. Hubungan ini bersifat **1-to-1, deterministik, dan type-safe**.

#### 3. Diagram Alur Data End-to-End

Diagram urutan berikut menggambarkan alur siklus penuh data dari aksi pengguna hingga pembaruan layar:

```mermaid
sequenceDiagram
    autonumber
    actor Staff as Staf RS (Browser)
    participant InertiaClient as Client Inertia (React 19)
    participant NginxLaravel as Laravel Web Server
    participant Controller as TicketController::index()
    participant Database as Database PostgreSQL / MySQL

    Staff->>InertiaClient: Buka URL /tickets (atau klik Navigasi Sidebar)
    InertiaClient->>NginxLaravel: GET /tickets (Header: X-Inertia: true)
    NginxLaravel->>Controller: Eksekusi TicketController::index($request)
    
    Controller->>Database: Query tickets (filter, search, pagination)
    Controller->>Database: Query master statuses, priorities, tags, categories
    Database-->>Controller: Return Eloquent Models & Collections
    
    Controller->>Controller: Cek Policy & Otorisasi ($user->can('delete', ...))
    Controller-->>NginxLaravel: Inertia::render('tickets/index', $payloadArray)
    
    NginxLaravel-->>InertiaClient: HTTP 200 OK (JSON Props Payload)
    Note over InertiaClient: Inertia mencocokkan string 'tickets/index'<br/>ke resources/js/pages/tickets/index.tsx
    InertiaClient->>InertiaClient: Destructuring props ({ tickets, statuses, filters, ... })
    InertiaClient->>Staff: Render Virtual DOM -> Tampilan Tabel Tiket Lengkap
```

---

### 2.2 Live Search & Filter Tanpa Reload (`preserveState: true`)

Salah satu kendala terbesar antarmuka tabel data berbasis server-side filtering di Blade konvensional adalah **pengalaman pengguna (UX) yang patah-patah**:
* Saat form filter di-submit, browser melakukan refresh penuh (*white-flash*).
* Posisi scrollbar melompat kembali ke puncak halaman.
* Kursor input pencarian terlepas (*blur*), sehingga user harus mengklik ulang kotak input setiap kali ingin memperpanjang kata kunci.
* Riwayat browser (*back button*) dipenuhi puluhan entri URL yang tidak diinginkan.

Inertia.js memecahkan kendala ini secara elegan melalui method `router.get()` yang dipadukan dengan opsi `preserveState: true` dan `replace: true`.

#### 1. Analisis Mendalam Fungsi `applyFilters` di [`resources/js/pages/tickets/index.tsx`](../../resources/js/pages/tickets/index.tsx#L130-L139)

Mari kita bedah fungsi filter aktual pada baris 130-139:

```tsx
// Cuplikan dari resources/js/pages/tickets/index.tsx:130-139
const applyFilters = useCallback(
    (newFilters: Partial<TicketFilters>) => {
        router.get(
            '/tickets',
            { ...filters, ...newFilters },
            { preserveState: true, replace: true }
        );
    },
    [filters]
);
```

Fungsi di atas kemudian dipanggil oleh berbagai kontrol antarmuka:
* **Pencarian Kata Kunci (Search Input):**
  ```tsx
  // Baris 141-144
  const handleSearch = (e: React.FormEvent) => {
      e.preventDefault();
      applyFilters({ search });
  };
  ```
* **Dropdown Filter Status / Prioritas / Kategori:**
  ```tsx
  // Mengubah filter status langsung memicu request parsial ke server
  <Select 
      value={filters.status || 'all'} 
      onValueChange={(val) => applyFilters({ status: val === 'all' ? undefined : val })}
  >
      <SelectTrigger className="w-36">
          <SelectValue placeholder="Semua Status" />
      </SelectTrigger>
      {/* ... */}
  </Select>
  ```
* **Pembersihan Filter (Clear Filters):**
  ```tsx
  // Baris 146-149
  const clearFilters = () => {
      setSearch('');
      router.get('/tickets', {}, { preserveState: true, replace: true });
  };
  ```

#### 2. Mengapa Opsi `preserveState: true` Sangat Krusial?

Secara default, saat Inertia menerima respons baru dari server, Inertia menganggap halaman telah diganti dengan instance baru dan **mereset seluruh state lokal komponen**.

Dengan menyertakan `{ preserveState: true }`:
1. **Mempertahankan State Komponen Lokal:** State lokal seperti teks yang sedang diketik pada input pencarian (`const [search, setSearch] = useState(filters.search || '')`), status buka-tutup accordion filter (`showFilters`), atau tab aktif tetap bertahan utuh.
2. **Mencegah Hilangnya Fokus Kursor:** Kursor pengguna tetap berada di dalam input pencarian tanpa interupsi, sehingga staf rumah sakit dapat mengetik dengan mulus.
3. **Mencegah Lompatan Scroll (Scroll Jump):** Halaman tidak akan bergulir (*jump*) ke atas, menjaga orientasi pandangan mata pengguna saat memeriksa baris tabel di bagian bawah.

#### 3. Mengapa Opsi `replace: true` Wajib Digunakan pada Filter?

Secara default, navigasi browser menambahkan entri baru ke riwayat peramban (`window.history.pushState`). Jika opsi `replace: true` tidak disertakan:
* Jika staf mengetik kata kunci "p-r-i-n-t-e-r" atau mengganti filter status 5 kali berturut-turut, riwayat browser akan mencatat 5 entri baru.
* Ketika staf menekan tombol **Back** di peramban, mereka terpaksa mengklik tombol tersebut 5 kali berturut-turut hanya untuk keluar dari halaman daftar tiket!
* Dengan `{ replace: true }`, Inertia mengeksekusi `window.history.replaceState`. Alamat URL di address bar browser tetap terbarui (sehingga URL dapat disalin atau di-bookmark dengan filter yang presisi), namun riwayat navigasi browser tetap bersih dan bersahabat.

---

### 2.3 Bedah Form Kompleks ([`resources/js/pages/tickets/create.tsx`](../../resources/js/pages/tickets/create.tsx))

Halaman pembuatan tiket insiden (`/tickets/create`) di Portal Sifast adalah contoh formulir transaksional berskala besar. Formulir ini tidak hanya mengumpulkan teks biasa, melainkan juga mengelola relasi hierarkis dinamis, lampiran multi-berkas (*multi-file attachments*), dan integrasi inventaris sarana rumah sakit.

#### 1. Manajemen 17 State Field dengan Hook `useForm`

Perhatikan inisialisasi state pada baris 76-97 di [`resources/js/pages/tickets/create.tsx`](../../resources/js/pages/tickets/create.tsx#L76-L97):

```tsx
// Cuplikan dari resources/js/pages/tickets/create.tsx:76-97
const { data, setData, post, processing, errors, transform } = useForm({
    ticket_type_id: '',
    dep_id: '' as '' | 'IT' | 'IPS',
    ticket_category_id: '',
    ticket_subcategory_id: '',
    ticket_priority_id: '',
    title: '',
    description: '',
    related_ticket_id: '',
    asset_id: initialAssetId as number | null,
    asset_no_inventaris: initialAssetNoInventaris,
    tag_ids: [] as number[],
    requester_id: null as number | null,
    created_at: '' as string,
    project_id: (initialProjectId ?? '') as string | number,
    is_draft: false,
    plan_ideas: '',
    plan_tools: '',
    budget_estimate: '',
    budget_notes: '',
    attachments: [] as File[],
});
```

> [!TIP]
> **Pelajaran TypeScript Penting untuk Developer Laravel:**
> Perhatikan penggunaan *type assertion* pada nilai awal:
> * `attachments: [] as File[]` secara eksplisit menegaskan kepada TypeScript bahwa array ini khusus menampung objek berkas binary `File`, bukan array tanpa tipe `never[]`.
> * `dep_id: '' as '' | 'IT' | 'IPS'` membatasi nilai departemen penanggung jawab insiden hanya pada dua unit layanan sarana rumah sakit: IT (Teknologi Informasi) atau IPS (Instalasi Pemeliharaan Sarana).

#### 2. Kategori & Subkategori Dinamis via `useEffect`

Di Blade + jQuery, ketergantungan antar dropdown (misal: memilih kategori "Jaringan Medis" memicu pemuatan subkategori "Kabel LAN", "Access Point Ruang Operasi", "Switch Farmasi") biasanya ditangani dengan memanggil AJAX endpoint terpisah saat event `$('#category_id').on('change', ...)` dipicu.

Di React dan Inertia, seluruh hierarki kategori dan subkategori telah disertakan oleh controller di prop `categories`. Keterkaitan antar dropdown dikelola secara deklaratif menggunakan `useEffect` ([`resources/js/pages/tickets/create.tsx:220-239`](../../resources/js/pages/tickets/create.tsx#L220-L239)):

```tsx
// Cuplikan dari resources/js/pages/tickets/create.tsx:220-239
useEffect(() => {
    if (data.ticket_category_id) {
        // Cari objek kategori yang dipilih dari array master categories
        const category = categories.find((c) => c.id === parseInt(data.ticket_category_id));
        setSelectedCategory(category || null);

        // Validasi protektif: Jika user mengubah kategori induk,
        // periksa apakah subkategori yang sebelumnya dipilih masih terdaftar di bawah kategori baru
        if (category && data.ticket_subcategory_id) {
            const hasSubcategory = category.subcategories?.some(
                (s) => s.id === parseInt(data.ticket_subcategory_id)
            );
            // Jika subkategori lama tidak cocok dengan kategori baru, otomatis reset ke string kosong!
            if (!hasSubcategory) {
                setData('ticket_subcategory_id', '');
            }
        }
    } else {
        setSelectedCategory(null);
        setData('ticket_subcategory_id', '');
    }
}, [data.ticket_category_id, categories]);
```

Manfaat dari pendekatan deklaratif ini:
* **Nol Latensi Jaringan:** Pergantian subkategori berlangsung instan tanpa menunggu request HTTP tambahan ke server.
* **Integritas Data Terjamin:** Mencegah anomali data di database (misalnya tersimpan tiket berkategori "Perangkat Keras" namun bersubkategori "Kabel FO Terputus").

#### 3. Unggah Berkas & Transformasi Payload (`forceFormData`)

Ketika formulir menyertakan file binary (seperti foto kerusakan fisik komputer atau hasil scan memo permohonan), payload HTTP tidak boleh dikirim sebagai format JSON biasa.

Perhatikan bagaimana method `handleSubmit` pada baris 241-279 mengorkestrasi pengiriman data:

```tsx
// Cuplikan dari resources/js/pages/tickets/create.tsx:241-279
const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const hasAttachments = data.attachments.length > 0;

    // Transformasi payload sebelum diserahkan ke Laravel FormRequest
    transform((formData) => {
        const payload: Record<string, unknown> = {
            ...formData,
            ticket_subcategory_id: formData.ticket_subcategory_id === '_none' ? '' : formData.ticket_subcategory_id,
        };
        
        // Sanitasi nilai numerik & relasi
        payload.asset_id = formData.asset_id || null;
        payload.budget_estimate = String(formData.budget_estimate).trim() !== '' 
            ? parseInt(String(formData.budget_estimate), 10) 
            : null;

        // Sertakan berkas lampiran hanya jika staf memilih file
        if (hasAttachments) {
            payload.attachments = formData.attachments;
        } else {
            delete payload.attachments;
        }

        return payload;
    });

    post('/tickets', {
        preserveScroll: true,
        forceFormData: hasAttachments, // Otomatis beralih ke multipart/form-data jika ada berkas
    });
};
```

Opsi `forceFormData: hasAttachments` memastikan bahwa bila terdapat berkas pada state `attachments`, Inertia secara otomatis mengonversi seluruh payload ke objek browser `FormData` standar sehingga Laravel dapat memprosesnya menggunakan method `$request->file('attachments')`.

#### 4. Penanganan Pesan Error Server dengan Komponen `<InputError />`

Saat validasi di Laravel `TicketStoreRequest` gagal, Laravel mengembalikan HTTP 422 Unprocessable Entity beserta objek JSON berisi daftar error per field. Hook `useForm` menangkap pesan error ini secara otomatis dan memetakannya ke objek `errors`.

Di antarmuka form, kita menampilkan error menggunakan komponen reusable [`InputError`](../../resources/js/components/input-error.tsx):

```tsx
// Penggunaan di resources/js/pages/tickets/create.tsx
<div>
    <Label htmlFor="title">Judul Tiket Gangguan *</Label>
    <Input
        id="title"
        value={data.title}
        onChange={(e) => setData('title', e.target.value)}
        placeholder="Contoh: Printer cetak label di Farmasi Rawat Jalan macet"
    />
    {/* Tampilkan pesan validasi otomatis dari Laravel FormRequest */}
    <InputError message={errors.title} className="mt-1" />
</div>
```

Mari kita bedah implementasi komponen [`resources/js/components/input-error.tsx`](../../resources/js/components/input-error.tsx):

```tsx
// Cuplikan lengkap dari resources/js/components/input-error.tsx
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

function normalizeMessage(message?: string | string[]): string | undefined {
    if (message == null) return undefined;
    if (Array.isArray(message)) return message[0];
    return message;
}

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string | string[] }) {
    const text = normalizeMessage(message);
    return text ? (
        <p
            {...props}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {text}
        </p>
    ) : null;
}
```

Komponen ini menormalisasi tipe pesan: baik Laravel mengirimkan string tunggal (`"Judul tiket wajib diisi."`) maupun array string pesan error (`["Format berkas tidak valid.", "Ukuran maksimal 5MB."]`), komponen akan mengekstrak pesan pertama dan merendernya dengan warna merah yang harmonis pada tema terang maupun gelap (*dark mode*). Pola ini sepenuhnya menggantikan direktif Blade `@error('title') ... @enderror`.

---

### 2.4 Wayfinder Routing Aktual pada Modul Tiket

Dalam pengembangan aplikasi Laravel konvensional maupun arsitektur SPA lama, developer sering dihadapkan pada dua pilihan pengelolaan URL di frontend:
1. **Hardcoded String URL:**
   ```tsx
   // ❌ RAPUH: Rentan salah ketik dan tidak terdeteksi saat compile
   router.get('/tickets/' + ticket.id);
   <Link href={`/tickets/${ticket.id}/edit`}>Ubah Tiket</Link>
   ```
   Jika suatu saat tim backend mengubah pola URL di `routes/web.php` (misalnya menjadi `/itil/tickets/{id}`), tidak ada peringatan dari compiler TypeScript. Bug link putus (*broken link 404*) baru akan meledak di produksi saat diklik oleh pengguna.
2. **Ziggy Global Helper (`route(...)`):**
   ```tsx
   // ⚠️ KURANG OPTIMAL: Masih berbasis string dinamis dan memuat seluruh rute aplikasi
   <Link href={route('tickets.show', ticket.id)}>Detail</Link>
   ```
   Meskipun lebih baik daripada string mentah, Ziggy mengharuskan seluruh definisi rute aplikasi diserialisasi ke dalam payload JSON global yang besar di browser. Selain itu, parameter rute tidak memiliki autocompletion tipe data yang ketat.

#### 1. Solusi Modern di Portal Sifast: Laravel Wayfinder

Portal Sifast menggunakan **Laravel Wayfinder**, toolchain modern yang menganalisis file rute Laravel secara berkala dan menghasilkan modul fungsi helper TypeScript murni di direktori `resources/js/routes/`.

Untuk modul tiket ITIL, fungsi rute diimpor langsung dari modul Wayfinder ([`resources/js/routes/tickets/index.ts`](../../resources/js/routes/tickets/index.ts)):

```tsx
// Impor fungsi rute spesifik yang dibutuhkan saja (Tree-shakeable & Zero Overhead)
import { index, create, show } from '@/routes/tickets';
```

#### 2. Sintaks Pembuatan URL Dinamis yang Aman (Type-Safe)

Perhatikan fleksibilitas dan keamanan tipe yang ditawarkan oleh fungsi helper rute Wayfinder:

```tsx
// Contoh Penggunaan 1: Mengoper ID primitif secara langsung
const urlDetail1 = show(ticket.id).url;
// Output string: '/tickets/101'

// Contoh Penggunaan 2: Mengoper objek model Ticket secara langsung
// Wayfinder secara cerdas mendeteksi properti .id pada objek model!
const urlDetail2 = show(ticket).url;
// Output string: '/tickets/101'

// Contoh Penggunaan 3: Mengoper query parameters untuk filter
const urlFilter = index({ query: { status: 'open', priority: 2 } }).url;
// Output string: '/tickets?status=open&priority=2'

// Contoh Penggunaan 4: Pada elemen navigasi Inertia <Link>
<Link href={show(ticket).url} className="text-teal-600 hover:underline">
    #{ticket.ticket_number} - {ticket.title}
</Link>
```

#### 3. Komparasi Mendalam 3 Pendekatan Routing di Laravel

Tabel berikut membandingkan secara komprehensif keunggulan Wayfinder dibandingkan metode lawas:

| Parameter Evaluasi | 1. Hardcoded String (`'/tickets/' + id`) | 2. Ziggy (`route('tickets.show', id)`) | 3. Laravel Wayfinder di Sifast (`show(id).url`) |
| :--- | :--- | :--- | :--- |
| **Type Safety saat Compile** | ❌ Nol (String murni) | ❌ Lemah (Nama rute tetap string) | ✅ **Penuh (Fungsi TypeScript murni)** |
| **Pendeteksian Error Typo** | Muncul saat runtime di browser (404) | Muncul saat runtime di browser | **Muncul seketika di IDE (garis merah) & Build Vite gagal** |
| **IDE Autocompletion** | ❌ Tidak ada | ⚠️ Terbatas pada plugin tertentu | ✅ **Penuh (Parameter type, docs, dan return type)** |
| **Navigasi Kode (Go-to-Definition)** | ❌ Tidak bisa | ❌ Tidak bisa | ✅ **Bisa (Klik fungsi langsung membuka file rute & controller)** |
| **Beban Ukuran Bundle (Bundle Size)** | Nol | ⚠️ Besar (Membawa kamus seluruh rute aplikasi) | ✅ **Minimal (Hanya mengimpor fungsi yang dipakai / tree-shaken)** |
| **Integrasi JSDoc ke Backend** | ❌ Tidak ada | ❌ Tidak ada | ✅ **Menampilkan anotasi controller PHP dan baris kodenya** |

#### 4. Fitur JSDoc Cerdas & Lompatan Kode ke Controller PHP

Salah satu fitur paling produktif dari Wayfinder bagi pengembang adalah anotasi JSDoc yang disertakan pada setiap fungsi helper. Saat Anda mengarahkan kursor (*hover*) ke fungsi `show()` di IDE Anda (Cursor / VS Code):

```typescript
/**
* @see \App\Http\Controllers\TicketController::show
* @see app/Http/Controllers/TicketController.php:811
* @route '/tickets/{ticket}'
*/
```

Anda cukup menekan **Ctrl+Klik** (atau **Cmd+Klik** di macOS) pada referensi file di tooltip JSDoc tersebut untuk langsung membuka baris 811 pada `TicketController.php`. Jembatan ini menyatukan pengalaman pengembangan frontend dan backend menjadi satu ekosistem yang terintegrasi secara harmonis.

---

*Lanjutkan membaca ke [Bab 3: Tutorial Hands-on CRUD Step-by-Step](#bab-3-tutorial-hands-on-crud-step-by-step-studi-kasus-modul-projects) (segera hadir di Task 5).*

