# Portal Pelaporan Eksternal SIMRS - Plan 4: Custom Browser Extension Manifest V3

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Catatan Arsitektur:** Dokumen ini merupakan **Bagian 4 dari 4 (Tahap Akhir)** rencana implementasi modular yang merujuk pada spesifikasi induk: [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md).
>
> **Daftar Rencana Modular:**
> 1. **Plan 1: Fondasi Backend & Database** *(Selesai - 29 Tests PASS)*
> 2. **Plan 2: Modul Admin (Master Portal & Mapping Akses)** *(Selesai - 33 Tests PASS, Total 62 Tests PASS)*
> 3. **Plan 3: Halaman Pengguna (Portal Agregator, Deteksi Ekstensi & Self-Service Kredensial)** *(Selesai - 13 Tests PASS, Total 91 Backend Tests PASS)*
> 4. **Plan 4: Custom Browser Extension Manifest V3 (rs-extension: Background Worker, Content Bridge, Autofill Injector, Heuristic Scanner, Popup Inspector)** *(Selesai - 51 Extension Tests PASS)*

**Goal:** Membangun ekstensi browser Chromium berbasis Manifest V3 (`rs-extension/`) yang aman (*zero-persistence* in-memory queue), mampu mendeteksi keberadaannya di portal SIMRS Sifast, mengisi otomatis kredensial login pada 8 kelompok website eksternal pemerintah (Kemenkes & BKKBN) menggunakan synthetic event dispatcher & heuristic scanner, serta menyediakan popup inspector 1-klik bagi Admin IT untuk mengekstrak selector form baru.

**Architecture:** Menggunakan arsitektur Chromium Manifest V3 tanpa dependensi runtime eksternal (pure vanilla JS). Terdiri dari Service Worker (`background.js`) yang mengelola antrean kredensial RAM sementara berbasis `tabId` dengan auto-flush dan TTL 30 detik; content bridge (`content-simrs.js`) yang menginjeksi dataset DOM dan event listener custom; content engine (`content-autofill.js`) yang menangani form SPA, membypass prototype setter React/Vue (`setNativeValue`), mendeteksi CAPTCHA untuk auto-focus, dan fallback ke Runtime Heuristic Scanner jika selector DOM berubah; serta popup tool (`popup/`) untuk inspeksi form login dan generator konfigurasi JSON `form_config`. Diuji secara otomatis dengan Node.js test runner bawaan (`node:test`).

**Tech Stack:** Chrome Extensions Manifest V3, JavaScript (ES2022+), DOM API, MutationObserver, Node.js 22 Test Runner (`node:test` & `node:assert`).

**Spec:** [`docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md`](file:///Users/adijaya/MyFiles/Projects/RSAisyiahSitiFatimahTulangan/PortalSifast/docs/superpowers/specs/2026-09-09-portal-eksternal-autofill-design.md)

---

## Global Constraints

- **Zero-Persistence Guarantee:** Kredensial (username & password) TIDAK BOLEH disimpan ke dalam storage persisten browser (`chrome.storage.local`, `localStorage`, `IndexedDB`, atau cookies). Seluruh kredensial hanya disimpan di memori RAM `background.js` (Map ber-index `tabId`), memiliki TTL 30 detik, dan **wajib langsung dihapus (auto-flush)** seketika setelah diambil oleh content script target.
- **Strict Host Permissions (Manifest V3):** Host permission hanya dibatasi untuk domain SIMRS Sifast (`*://*.rsaisyiyahsitifatimah.com/*`, `http://localhost/*`, `http://127.0.0.1/*`) dan domain instansi target resmi (`https://*.kemkes.go.id/*`, `https://*.bkkbn.go.id/*`, `https://*.kemendukbangga.go.id/*`, `https://*.sitb.id/*`).
- **React/Vue Prototype Setter Bypass:** Input modern berbasis React, Vue, atau Angular tidak merespons perubahan langsung `element.value = ...`. Ekstensi wajib menyuntikkan `setNativeValue` yang memanggil prototype property descriptor setter asli (`Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(el, val)`) diikuti dispatching event `input`, `change`, dan `blur` yang membual (*bubbles: true*).
- **Runtime Heuristic Scanner Fallback:** Jika selector statis dari database SIMRS (`form_config.username_field` atau `password_field`) tidak menemukan elemen di DOM target (misalnya karena perubahan antarmuka atau tanpa ID/Name seperti SIRS Online), sistem wajib otomatis menjalankan Heuristic Scanner yang menganalisis elemen `input[type='password']`, elemen input teks/email sebelumnya, dan atribut pendukung (`placeholder`, `name`, `id`, `aria-label`).
- **Manual CAPTCHA Completion:** Ekstensi tidak pernah mencoba membypass atau mengisi CAPTCHA secara otomatis. Ketika input CAPTCHA terdeteksi di form target, ekstensi wajib memindahkan fokus kursor ke bidang CAPTCHA (`captchaElement.focus()`) dan memunculkan toast non-intrusif memberitahukan pengguna untuk memasukkan CAPTCHA secara manual.
- **Zero Framework Bloat:** Seluruh berkas ekstensi dalam `rs-extension/` adalah Vanilla JavaScript tanpa bundler berat (no Webpack/Vite runtime overhead) sehingga instan dimuat oleh browser dan mudah di-audit serta dikemas ke dalam ZIP.
- **Automated Testable Architecture:** Logika inti (store kredensial, parser selector, heuristic scanner, event bridge) diisolasi ke dalam fungsi modular yang kompatibel di browser sekaligus dapat diuji langsung menggunakan runner bawaan Node.js (`node:test`).

---

## File Structure & Responsibilities

```
rs-extension/
├── manifest.json                  # Manifest V3 configuration (permissions, host permissions, scripts, icons)
├── background.js                  # Service worker: in-memory credential queue (tabId-indexed), TTL 30s, auto-flush
├── content-simrs.js               # Content script for SIMRS domain: inject dataset DOM & CustomEvent relay bridge
├── content-autofill.js            # Content script for target portals: SPA handler, setNativeValue, heuristic scanner, toast
├── scripts/
│   └── generate-icons.js          # Generator script: generates valid PNG icons (16px, 48px, 128px) without dependencies
├── icons/
│   ├── icon-16.png                # 16x16 icon for favicon & extension list
│   ├── icon-48.png                # 48x48 icon for Chrome extensions manager
│   └── icon-128.png               # 128x128 icon for Chrome Web Store & installation
├── popup/
│   ├── popup.html                 # Extension popup UI (status, connection badge, Admin Form Inspector trigger)
│   ├── popup.css                  # Modern styling for popup UI (emerald/teal theme, clean typography)
│   └── popup.js                   # Popup logic: active tab query, form inspector execution, JSON form_config generator
├── tests/
│   ├── helpers/
│   │   └── mock-dom.js            # Mock DOM & Chrome API helpers for Node.js test environment
│   ├── manifest-validation.test.js # Test: schema Manifest V3, permissions, content script patterns, and icons
│   ├── background.test.js         # Test: in-memory queue, auto-flush, TTL 30s expiry, tab removal cleanup
│   ├── content-simrs.test.js      # Test: dataset DOM injection, SIFAST_EXTENSION_READY emit, ping/pong, launch relay
│   ├── content-autofill.test.js   # Test: setNativeValue, selector query, heuristic scanner, captcha focus, zero leakage
│   ├── popup-inspector.test.js    # Test: DOM inspection, selector extraction, form_config JSON generation
│   └── e2e-simulation.test.js     # Test: end-to-end simulated flow from SIMRS dispatch event to autofill completion
└── README.md                      # Developer & Admin guide: installation in Chrome/Edge, architecture, testing
```

---

### Task 1: Extension Scaffold, Manifest V3, Icon Generation, and Manifest Validator Test

**Files:**
- Create: `rs-extension/manifest.json`
- Create: `rs-extension/scripts/generate-icons.js`
- Create: `rs-extension/icons/icon-16.png` (dihasilkan oleh script)
- Create: `rs-extension/icons/icon-48.png` (dihasilkan oleh script)
- Create: `rs-extension/icons/icon-128.png` (dihasilkan oleh script)
- Create: `rs-extension/tests/manifest-validation.test.js`
- Modify: `package.json:11-13` (tambah script `"test:extension"`)

**Interfaces:**
- Consumes: Spesifikasi Bagian 5.1 & 5.2 terkait perizinan `manifest.json`.
- Produces: Berkas `manifest.json` yang valid untuk Chromium Manifest V3, 3 file PNG icon, serta validasi skema otomatis melalui `node --test`.

- [x] **Step 1: Write the failing manifest validation test**

Buat berkas test `rs-extension/tests/manifest-validation.test.js` yang memverifikasi bahwa `manifest.json` dan ketiga berkas icon ada, memiliki struktur Manifest V3 yang valid, permission yang tepat, serta script paths yang terdaftar.

```javascript
import { test } from 'node:test';
import assert from 'node:assert';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const EXTENSION_DIR = path.resolve(__dirname, '..');
const MANIFEST_PATH = path.join(EXTENSION_DIR, 'manifest.json');

test('Manifest V3 validation', async (t) => {
  await t.test('manifest.json exists and is valid JSON', () => {
    assert.strictEqual(fs.existsSync(MANIFEST_PATH), true, 'manifest.json must exist');
    const raw = fs.readFileSync(MANIFEST_PATH, 'utf-8');
    const manifest = JSON.parse(raw);
    assert.strictEqual(manifest.manifest_version, 3);
    assert.strictEqual(typeof manifest.name, 'string');
    assert.strictEqual(typeof manifest.version, 'string');
    assert.strictEqual(manifest.version, '1.0.0');
  });

  await t.test('permissions and host_permissions conform to specification', () => {
    const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf-8'));
    assert.deepStrictEqual(manifest.permissions.sort(), ['scripting', 'storage', 'tabs'].sort());
    
    // Check host permissions include SIMRS domains and target agencies
    const hosts = manifest.host_permissions || [];
    assert.ok(hosts.includes('*://*.rsaisyiyahsitifatimah.com/*'), 'Must include production SIMRS domain');
    assert.ok(hosts.includes('http://localhost/*'), 'Must include local development domain');
    assert.ok(hosts.includes('http://127.0.0.1/*'), 'Must include local 127.0.0.1 domain');
    assert.ok(hosts.includes('https://*.kemkes.go.id/*'), 'Must include Kemenkes domains');
    assert.ok(hosts.includes('https://*.bkkbn.go.id/*'), 'Must include BKKBN domains');
    assert.ok(hosts.includes('https://*.kemendukbangga.go.id/*'), 'Must include Kemendukbangga domains');
    assert.ok(hosts.includes('https://*.sitb.id/*'), 'Must include SITB domains');
  });

  await t.test('service worker and content scripts are registered', () => {
    const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf-8'));
    assert.strictEqual(manifest.background.service_worker, 'background.js');

    assert.ok(Array.isArray(manifest.content_scripts), 'content_scripts must be an array');
    assert.strictEqual(manifest.content_scripts.length, 2);

    const simrsScript = manifest.content_scripts.find((s) => s.js.includes('content-simrs.js'));
    assert.ok(simrsScript, 'content-simrs.js must be registered');
    assert.strictEqual(simrsScript.run_at, 'document_start');

    const targetScript = manifest.content_scripts.find((s) => s.js.includes('content-autofill.js'));
    assert.ok(targetScript, 'content-autofill.js must be registered');
    assert.strictEqual(targetScript.run_at, 'document_idle');
  });

  await t.test('icons and action popup are properly registered and files exist', () => {
    const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf-8'));
    assert.strictEqual(manifest.action.default_popup, 'popup/popup.html');

    const iconSizes = ['16', '48', '128'];
    for (const size of iconSizes) {
      const relPath = manifest.icons[size];
      assert.ok(relPath, `manifest.icons[${size}] must be defined`);
      const fullPath = path.join(EXTENSION_DIR, relPath);
      assert.strictEqual(fs.existsSync(fullPath), true, `Icon file ${relPath} must exist`);
      
      // Verify file is a non-empty PNG
      const buffer = fs.readFileSync(fullPath);
      assert.ok(buffer.length > 50, `Icon ${relPath} must not be empty`);
      // PNG magic number: 89 50 4E 47 0D 0A 1A 0A
      assert.strictEqual(buffer[0], 0x89);
      assert.strictEqual(buffer[1], 0x50);
      assert.strictEqual(buffer[2], 0x4e);
      assert.strictEqual(buffer[3], 0x47);
    }
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Jalankan test runner Node.js:
Run: `node --test rs-extension/tests/manifest-validation.test.js`
Expected: FAIL dengan error "manifest.json must exist" atau file not found.

- [x] **Step 3: Implement icon generator script and create manifest.json**

1. Buat `rs-extension/scripts/generate-icons.js`:
Script pure Node.js (menggunakan `zlib.deflateSync`) untuk membuat file binary PNG valid dengan ukuran 16x16, 48x48, dan 128x128 berlatar hijau khas SIMRS Siti Fatimah (`#059669` emerald) dengan lambang tanda centang / perisai putih.

```javascript
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const iconsDir = path.resolve(__dirname, '../icons');

if (!fs.existsSync(iconsDir)) {
  fs.mkdirSync(iconsDir, { recursive: true });
}

function crc32(buf) {
  let crc = 0xffffffff;
  for (let i = 0; i < buf.length; i++) {
    crc ^= buf[i];
    for (let j = 0; j < 8; j++) {
      crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
    }
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function createPngChunk(type, data) {
  const len = Buffer.alloc(4);
  len.writeUInt32BE(data.length, 0);

  const typeAndData = Buffer.concat([Buffer.from(type, 'ascii'), data]);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(typeAndData), 0);

  return Buffer.concat([len, typeAndData, crc]);
}

function generatePng(width, height, r = 5, g = 150, b = 105) {
  // Signature
  const signature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

  // IHDR
  const ihdrData = Buffer.alloc(13);
  ihdrData.writeUInt32BE(width, 0);
  ihdrData.writeUInt32BE(height, 4);
  ihdrData.writeUInt8(8, 8); // 8-bit depth
  ihdrData.writeUInt8(6, 9); // RGBA color
  ihdrData.writeUInt8(0, 10); // compression
  ihdrData.writeUInt8(0, 11); // filter
  ihdrData.writeUInt8(0, 12); // interlace
  const ihdrChunk = createPngChunk('IHDR', ihdrData);

  // Scanlines (RGBA)
  const rowBytes = width * 4;
  const rawData = Buffer.alloc(height * (rowBytes + 1));
  let offset = 0;

  for (let y = 0; y < height; y++) {
    rawData.writeUInt8(0, offset++); // Filter byte for row: 0 (None)
    for (let x = 0; x < width; x++) {
      // Draw a rounded shield badge with center highlight
      const cx = width / 2;
      const cy = height / 2;
      const dist = Math.hypot(x - cx, y - cy);
      const radius = width * 0.45;

      if (dist <= radius) {
        // Inner white cross/symbol inside green circle
        const isCross = (Math.abs(x - cx) <= width * 0.12 && Math.abs(y - cy) <= height * 0.3) ||
                        (Math.abs(y - cy) <= height * 0.12 && Math.abs(x - cx) <= width * 0.3);
        if (isCross) {
          rawData.writeUInt8(255, offset++); // R
          rawData.writeUInt8(255, offset++); // G
          rawData.writeUInt8(255, offset++); // B
          rawData.writeUInt8(255, offset++); // A
        } else {
          rawData.writeUInt8(r, offset++);   // R (emerald 600: #059669)
          rawData.writeUInt8(g, offset++);   // G
          rawData.writeUInt8(b, offset++);   // B
          rawData.writeUInt8(255, offset++); // A
        }
      } else {
        // Transparent outside circle
        rawData.writeUInt8(0, offset++);
        rawData.writeUInt8(0, offset++);
        rawData.writeUInt8(0, offset++);
        rawData.writeUInt8(0, offset++);
      }
    }
  }

  const idatChunk = createPngChunk('IDAT', zlib.deflateSync(rawData));
  const iendChunk = createPngChunk('IEND', Buffer.alloc(0));

  return Buffer.concat([signature, ihdrChunk, idatChunk, iendChunk]);
}

const sizes = [16, 48, 128];
for (const size of sizes) {
  const filePath = path.join(iconsDir, `icon-${size}.png`);
  fs.writeFileSync(filePath, generatePng(size, size));
  console.log(`Generated ${filePath}`);
}
```

Jalankan script generator icon:
Run: `node rs-extension/scripts/generate-icons.js`

2. Buat `rs-extension/manifest.json`:

```json
{
  "manifest_version": 3,
  "name": "SIFAST Portal Autofill Assistant",
  "version": "1.0.0",
  "description": "Ekstensi resmi SIMRS Siti Fatimah Tulangan untuk pengisian otomatis kredensial portal pelaporan eksternal.",
  "action": {
    "default_popup": "popup/popup.html",
    "default_title": "SIFAST Portal Autofill Assistant",
    "default_icon": {
      "16": "icons/icon-16.png",
      "48": "icons/icon-48.png",
      "128": "icons/icon-128.png"
    }
  },
  "icons": {
    "16": "icons/icon-16.png",
    "48": "icons/icon-48.png",
    "128": "icons/icon-128.png"
  },
  "background": {
    "service_worker": "background.js"
  },
  "permissions": [
    "tabs",
    "scripting",
    "storage"
  ],
  "host_permissions": [
    "*://*.rsaisyiyahsitifatimah.com/*",
    "http://localhost/*",
    "http://127.0.0.1/*",
    "https://*.kemkes.go.id/*",
    "https://*.bkkbn.go.id/*",
    "https://*.kemendukbangga.go.id/*",
    "https://*.sitb.id/*"
  ],
  "content_scripts": [
    {
      "matches": [
        "*://*.rsaisyiyahsitifatimah.com/*",
        "http://localhost/*",
        "http://127.0.0.1/*"
      ],
      "js": ["content-simrs.js"],
      "run_at": "document_start"
    },
    {
      "matches": [
        "https://*.kemkes.go.id/*",
        "https://*.bkkbn.go.id/*",
        "https://*.kemendukbangga.go.id/*",
        "https://*.sitb.id/*"
      ],
      "js": ["content-autofill.js"],
      "run_at": "document_idle"
    }
  ]
}
```

3. Modifikasi `package.json` untuk menambahkan script pengujian ekstensi:
```json
    "scripts": {
        "build": "vite build",
        "build:ssr": "vite build && vite build --ssr",
        "dev": "vite",
        "format": "prettier --write resources/",
        "format:check": "prettier --check resources/",
        "lint": "eslint . --fix",
        "test:extension": "node --test rs-extension/tests/*.test.js",
        "types": "tsc --noEmit"
    },
```

- [x] **Step 4: Run test to verify it passes**

Run: `npm run test:extension`
Expected: PASS untuk `rs-extension/tests/manifest-validation.test.js` (4 subtests ok).

- [x] **Step 5: Commit**

```bash
git add package.json rs-extension/manifest.json rs-extension/scripts/generate-icons.js rs-extension/icons/ rs-extension/tests/manifest-validation.test.js
git commit -m "feat(extension): initialize Manifest V3 scaffold, icons, and validation test suite"
```

---

### Task 2: Service Worker Background Manager (`background.js`) - In-Memory Credential Queue, 30s TTL, & Auto-Flush

**Files:**
- Create: `rs-extension/tests/helpers/mock-chrome.js`
- Create: `rs-extension/tests/background.test.js`
- Create: `rs-extension/background.js`

**Interfaces:**
- Consumes: Pesan `SIFAST_PORTAL_LAUNCH` dari `content-simrs.js` dengan payload `{ portal: { url, ... }, credentials: { username, password, extra_fields } }`.
- Produces: 
  - `pendingCredentials` Map in-memory keyed by `tabId`.
  - Pesan `SIFAST_GET_CREDENTIALS` responsing to target tab `sender.tab.id`, diikuti pembersihan instan `pendingCredentials.delete(tabId)`.
  - Timer TTL 30 detik auto-expire untuk mencegah kebocoran memori.
  - Listener `chrome.tabs.onRemoved` untuk membersihkan kredensial tab yang ditutup pengguna sebelum login.

- [x] **Step 1: Write the failing background service worker tests**

1. Buat `rs-extension/tests/helpers/mock-chrome.js` untuk menyediakan mock API Chrome standar (`chrome.runtime`, `chrome.tabs`) di lingkungan Node.js test:

```javascript
export function createMockChrome() {
  const messageListeners = [];
  const tabRemovedListeners = [];
  let nextTabId = 1001;

  const mockTabs = new Map();

  return {
    runtime: {
      onMessage: {
        addListener(fn) {
          messageListeners.push(fn);
        },
        _trigger(message, sender = {}) {
          return new Promise((resolve) => {
            let responded = false;
            const sendResponse = (res) => {
              responded = true;
              resolve(res);
            };
            for (const listener of messageListeners) {
              const result = listener(message, sender, sendResponse);
              if (result === true) {
                // Async response handled via sendResponse callback
                return;
              }
            }
            if (!responded) resolve(undefined);
          });
        },
      },
    },
    tabs: {
      create({ url, active }, callback) {
        const tab = { id: nextTabId++, url, active: active ?? true };
        mockTabs.set(tab.id, tab);
        if (callback) callback(tab);
        return Promise.resolve(tab);
      },
      onRemoved: {
        addListener(fn) {
          tabRemovedListeners.push(fn);
        },
        _trigger(tabId) {
          mockTabs.delete(tabId);
          for (const listener of tabRemovedListeners) {
            listener(tabId);
          }
        },
      },
      _get(tabId) {
        return mockTabs.get(tabId);
      },
    },
    _reset() {
      messageListeners.length = 0;
      tabRemovedListeners.length = 0;
      mockTabs.clear();
      nextTabId = 1001;
    },
  };
}
```

2. Buat `rs-extension/tests/background.test.js`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert';
import { createMockChrome } from './helpers/mock-chrome.js';

test('Background Service Worker Credential Queue', async (t) => {
  // Global mock environment setup
  const mockChrome = createMockChrome();
  global.chrome = mockChrome;

  // Dynamically import background script
  const bg = await import('../background.js');

  await t.test('stores credentials upon SIFAST_PORTAL_LAUNCH and opens new tab', async () => {
    mockChrome._reset();
    bg.initBackground();

    const launchPayload = {
      portal: {
        id: 1,
        name: 'SIRS Online',
        url: 'https://akun-yankes.kemkes.go.id/',
      },
      credentials: {
        type: 'shared',
        username: 'sifast_user',
        password: 'SecretPassword123!',
      },
      dispatched_at: new Date().toISOString(),
    };

    const response = await mockChrome.runtime.onMessage._trigger({
      type: 'SIFAST_PORTAL_LAUNCH',
      payload: launchPayload,
    });

    assert.strictEqual(response.success, true);
    assert.strictEqual(typeof response.tabId, 'number');
    const targetTabId = response.tabId;

    // Verify queue stores it in memory
    assert.strictEqual(bg.getQueueSize(), 1);
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);
  });

  await t.test('auto-flushes credentials immediately when fetched by target tab (zero-persistence)', async () => {
    mockChrome._reset();
    bg.initBackground();

    const launchPayload = {
      portal: { id: 2, name: 'SITB', url: 'https://jatim.sitb.id/sitb2024/app' },
      credentials: { type: 'personal', username: 'petugas_sitb', password: 'P@sswordSITB!' },
    };

    const launchRes = await mockChrome.runtime.onMessage._trigger({
      type: 'SIFAST_PORTAL_LAUNCH',
      payload: launchPayload,
    });
    const targetTabId = launchRes.tabId;

    // Simulate target content script requesting credentials
    const fetchRes = await mockChrome.runtime.onMessage._trigger(
      { type: 'SIFAST_GET_CREDENTIALS' },
      { tab: { id: targetTabId, url: 'https://jatim.sitb.id/sitb2024/app' } }
    );

    assert.strictEqual(fetchRes.success, true);
    assert.strictEqual(fetchRes.payload.credentials.username, 'petugas_sitb');
    assert.strictEqual(fetchRes.payload.credentials.password, 'P@sswordSITB!');

    // AUTO-FLUSH VERIFICATION: Queue must immediately be cleared for this tab
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
    assert.strictEqual(bg.getQueueSize(), 0);

    // Second request must fail with NO_CREDENTIALS_OR_EXPIRED
    const secondFetchRes = await mockChrome.runtime.onMessage._trigger(
      { type: 'SIFAST_GET_CREDENTIALS' },
      { tab: { id: targetTabId } }
    );
    assert.strictEqual(secondFetchRes.success, false);
    assert.strictEqual(secondFetchRes.reason, 'NO_CREDENTIALS_OR_EXPIRED');
  });

  await t.test('expires credentials automatically after TTL (30s timeout)', async () => {
    mockChrome._reset();
    bg.initBackground();

    const targetTabId = 999;
    bg.storeCredentials(targetTabId, {
      portal: { id: 3, name: 'SIRIKA', url: 'https://siga-sirika.bkkbn.go.id/login' },
      credentials: { username: 'bkkbn_user', password: 'pass' },
    }, 50 /* 50ms TTL for test */);

    assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);

    // Wait for TTL expiry
    await new Promise((resolve) => setTimeout(resolve, 80));

    assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
    assert.strictEqual(bg.getQueueSize(), 0);
  });

  await t.test('cleans up pending credentials if tab is closed before autofill (chrome.tabs.onRemoved)', async () => {
    mockChrome._reset();
    bg.initBackground();

    const launchRes = await mockChrome.runtime.onMessage._trigger({
      type: 'SIFAST_PORTAL_LAUNCH',
      payload: {
        portal: { id: 4, name: 'SIGA', url: 'https://newsiga-siga.kemendukbangga.go.id/#/login' },
        credentials: { username: 'user_siga', password: 'pwd' },
      },
    });
    const targetTabId = launchRes.tabId;
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);

    // Simulate tab closed by user
    mockChrome.tabs.onRemoved._trigger(targetTabId);

    // Verify cleanup
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
    assert.strictEqual(bg.getQueueSize(), 0);
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `node --test rs-extension/tests/background.test.js`
Expected: FAIL dengan error "Cannot find module '../background.js'".

- [x] **Step 3: Write implementation of `rs-extension/background.js`**

Buat berkas `rs-extension/background.js`:

```javascript
/**
 * SIFAST Portal Autofill Extension - Background Service Worker (Manifest V3)
 *
 * Mengelola antrean kredensial sementara dalam memori RAM (Zero-Persistence).
 * - Tidak pernah menulis data kredensial ke storage lokal / disk.
 * - Indexed by target tabId.
 * - TTL 30 detik auto-expire.
 * - Auto-flush seketika setelah diambil oleh target tab.
 */

const pendingCredentials = new Map();
const TTL_MS = 30000; // 30 seconds TTL

/**
 * Menyimpan kredensial dalam antrean memori RAM.
 */
export function storeCredentials(tabId, payload, customTtlMs = TTL_MS) {
  if (pendingCredentials.has(tabId)) {
    const existing = pendingCredentials.get(tabId);
    if (existing.timeoutId) clearTimeout(existing.timeoutId);
  }

  const timeoutId = setTimeout(() => {
    pendingCredentials.delete(tabId);
    console.log(`[SIFAST Background] Credentials expired for tabId: ${tabId}`);
  }, customTtlMs);

  pendingCredentials.set(tabId, {
    portal: payload.portal,
    credentials: payload.credentials,
    dispatchedAt: payload.dispatched_at || new Date().toISOString(),
    createdAt: Date.now(),
    timeoutId,
  });

  console.log(`[SIFAST Background] Credentials queued for tabId: ${tabId} (TTL: ${customTtlMs}ms)`);
}

/**
 * Mengambil kredensial sekaligus menghapusnya dari RAM (Auto-Flush / Zero Persistence).
 */
export function getAndFlushCredentials(tabId) {
  if (!pendingCredentials.has(tabId)) {
    return null;
  }

  const entry = pendingCredentials.get(tabId);
  if (entry.timeoutId) {
    clearTimeout(entry.timeoutId);
  }

  // Hapus seketika dari RAM
  pendingCredentials.delete(tabId);
  console.log(`[SIFAST Background] Credentials consumed and flushed for tabId: ${tabId}`);

  return {
    portal: entry.portal,
    credentials: entry.credentials,
    dispatchedAt: entry.dispatchedAt,
  };
}

/**
 * Hapus kredensial tab tertentu.
 */
export function removeCredentials(tabId) {
  if (pendingCredentials.has(tabId)) {
    const entry = pendingCredentials.get(tabId);
    if (entry.timeoutId) clearTimeout(entry.timeoutId);
    pendingCredentials.delete(tabId);
  }
}

/**
 * Cek status antrean tab tertentu.
 */
export function hasPendingCredentials(tabId) {
  return pendingCredentials.has(tabId);
}

/**
 * Mendapatkan jumlah antrean kredensial saat ini.
 */
export function getQueueSize() {
  return pendingCredentials.size;
}

/**
 * Inisialisasi event listener Chrome runtime & tabs.
 */
export function initBackground() {
  if (typeof chrome === 'undefined' || !chrome.runtime) {
    return;
  }

  // Message router
  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (!message || !message.type) return false;

    switch (message.type) {
      case 'SIFAST_PORTAL_LAUNCH': {
        const payload = message.payload;
        if (!payload || !payload.portal || !payload.portal.url) {
          sendResponse({ success: false, error: 'Invalid portal launch payload' });
          return false;
        }

        chrome.tabs.create({ url: payload.portal.url, active: true }, (tab) => {
          if (!tab || !tab.id) {
            sendResponse({ success: false, error: 'Failed to create target tab' });
            return;
          }

          storeCredentials(tab.id, payload);
          sendResponse({ success: true, tabId: tab.id });
        });

        return true; // Asynchronous sendResponse
      }

      case 'SIFAST_GET_CREDENTIALS': {
        const tabId = sender.tab ? sender.tab.id : message.tabId;
        if (!tabId) {
          sendResponse({ success: false, reason: 'NO_TAB_IDENTIFIER' });
          return false;
        }

        const data = getAndFlushCredentials(tabId);
        if (data) {
          sendResponse({ success: true, payload: data });
        } else {
          sendResponse({ success: false, reason: 'NO_CREDENTIALS_OR_EXPIRED' });
        }
        return false;
      }

      case 'SIFAST_GET_EXTENSION_STATUS': {
        sendResponse({
          success: true,
          version: '1.0.0',
          queueSize: pendingCredentials.size,
        });
        return false;
      }

      default:
        return false;
    }
  });

  // Tab closed cleanup
  if (chrome.tabs && chrome.tabs.onRemoved) {
    chrome.tabs.onRemoved.addListener((tabId) => {
      removeCredentials(tabId);
    });
  }
}

// Jalankan inisialisasi di environment browser
initBackground();
```

- [x] **Step 4: Run test to verify it passes**

Run: `node --test rs-extension/tests/background.test.js`
Expected: PASS untuk seluruh 4 subtest (stores credentials, auto-flushes, expires on TTL, cleans up on tab removed).

- [x] **Step 5: Commit**

```bash
git add rs-extension/tests/helpers/mock-chrome.js rs-extension/tests/background.test.js rs-extension/background.js
git commit -m "feat(extension): implement background service worker in-memory credential queue with 30s TTL and auto-flush"
```

---

### Task 3: SIMRS Content Bridge Script (`content-simrs.js`) - DOM Dataset Injection & CustomEvent Bridge

**Files:**
- Create: `rs-extension/tests/content-simrs.test.js`
- Create: `rs-extension/content-simrs.js`

**Interfaces:**
- Consumes: Halaman web SIMRS Sifast (`document.documentElement`), CustomEvent `SIFAST_PING_EXTENSION`, CustomEvent `SIFAST_PORTAL_LAUNCH`.
- Produces:
  - `document.documentElement.dataset.sifastExtensionInstalled = "true"`.
  - `document.documentElement.dataset.sifastExtensionVersion = "1.0.0"`.
  - Dispatch event `SIFAST_EXTENSION_READY` (detail: `{ version, installed: true }`).
  - Dispatch event `SIFAST_PONG_EXTENSION` saat menerima ping.
  - Forward event `SIFAST_PORTAL_LAUNCH` ke Service Worker via `chrome.runtime.sendMessage`.

- [x] **Step 1: Write the failing content-simrs tests**

Buat berkas `rs-extension/tests/content-simrs.test.js`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert';
import { createMockChrome } from './helpers/mock-chrome.js';

test('SIMRS Content Script Bridge (content-simrs.js)', async (t) => {
  // Mock Window, Document, CustomEvent, and EventTarget
  class MockCustomEvent {
    constructor(type, options = {}) {
      this.type = type;
      this.detail = options.detail || null;
    }
  }

  const windowListeners = new Map();
  const mockWindow = {
    addEventListener(type, fn) {
      if (!windowListeners.has(type)) windowListeners.set(type, []);
      windowListeners.get(type).push(fn);
    },
    dispatchEvent(event) {
      const listeners = windowListeners.get(event.type) || [];
      for (const fn of listeners) fn(event);
      return true;
    },
  };

  const mockDocument = {
    documentElement: {
      dataset: {},
    },
  };

  const mockChrome = createMockChrome();

  global.window = mockWindow;
  global.document = mockDocument;
  global.CustomEvent = MockCustomEvent;
  global.chrome = mockChrome;

  const simrsBridge = await import('../content-simrs.js');

  await t.test('injects dataset attributes into document.documentElement', () => {
    mockDocument.documentElement.dataset = {};
    simrsBridge.injectDomMarkers();

    assert.strictEqual(mockDocument.documentElement.dataset.sifastExtensionInstalled, 'true');
    assert.strictEqual(mockDocument.documentElement.dataset.sifastExtensionVersion, '1.0.0');
  });

  await t.test('dispatches SIFAST_EXTENSION_READY event on initialization', () => {
    let capturedEvent = null;
    windowListeners.set('SIFAST_EXTENSION_READY', [(e) => { capturedEvent = e; }]);

    simrsBridge.notifyExtensionReady();

    assert.ok(capturedEvent, 'SIFAST_EXTENSION_READY must be dispatched');
    assert.strictEqual(capturedEvent.detail.installed, true);
    assert.strictEqual(capturedEvent.detail.version, '1.0.0');
  });

  await t.test('responds to SIFAST_PING_EXTENSION with SIFAST_PONG_EXTENSION', () => {
    let pongEvent = null;
    windowListeners.set('SIFAST_PONG_EXTENSION', [(e) => { pongEvent = e; }]);

    simrsBridge.initSimrsBridge();

    // Trigger ping from SIMRS React page
    mockWindow.dispatchEvent(new MockCustomEvent('SIFAST_PING_EXTENSION'));

    assert.ok(pongEvent, 'SIFAST_PONG_EXTENSION must be dispatched on ping');
    assert.strictEqual(pongEvent.detail.installed, true);
    assert.strictEqual(pongEvent.detail.version, '1.0.0');
  });

  await t.test('relays SIFAST_PORTAL_LAUNCH event to background service worker', async () => {
    let messageSent = null;
    mockChrome.runtime.sendMessage = (msg, cb) => {
      messageSent = msg;
      if (cb) cb({ success: true, tabId: 1005 });
    };

    let ackEvent = null;
    windowListeners.set('SIFAST_PORTAL_LAUNCH_ACK', [(e) => { ackEvent = e; }]);

    simrsBridge.initSimrsBridge();

    const portalDetail = {
      portal: { id: 1, name: 'SIRS Online', url: 'https://akun-yankes.kemkes.go.id/' },
      credentials: { username: 'test', password: '123' },
    };

    mockWindow.dispatchEvent(new MockCustomEvent('SIFAST_PORTAL_LAUNCH', { detail: portalDetail }));

    assert.ok(messageSent, 'chrome.runtime.sendMessage must be called');
    assert.strictEqual(messageSent.type, 'SIFAST_PORTAL_LAUNCH');
    assert.deepStrictEqual(messageSent.payload, portalDetail);

    assert.ok(ackEvent, 'SIFAST_PORTAL_LAUNCH_ACK event must be dispatched to window');
    assert.strictEqual(ackEvent.detail.success, true);
    assert.strictEqual(ackEvent.detail.tabId, 1005);
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `node --test rs-extension/tests/content-simrs.test.js`
Expected: FAIL dengan error "Cannot find module '../content-simrs.js'".

- [x] **Step 3: Write implementation of `rs-extension/content-simrs.js`**

Buat berkas `rs-extension/content-simrs.js`:

```javascript
/**
 * SIFAST Portal Autofill Extension - SIMRS Content Bridge
 *
 * Berjalan di domain SIMRS Sifast (run_at: document_start).
 * 1. Menginjeksi dataset DOM agar React/Inertia dapat langsung mendeteksi ekstensi.
 * 2. Mengirim sinyal SIFAST_EXTENSION_READY dan merespons SIFAST_PING_EXTENSION.
 * 3. Menjembatani event SIFAST_PORTAL_LAUNCH dari halaman React ke Service Worker.
 */

const EXTENSION_VERSION = '1.0.0';

/**
 * Injeksi atribut dataset pada elemen <html>.
 */
export function injectDomMarkers() {
  if (typeof document !== 'undefined' && document.documentElement) {
    document.documentElement.dataset.sifastExtensionInstalled = 'true';
    document.documentElement.dataset.sifastExtensionVersion = EXTENSION_VERSION;
  }
}

/**
 * Memancarkan event CustomEvent SIFAST_EXTENSION_READY ke window.
 */
export function notifyExtensionReady() {
  if (typeof window !== 'undefined' && typeof CustomEvent !== 'undefined') {
    window.dispatchEvent(
      new CustomEvent('SIFAST_EXTENSION_READY', {
        detail: {
          installed: true,
          version: EXTENSION_VERSION,
        },
      })
    );
  }
}

/**
 * Inisialisasi bridge komunikasi antara SIMRS dan ekstensi.
 */
export function initSimrsBridge() {
  injectDomMarkers();
  notifyExtensionReady();

  if (typeof window === 'undefined') return;

  // Listener untuk navigasi SPA / Ping dari React
  window.addEventListener('SIFAST_PING_EXTENSION', () => {
    injectDomMarkers();
    window.dispatchEvent(
      new CustomEvent('SIFAST_PONG_EXTENSION', {
        detail: {
          installed: true,
          version: EXTENSION_VERSION,
        },
      })
    );
  });

  // Listener pemicu peluncuran portal dari tombol SIMRS
  window.addEventListener('SIFAST_PORTAL_LAUNCH', (event) => {
    if (!event || !event.detail) {
      console.warn('[SIFAST Bridge] Ignored empty SIFAST_PORTAL_LAUNCH event');
      return;
    }

    const payload = event.detail;

    if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.sendMessage) {
      chrome.runtime.sendMessage(
        {
          type: 'SIFAST_PORTAL_LAUNCH',
          payload: payload,
        },
        (response) => {
          window.dispatchEvent(
            new CustomEvent('SIFAST_PORTAL_LAUNCH_ACK', {
              detail: response || { success: false, error: 'No response from extension background' },
            })
          );
        }
      );
    } else {
      console.error('[SIFAST Bridge] chrome.runtime.sendMessage is not available');
    }
  });

  console.log(`[SIFAST Bridge] Extension v${EXTENSION_VERSION} attached to SIMRS portal.`);
}

// Jalankan otomatis di browser
if (typeof window !== 'undefined') {
  initSimrsBridge();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectDomMarkers);
  }
}
```

- [x] **Step 4: Run test to verify it passes**

Run: `node --test rs-extension/tests/content-simrs.test.js`
Expected: PASS untuk seluruh 4 subtest (injects dataset, dispatches ready, responds to ping, relays launch).

- [x] **Step 5: Commit**

```bash
git add rs-extension/tests/content-simrs.test.js rs-extension/content-simrs.js
git commit -m "feat(extension): implement SIMRS content script DOM detection and event relay bridge"
```

---

### Task 4: Target Autofill Engine (`content-autofill.js`) - Synthetic Dispatcher, Resilient Selectors, & Heuristic Scanner

**Files:**
- Create: `rs-extension/tests/content-autofill.test.js`
- Create: `rs-extension/content-autofill.js`

**Interfaces:**
- Consumes: Kredensial via `chrome.runtime.sendMessage({ type: 'SIFAST_GET_CREDENTIALS' })`.
- Produces:
  - `setNativeValue(element, value)`: Bypass prototype descriptor setter + dispatch event `input`, `change`, `blur`.
  - `queryField(selector, root)`: Resolusi CSS selector dan XPath expression.
  - `runHeuristicScanner(root)`: Pendeteksi input password visible, pendeteksi username input kandidat terdekat, dan analisis keyword.
  - `detectCaptcha(root)`: Deteksi input CAPTCHA dan auto-focus kursor.
  - `showAutofillToast(portalName)`: Tampilan toast notifikasi status di pojok kanan bawah target tab.
  - Pembersihan seketika variabel memori kredensial setelah proses pengisian selesai.

- [x] **Step 1: Write the failing content-autofill tests**

Buat berkas `rs-extension/tests/content-autofill.test.js`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert';

test('Target Portal Autofill Engine (content-autofill.js)', async (t) => {
  // Mock Element & DOM Environment
  class MockElement {
    constructor(tagName, attributes = {}) {
      this.tagName = tagName.toUpperCase();
      this.attributes = { ...attributes };
      this._value = attributes.value || '';
      this.style = {};
      this.eventsDispatched = [];
      this.focused = false;
      this.parentElement = null;
      this.children = [];
    }

    get id() { return this.attributes.id || ''; }
    get name() { return this.attributes.name || ''; }
    get type() { return this.attributes.type || 'text'; }
    get placeholder() { return this.attributes.placeholder || ''; }
    get value() { return this._value; }
    set value(val) { this._value = val; }

    getAttribute(name) { return this.attributes[name] || null; }
    setAttribute(name, val) { this.attributes[name] = val; }

    dispatchEvent(event) {
      this.eventsDispatched.push(event.type);
      return true;
    }

    focus() {
      this.focused = true;
    }

    closest(tag) {
      let cur = this.parentElement;
      while (cur) {
        if (cur.tagName.toLowerCase() === tag.toLowerCase()) return cur;
        cur = cur.parentElement;
      }
      return null;
    }
  }

  // Setup prototype descriptor setter for testing React/Vue bypass
  let nativeSetterCalled = false;
  Object.defineProperty(MockElement.prototype, 'value', {
    get() { return this._value; },
    set(val) {
      nativeSetterCalled = true;
      this._value = val;
    },
    configurable: true,
  });

  const autofillEngine = await import('../content-autofill.js');

  await t.test('setNativeValue bypasses framework wrappers and dispatches input/change/blur', () => {
    nativeSetterCalled = false;
    const input = new MockElement('input', { type: 'text' });

    // Simulate React property override on the instance
    Object.defineProperty(input, 'value', {
      value: '',
      writable: true,
      configurable: true,
    });

    autofillEngine.setNativeValue(input, 'admin_sifast');

    assert.strictEqual(input.value, 'admin_sifast');
    assert.ok(input.eventsDispatched.includes('input'), 'Must dispatch input event');
    assert.ok(input.eventsDispatched.includes('change'), 'Must dispatch change event');
    assert.ok(input.eventsDispatched.includes('blur'), 'Must dispatch blur event');
  });

  await t.test('finds form fields using static CSS selectors', () => {
    const userInput = new MockElement('input', { id: 'email', name: 'email', type: 'text' });
    const passInput = new MockElement('input', { id: 'password', name: 'password', type: 'password' });

    const root = {
      querySelector(selector) {
        if (selector === '#email' || selector === "input[name='email']") return userInput;
        if (selector === '#password' || selector === "input[name='password']") return passInput;
        return null;
      },
      querySelectorAll() { return []; },
    };

    const formConfig = {
      username_field: { selectors: ['#c', "input[name='email']", '#email'] },
      password_field: { selectors: ['#password', "input[name='password']"] },
    };

    const resolved = autofillEngine.resolveFormFields(formConfig, root);
    assert.strictEqual(resolved.usernameElement, userInput);
    assert.strictEqual(resolved.passwordElement, passInput);
  });

  await t.test('heuristic scanner finds fields when static selectors fail (e.g. SIRS Online without id/name)', () => {
    // Construct DOM structure without id and name
    const form = new MockElement('form');
    const emailInput = new MockElement('input', { type: 'email', placeholder: 'Masukkan Email Pengguna' });
    const passwordInput = new MockElement('input', { type: 'password', placeholder: 'Kata Sandi' });
    const captchaInput = new MockElement('input', { type: 'text', placeholder: 'Kode Captcha' });

    emailInput.parentElement = form;
    passwordInput.parentElement = form;
    captchaInput.parentElement = form;
    form.children = [emailInput, passwordInput, captchaInput];

    const allInputs = [emailInput, passwordInput, captchaInput];

    const root = {
      querySelector() { return null; },
      querySelectorAll(selector) {
        if (selector.includes("type='password'")) return [passwordInput];
        if (selector === 'input, select, textarea') return allInputs;
        return allInputs;
      },
    };

    const detected = autofillEngine.runHeuristicScanner(root);
    assert.strictEqual(detected.passwordElement, passwordInput);
    assert.strictEqual(detected.usernameElement, emailInput);
  });

  await t.test('detects CAPTCHA field and focuses on it', () => {
    const captchaInput = new MockElement('input', {
      type: 'text',
      id: 'captcha_code',
      placeholder: 'Ketik huruf di samping',
    });

    const root = {
      querySelectorAll(selector) {
        if (selector.includes('captcha')) return [captchaInput];
        return [captchaInput];
      },
    };

    const captcha = autofillEngine.detectCaptcha(root);
    assert.strictEqual(captcha, captchaInput);

    captcha.focus();
    assert.strictEqual(captchaInput.focused, true);
  });

  await t.test('executes autofill, fills extra fields, focuses captcha, and wipes memory variables', () => {
    const userInput = new MockElement('input', { id: 'uname', type: 'text' });
    const passInput = new MockElement('input', { id: 'pwd', type: 'password' });
    const satkerInput = new MockElement('input', { id: 'satker', type: 'text' });
    const captchaInput = new MockElement('input', { id: 'captcha', type: 'text' });

    const root = {
      querySelector(sel) {
        if (sel === '#uname') return userInput;
        if (sel === '#pwd') return passInput;
        if (sel === '#satker') return satkerInput;
        if (sel === '#captcha') return captchaInput;
        return null;
      },
      querySelectorAll(sel) {
        if (sel.includes('captcha')) return [captchaInput];
        return [userInput, passInput, satkerInput, captchaInput];
      },
    };

    const payload = {
      portal: {
        name: 'MutuFasyankes SIMAR',
        form_config: {
          username_field: { selectors: ['#uname'] },
          password_field: { selectors: ['#pwd'] },
          extra_fields: [{ key: 'kode_satker', selectors: ['#satker'] }],
        },
      },
      credentials: {
        username: 'rsasf_simar',
        password: 'PasswordSimar123!',
        extra_fields: { kode_satker: '3515002' },
      },
    };

    const result = autofillEngine.executeAutofill(payload, root, { showToast: false });

    assert.strictEqual(result.success, true);
    assert.strictEqual(userInput.value, 'rsasf_simar');
    assert.strictEqual(passInput.value, 'PasswordSimar123!');
    assert.strictEqual(satkerInput.value, '3515002');
    assert.strictEqual(captchaInput.focused, true, 'CAPTCHA input must receive focus');

    // Zero-leakage check: payload credentials must be erased
    assert.strictEqual(payload.credentials, null);
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `node --test rs-extension/tests/content-autofill.test.js`
Expected: FAIL dengan error "Cannot find module '../content-autofill.js'".

- [x] **Step 3: Write implementation of `rs-extension/content-autofill.js`**

Buat berkas `rs-extension/content-autofill.js`:

```javascript
/**
 * SIFAST Portal Autofill Extension - Target Content Script
 *
 * Mengisi kredensial login pada 8 portal eksternal:
 * 1. Menangani framework modern React/Vue via prototype setter (`setNativeValue`).
 * 2. Mencoba konfigurasi statis `form_config` terlebih dahulu.
 * 3. Fallback ke Runtime Heuristic Scanner jika selector berubah / tidak memiliki ID/Name.
 * 4. Mendeteksi CAPTCHA dan memindahkan fokus kursor ke input CAPTCHA.
 * 5. Menampilkan toast notifikasi floating yang accessible dan ramah pengguna.
 * 6. Mengosongkan variabel kredensial dari memori RAM setelah pengisian (*zero memory leak*).
 */

/**
 * Mengisi nilai input dengan membypass custom setter framework (React/Vue/Angular)
 * dan memancarkan event 'input', 'change', serta 'blur'.
 */
export function setNativeValue(element, value) {
  if (!element) return;

  const valueSetter = Object.getOwnPropertyDescriptor(element, 'value')?.set;
  const prototype = Object.getPrototypeOf(element);
  const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;

  if (prototypeValueSetter && valueSetter !== prototypeValueSetter) {
    prototypeValueSetter.call(element, value);
  } else if (valueSetter) {
    valueSetter.call(element, value);
  } else {
    element.value = value;
  }

  element.dispatchEvent(new Event('input', { bubbles: true }));
  element.dispatchEvent(new Event('change', { bubbles: true }));
  element.dispatchEvent(new Event('blur', { bubbles: true }));
}

/**
 * Mencari elemen berdasarkan CSS selector atau XPath expression.
 */
export function queryField(selector, root = document) {
  if (!selector || typeof selector !== 'string') return null;

  try {
    // Cek jika selector berupa XPath (diawali // atau ()
    if (selector.startsWith('//') || selector.startsWith('(')) {
      if (typeof document !== 'undefined' && document.evaluate && typeof XPathResult !== 'undefined') {
        const result = document.evaluate(
          selector,
          root,
          null,
          XPathResult.FIRST_ORDERED_NODE_TYPE,
          null
        );
        return result.singleNodeValue;
      }
    }

    // Standard CSS selector
    return root.querySelector(selector);
  } catch (err) {
    console.warn(`[SIFAST Autofill] Invalid selector: "${selector}"`, err);
    return null;
  }
}

/**
 * Mencari elemen berdasarkan daftar selector terurut prioritas.
 */
export function resolveElementFromList(selectors, root = document) {
  if (!Array.isArray(selectors)) return null;

  for (const selector of selectors) {
    const el = queryField(selector, root);
    if (el) return el;
  }
  return null;
}

/**
 * Mencari username dan password menggunakan konfigurasi form_config statis.
 */
export function resolveFormFields(formConfig, root = document) {
  if (!formConfig) return { usernameElement: null, passwordElement: null, extraElements: [] };

  const usernameElement = resolveElementFromList(formConfig.username_field?.selectors, root);
  const passwordElement = resolveElementFromList(formConfig.password_field?.selectors, root);

  const extraElements = [];
  if (Array.isArray(formConfig.extra_fields)) {
    for (const extra of formConfig.extra_fields) {
      const el = resolveElementFromList(extra.selectors, root);
      if (el) {
        extraElements.push({ key: extra.key, element: el });
      }
    }
  }

  return { usernameElement, passwordElement, extraElements };
}

/**
 * Runtime Heuristic Scanner: Mencari form login saat konfigurasi statis gagal atau DOM berubah.
 */
export function runHeuristicScanner(root = document) {
  let passwordElement = null;
  let usernameElement = null;

  // 1. Cari input bertipe password
  const passwordInputs = Array.from(root.querySelectorAll("input[type='password']"));
  if (passwordInputs.length > 0) {
    passwordElement = passwordInputs[0];
  }

  // 2. Cari seluruh input di dalam scope dokumen/form
  const allInputs = Array.from(root.querySelectorAll('input, select, textarea'));
  const candidateUsernames = [];

  for (const input of allInputs) {
    const type = (input.type || 'text').toLowerCase();
    if (type === 'hidden' || type === 'password' || type === 'submit' || type === 'button' || type === 'checkbox' || type === 'radio') {
      continue;
    }

    let score = 0;
    const name = (input.name || '').toLowerCase();
    const id = (input.id || '').toLowerCase();
    const placeholder = (input.placeholder || '').toLowerCase();
    const autocomplete = (input.getAttribute('autocomplete') || '').toLowerCase();

    if (type === 'email') score += 50;
    if (autocomplete.includes('username') || autocomplete.includes('email')) score += 50;

    const keywords = ['user', 'email', 'uname', 'login', 'id', 'nik', 'akun', 'nama'];
    for (const kw of keywords) {
      if (name.includes(kw)) score += 30;
      if (id.includes(kw)) score += 30;
      if (placeholder.includes(kw)) score += 25;
    }

    // Input yang berada tepat sebelum password mendapat bobot tambahan
    if (passwordElement) {
      const form = passwordElement.closest ? passwordElement.closest('form') : null;
      if (form && input.closest && input.closest('form') === form) {
        score += 20;
      }
    }

    if (score > 0) {
      candidateUsernames.push({ input, score });
    }
  }

  if (candidateUsernames.length > 0) {
    candidateUsernames.sort((a, b) => b.score - a.score);
    usernameElement = candidateUsernames[0].input;
  } else if (passwordElement) {
    // Fallback: ambil input teks pertama sebelum password
    const pwdIndex = allInputs.indexOf(passwordElement);
    for (let i = pwdIndex - 1; i >= 0; i--) {
      const prev = allInputs[i];
      const type = (prev.type || 'text').toLowerCase();
      if (type === 'text' || type === 'email') {
        usernameElement = prev;
        break;
      }
    }
  }

  return { usernameElement, passwordElement };
}

/**
 * Mendeteksi bidang input CAPTCHA jika ada pada halaman.
 */
export function detectCaptcha(root = document) {
  const inputs = Array.from(root.querySelectorAll('input'));
  for (const input of inputs) {
    const type = (input.type || 'text').toLowerCase();
    if (type === 'hidden' || type === 'password' || type === 'submit') continue;

    const id = (input.id || '').toLowerCase();
    const name = (input.name || '').toLowerCase();
    const placeholder = (input.placeholder || '').toLowerCase();
    const className = (typeof input.className === 'string' ? input.className : '').toLowerCase();

    if (
      id.includes('captcha') ||
      name.includes('captcha') ||
      placeholder.includes('captcha') ||
      placeholder.includes('kode') ||
      className.includes('captcha')
    ) {
      return input;
    }
  }
  return null;
}

/**
 * Menampilkan floating toast status pada halaman web target.
 */
export function showAutofillToast(portalName = 'Portal Pelaporan') {
  if (typeof document === 'undefined') return;

  const existingToast = document.getElementById('sifast-autofill-toast');
  if (existingToast) existingToast.remove();

  const toast = document.createElement('div');
  toast.id = 'sifast-autofill-toast';
  toast.style.cssText = `
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999999;
    max-width: 380px;
    background: #064e3b;
    color: #ffffff;
    padding: 14px 18px;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 13px;
    line-height: 1.4;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border: 1px solid #059669;
    animation: sifastSlideUp 0.3s ease-out;
  `;

  toast.innerHTML = `
    <div style="flex-shrink: 0; margin-top: 2px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <path d="m9 12 2 2 4-4"/>
      </svg>
    </div>
    <div style="flex-grow: 1;">
      <div style="font-weight: 600; font-size: 14px; margin-bottom: 2px; color: #a7f3d0;">
        SIFAST Autofill: ${portalName}
      </div>
      <div style="color: #ecfdf5;">
        Kredensial berhasil diisi otomatis. Silakan lengkapi <strong>CAPTCHA</strong> jika ada lalu klik tombol login.
      </div>
    </div>
    <button id="sifast-toast-close" style="background: none; border: none; color: #a7f3d0; cursor: pointer; padding: 0; margin-left: 4px; font-size: 16px; line-height: 1;">
      &times;
    </button>
  `;

  document.body.appendChild(toast);

  const closeBtn = document.getElementById('sifast-toast-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => toast.remove());
  }

  setTimeout(() => {
    if (toast.parentElement) toast.remove();
  }, 6000);
}

/**
 * Menjalankan proses pengisian autofill dan mengosongkan kredensial dari memori.
 */
export function executeAutofill(payload, root = document, options = { showToast: true }) {
  if (!payload || !payload.credentials) {
    return { success: false, reason: 'NO_CREDENTIALS' };
  }

  const credentials = { ...payload.credentials };
  const portal = payload.portal || {};
  const formConfig = portal.form_config || {};

  // 1. Coba konfigurasi statis
  let { usernameElement, passwordElement, extraElements } = resolveFormFields(formConfig, root);

  // 2. Fallback Heuristic Scanner jika salah satu tidak ditemukan
  if (!usernameElement || !passwordElement) {
    const heuristic = runHeuristicScanner(root);
    if (!usernameElement) usernameElement = heuristic.usernameElement;
    if (!passwordElement) passwordElement = heuristic.passwordElement;
  }

  let filledCount = 0;

  if (usernameElement && credentials.username) {
    setNativeValue(usernameElement, credentials.username);
    filledCount++;
  }

  if (passwordElement && credentials.password) {
    setNativeValue(passwordElement, credentials.password);
    filledCount++;
  }

  // 3. Isi extra fields jika ada
  if (Array.isArray(extraElements) && credentials.extra_fields) {
    for (const item of extraElements) {
      const extraVal = credentials.extra_fields[item.key];
      if (extraVal && item.element) {
        setNativeValue(item.element, String(extraVal));
        filledCount++;
      }
    }
  }

  // 4. Deteksi CAPTCHA dan fokuskan kursor
  const captchaElement = detectCaptcha(root);
  if (captchaElement && typeof captchaElement.focus === 'function') {
    captchaElement.focus();
  }

  // 5. Tampilkan toast jika berhasil mengisi setidaknya satu field
  if (filledCount > 0 && options.showToast) {
    showAutofillToast(portal.name || 'Portal Pelaporan');
  }

  // ZERO-PERSISTENCE MEMORY WIPE
  payload.credentials = null;

  return {
    success: filledCount > 0,
    filled: {
      username: !!usernameElement,
      password: !!passwordElement,
      captchaFocused: !!captchaElement,
    },
  };
}

/**
 * Inisialisasi pada browser: polling & MutationObserver untuk SPA (e.g. React/Vue).
 */
export function initAutofill() {
  if (typeof chrome === 'undefined' || !chrome.runtime || !chrome.runtime.sendMessage) {
    return;
  }

  chrome.runtime.sendMessage({ type: 'SIFAST_GET_CREDENTIALS' }, (response) => {
    if (!response || !response.success || !response.payload) {
      return;
    }

    const payload = response.payload;
    const formConfig = payload.portal?.form_config || {};
    const waitTimeout = formConfig.wait_timeout_ms || 10000;

    // Coba langsung isi
    const immediateResult = executeAutofill(payload, document, { showToast: true });
    if (immediateResult.success) return;

    // Jika belum ditemukan (halaman SPA masih rendering DOM), pasang MutationObserver
    let observer = null;
    let timeoutId = null;

    const tryFill = () => {
      const retryResult = executeAutofill(payload, document, { showToast: true });
      if (retryResult.success) {
        if (observer) observer.disconnect();
        if (timeoutId) clearTimeout(timeoutId);
      }
    };

    if (typeof MutationObserver !== 'undefined' && document.body) {
      observer = new MutationObserver(() => {
        tryFill();
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }

    // Batasi durasi pengamatan SPA agar observer tidak menggantung
    timeoutId = setTimeout(() => {
      if (observer) observer.disconnect();
    }, waitTimeout);
  });
}

// Eksekusi otomatis di target tab browser
if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutofill);
  } else {
    initAutofill();
  }
}
```

- [x] **Step 4: Run test to verify it passes**

Run: `node --test rs-extension/tests/content-autofill.test.js`
Expected: PASS untuk seluruh 5 subtest (setNativeValue, resolveFormFields, heuristic scanner, detectCaptcha, executeAutofill zero-leakage).

- [x] **Step 5: Commit**

```bash
git add rs-extension/tests/content-autofill.test.js rs-extension/content-autofill.js
git commit -m "feat(extension): implement target portal autofill engine with synthetic events and heuristic scanner"
```

---

### Task 5: Admin Form Inspector & Popup UI Tool (`popup/`)

**Files:**
- Create: `rs-extension/popup/popup.html`
- Create: `rs-extension/popup/popup.css`
- Create: `rs-extension/popup/popup.js`
- Create: `rs-extension/tests/popup-inspector.test.js`

**Interfaces:**
- Consumes: Pesan `SIFAST_INSPECT_FORM` dari popup yang dieksekusi di tab aktif.
- Produces:
  - Antarmuka popup modern dengan indikator status ekstensi, tab aktif, dan panduan.
  - Fitur Admin Form Inspector: tombol 1-klik "Scan Form Login Halaman Ini" yang mendeteksi seluruh input (type, name, id, placeholder) dan menghasilkan konfigurasi `form_config` JSON siap pakai sesuai Bagian 4.1 spesifikasi.
  - Tombol "Salin JSON Config" yang menyalin JSON ke clipboard dengan feedback visual.

- [x] **Step 1: Write the failing popup inspector unit test**

Buat berkas `rs-extension/tests/popup-inspector.test.js`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert';

test('Popup Admin Form Inspector Logic', async (t) => {
  // Mock element
  class MockInput {
    constructor(attrs = {}) {
      this.tagName = 'INPUT';
      this.attributes = { ...attrs };
    }
    get id() { return this.attributes.id || ''; }
    get name() { return this.attributes.name || ''; }
    get type() { return this.attributes.type || 'text'; }
    get placeholder() { return this.attributes.placeholder || ''; }
    getAttribute(name) { return this.attributes[name] || null; }
  }

  const popupModule = await import('../popup/popup.js');

  await t.test('inspects form elements and generates valid form_config JSON matching spec 4.1', () => {
    const inputs = [
      new MockInput({ id: 'user_login', name: 'email', type: 'email', placeholder: 'Alamat Email' }),
      new MockInput({ id: 'user_pass', name: 'password', type: 'password', placeholder: 'Kata Sandi' }),
      new MockInput({ id: 'satker_id', name: 'satker', type: 'text', placeholder: 'Kode Satker' }),
      new MockInput({ id: 'captcha_code', name: 'captcha', type: 'text', placeholder: 'Captcha' }),
    ];

    const inspection = popupModule.analyzePageInputs(inputs);

    assert.ok(inspection.usernameCandidate, 'Username candidate must be detected');
    assert.strictEqual(inspection.usernameCandidate.id, 'user_login');

    assert.ok(inspection.passwordCandidate, 'Password candidate must be detected');
    assert.strictEqual(inspection.passwordCandidate.id, 'user_pass');

    assert.ok(inspection.captchaCandidate, 'Captcha candidate must be detected');

    // Verify generated form_config structure
    const config = popupModule.generateFormConfigJson(inspection);
    assert.strictEqual(config.is_spa, true);
    assert.strictEqual(config.auto_submit, false);
    assert.ok(Array.isArray(config.username_field.selectors));
    assert.ok(config.username_field.selectors.includes('#user_login'));
    assert.ok(config.username_field.selectors.includes("input[name='email']"));
    assert.ok(config.password_field.selectors.includes('#user_pass'));
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `node --test rs-extension/tests/popup-inspector.test.js`
Expected: FAIL dengan error "Cannot find module '../popup/popup.js'".

- [x] **Step 3: Write implementation of `rs-extension/popup/`**

1. Buat `rs-extension/popup/popup.html`:

```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIFAST Portal Autofill</title>
  <link rel="stylesheet" href="popup.css">
</head>
<body>
  <header class="popup-header">
    <div class="logo-container">
      <div class="logo-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
      </div>
      <div>
        <h1 class="popup-title">SIFAST Autofill</h1>
        <span class="version-tag">v1.0.0 (Manifest V3)</span>
      </div>
    </div>
    <span class="status-indicator ready" id="global-status-badge">Aktif</span>
  </header>

  <main class="popup-main">
    <section class="card security-card">
      <div class="security-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span>Zero-Persistence Mode</span>
      </div>
      <p class="security-desc">Kredensial hanya disimpan di RAM sementara (30s) dan otomatis terhapus seketika setelah login diisi.</p>
    </section>

    <section class="card tab-card">
      <div class="card-label">Tab Browser Saat Ini</div>
      <div class="tab-url-text" id="active-tab-domain">Memeriksa domain tab...</div>
    </section>

    <!-- Admin Form Inspector Section -->
    <section class="card inspector-card">
      <div class="inspector-header">
        <div>
          <div class="card-title">🛠️ Admin Form Inspector</div>
          <div class="card-desc">Scan halaman login aktif untuk menghasilkan selector JSON form_config.</div>
        </div>
      </div>

      <button id="btn-inspect-form" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        Scan Form Login Halaman Ini
      </button>

      <div id="inspector-results" class="inspector-results hidden">
        <div class="detected-summary" id="detected-summary"></div>
        <div class="json-preview-container">
          <div class="json-preview-header">
            <span>JSON form_config (Spec 4.1)</span>
            <button id="btn-copy-json" class="btn-copy">Salin JSON</button>
          </div>
          <pre id="json-code-block"></pre>
        </div>
      </div>
    </section>
  </main>

  <footer class="popup-footer">
    <span>RS Aisyiyah Siti Fatimah Tulangan &copy; 2026</span>
  </footer>

  <script type="module" src="popup.js"></script>
</body>
</html>
```

2. Buat `rs-extension/popup/popup.css`:

```css
:root {
  --primary: #059669;
  --primary-dark: #064e3b;
  --primary-light: #ecfdf5;
  --bg-main: #f8fafc;
  --card-bg: #ffffff;
  --border-color: #e2e8f0;
  --text-main: #0f172a;
  --text-muted: #64748b;
  --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  width: 360px;
  background-color: var(--bg-main);
  color: var(--text-main);
  font-family: var(--font-family);
  font-size: 13px;
  line-height: 1.4;
}

.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-badge {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.popup-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-main);
}

.version-tag {
  font-size: 10px;
  color: var(--text-muted);
}

.status-indicator {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 9999px;
}

.status-indicator.ready {
  background: var(--primary-light);
  color: var(--primary);
  border: 1px solid #a7f3d0;
}

.popup-main {
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 12px;
}

.security-card {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.security-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #166534;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 4px;
}

.security-desc {
  font-size: 11px;
  color: #14532d;
  line-height: 1.35;
}

.card-label {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 2px;
}

.tab-url-text {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-main);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
}

.card-desc {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 2px;
  margin-bottom: 10px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  border: none;
  transition: all 0.15s ease;
}

.btn-primary {
  background: var(--primary);
  color: #ffffff;
}

.btn-primary:hover {
  background: #047857;
}

.inspector-results {
  margin-top: 12px;
  border-top: 1px solid var(--border-color);
  padding-top: 10px;
}

.inspector-results.hidden {
  display: none;
}

.detected-summary {
  font-size: 11px;
  margin-bottom: 8px;
  line-height: 1.5;
}

.json-preview-container {
  background: #0f172a;
  border-radius: 6px;
  overflow: hidden;
}

.json-preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #1e293b;
  padding: 4px 8px;
  font-size: 10px;
  color: #94a3b8;
}

.btn-copy {
  background: #334155;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 10px;
  cursor: pointer;
}

.btn-copy:hover {
  background: var(--primary);
}

#json-code-block {
  padding: 8px;
  font-family: monospace;
  font-size: 10px;
  color: #38bdf8;
  max-height: 160px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.popup-footer {
  text-align: center;
  padding: 8px 16px;
  font-size: 10px;
  color: var(--text-muted);
  border-top: 1px solid var(--border-color);
  background: var(--card-bg);
}
```

3. Buat `rs-extension/popup/popup.js`:

```javascript
/**
 * SIFAST Portal Autofill Extension - Popup & Admin Form Inspector Script
 */

/**
 * Menganalisis daftar elemen input untuk menentukan kandidat form login.
 */
export function analyzePageInputs(inputs) {
  let usernameCandidate = null;
  let passwordCandidate = null;
  let captchaCandidate = null;
  const extraCandidates = [];

  for (const el of inputs) {
    const type = (el.type || 'text').toLowerCase();
    const id = (el.id || '').toLowerCase();
    const name = (el.name || '').toLowerCase();
    const placeholder = (el.placeholder || '').toLowerCase();

    if (type === 'password' && !passwordCandidate) {
      passwordCandidate = el;
      continue;
    }

    if (id.includes('captcha') || name.includes('captcha') || placeholder.includes('captcha')) {
      captchaCandidate = el;
      continue;
    }

    if (!usernameCandidate && (type === 'email' || id.includes('user') || name.includes('user') || id.includes('email') || name.includes('email') || id.includes('login') || name.includes('login'))) {
      usernameCandidate = el;
      continue;
    }

    // Jika tipe teks biasa dan bukan captcha/username
    if (type === 'text' || type === 'number') {
      extraCandidates.push(el);
    }
  }

  // Fallback username jika belum terdeteksi tapi ada input teks sebelum password
  if (!usernameCandidate && extraCandidates.length > 0) {
    usernameCandidate = extraCandidates.shift();
  }

  return {
    usernameCandidate,
    passwordCandidate,
    captchaCandidate,
    extraCandidates,
  };
}

/**
 * Menghasilkan konfigurasi form_config format JSON spesifikasi 4.1.
 */
export function generateFormConfigJson(analysis) {
  const usernameSelectors = [];
  if (analysis.usernameCandidate) {
    const el = analysis.usernameCandidate;
    if (el.id) usernameSelectors.push(`#${el.id}`);
    if (el.name) usernameSelectors.push(`input[name='${el.name}']`);
    if (el.type === 'email') usernameSelectors.push("input[type='email']");
    if (el.placeholder) usernameSelectors.push(`input[placeholder*='${el.placeholder}' i]`);
  } else {
    usernameSelectors.push("#username", "input[name='username']", "input[type='email']");
  }

  const passwordSelectors = [];
  if (analysis.passwordCandidate) {
    const el = analysis.passwordCandidate;
    if (el.id) passwordSelectors.push(`#${el.id}`);
    if (el.name) passwordSelectors.push(`input[name='${el.name}']`);
    passwordSelectors.push("input[type='password']");
  } else {
    passwordSelectors.push("#password", "input[name='password']", "input[type='password']");
  }

  return {
    is_spa: true,
    wait_timeout_ms: 10000,
    username_field: {
      selectors: [...new Set(usernameSelectors)],
    },
    password_field: {
      selectors: [...new Set(passwordSelectors)],
    },
    extra_fields: [],
    auto_submit: false,
  };
}

/**
 * Fungsi yang diinjeksi ke tab aktif untuk mengekstrak metadata input.
 */
function scanDomInTab() {
  const allInputs = Array.from(document.querySelectorAll('input, select, textarea'));
  return allInputs.map((input) => ({
    tagName: input.tagName,
    id: input.id || '',
    name: input.name || '',
    type: input.type || 'text',
    placeholder: input.placeholder || '',
    className: input.className || '',
  }));
}

/**
 * Inisialisasi event listener UI popup.
 */
export function initPopup() {
  if (typeof chrome === 'undefined' || !chrome.tabs) return;

  const activeTabDomainEl = document.getElementById('active-tab-domain');
  const btnInspect = document.getElementById('btn-inspect-form');
  const resultsContainer = document.getElementById('inspector-results');
  const summaryEl = document.getElementById('detected-summary');
  const codeBlock = document.getElementById('json-code-block');
  const btnCopy = document.getElementById('btn-copy-json');

  // Query tab aktif
  chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
    if (!tabs || tabs.length === 0) return;
    const currentTab = tabs[0];
    try {
      const url = new URL(currentTab.url);
      if (activeTabDomainEl) {
        activeTabDomainEl.textContent = `${url.hostname} (${url.pathname})`;
      }
    } catch {
      if (activeTabDomainEl) activeTabDomainEl.textContent = currentTab.url || 'Tab Lokal';
    }

    if (btnInspect) {
      btnInspect.addEventListener('click', () => {
        btnInspect.disabled = true;
        btnInspect.textContent = 'Memindai DOM...';

        chrome.scripting.executeScript(
          {
            target: { tabId: currentTab.id },
            func: scanDomInTab,
          },
          (results) => {
            btnInspect.disabled = false;
            btnInspect.textContent = 'Scan Form Login Halaman Ini';

            if (!results || !results[0] || !results[0].result) {
              alert('Gagal memindai tab aktif.');
              return;
            }

            const inputs = results[0].result;
            const analysis = analyzePageInputs(inputs);
            const config = generateFormConfigJson(analysis);
            const configJson = JSON.stringify(config, null, 2);

            if (summaryEl) {
              summaryEl.innerHTML = `
                <div><strong>Hasil Deteksi:</strong></div>
                <div>Username: <code>${analysis.usernameCandidate ? (analysis.usernameCandidate.id || analysis.usernameCandidate.name || 'Ditemukan') : 'Tidak terdeteksi'}</code></div>
                <div>Password: <code>${analysis.passwordCandidate ? (analysis.passwordCandidate.id || analysis.passwordCandidate.name || 'Ditemukan') : 'Tidak terdeteksi'}</code></div>
                <div>CAPTCHA: <code>${analysis.captchaCandidate ? 'Terdeteksi (Manual Fokus)' : 'Tidak terdeteksi'}</code></div>
              `;
            }

            if (codeBlock) {
              codeBlock.textContent = configJson;
            }

            if (resultsContainer) {
              resultsContainer.classList.remove('hidden');
            }
          }
        );
      });
    }

    if (btnCopy && codeBlock) {
      btnCopy.addEventListener('click', () => {
        navigator.clipboard.writeText(codeBlock.textContent).then(() => {
          btnCopy.textContent = 'Tersalin!';
          setTimeout(() => {
            btnCopy.textContent = 'Salin JSON';
          }, 2000);
        });
      });
    }
  });
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', initPopup);
}
```

- [x] **Step 4: Run test to verify it passes**

Run: `node --test rs-extension/tests/popup-inspector.test.js`
Expected: PASS untuk `analyzePageInputs` dan `generateFormConfigJson` (2 tests ok).

- [x] **Step 5: Commit**

```bash
git add rs-extension/popup/ rs-extension/tests/popup-inspector.test.js
git commit -m "feat(extension): implement popup UI with Admin Form Inspector and config generator"
```

---

### Task 6: End-to-End Simulation Test & Extension Documentation (`README.md`)

**Files:**
- Create: `rs-extension/tests/e2e-simulation.test.js`
- Create: `rs-extension/README.md`

**Interfaces:**
- Consumes: Seluruh modul ekstensi (`background.js`, `content-simrs.js`, `content-autofill.js`, `popup/popup.js`).
- Produces:
  - Uji simulasi end-to-end lengkap dari klik tombol SIMRS -> antrean background -> autofill target tab -> pembersihan RAM (zero-persistence) -> verifikasi DOM input terisi.
  - Dokumentasi resmi instalasi dan cara penggunaan ekstensi bagi tim IT & staf rumah sakit (`README.md`).

- [x] **Step 1: Write the failing end-to-end simulation test**

Buat berkas `rs-extension/tests/e2e-simulation.test.js`:

```javascript
import { test } from 'node:test';
import assert from 'node:assert';
import { createMockChrome } from './helpers/mock-chrome.js';

test('End-to-End Extension Flow Simulation', async (t) => {
  const mockChrome = createMockChrome();
  global.chrome = mockChrome;

  // 1. Import modules
  const bg = await import('../background.js');
  const autofill = await import('../content-autofill.js');

  await t.test('full lifecycle: SIMRS dispatch -> background queue -> target autofill -> memory wipe', async () => {
    mockChrome._reset();
    bg.initBackground();

    // STEP 1: SIMRS triggers launch
    const simrsPayload = {
      portal: {
        id: 5,
        name: 'SITB Kemenkes',
        url: 'https://jatim.sitb.id/sitb2024/app',
        form_config: {
          username_field: { selectors: ["input[name='username']", '#user'] },
          password_field: { selectors: ["input[name='password']", '#pass'] },
        },
      },
      credentials: {
        type: 'personal',
        username: 'tb_petugas_siti_fatimah',
        password: 'SITB_SecretPass#2026',
        extra_fields: {},
      },
      dispatched_at: new Date().toISOString(),
    };

    const launchRes = await mockChrome.runtime.onMessage._trigger({
      type: 'SIFAST_PORTAL_LAUNCH',
      payload: simrsPayload,
    });

    assert.strictEqual(launchRes.success, true);
    const targetTabId = launchRes.tabId;
    assert.ok(targetTabId, 'Target tab must be assigned a numeric id');

    // Verify background holds credential temporarily
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);
    assert.strictEqual(bg.getQueueSize(), 1);

    // STEP 2: Target portal finishes loading and requests credentials
    const fetchRes = await mockChrome.runtime.onMessage._trigger(
      { type: 'SIFAST_GET_CREDENTIALS' },
      { tab: { id: targetTabId, url: 'https://jatim.sitb.id/sitb2024/app' } }
    );

    assert.strictEqual(fetchRes.success, true);
    assert.strictEqual(fetchRes.payload.portal.name, 'SITB Kemenkes');
    assert.strictEqual(fetchRes.payload.credentials.username, 'tb_petugas_siti_fatimah');

    // STEP 3: Auto-flush guarantee check in Background Worker
    assert.strictEqual(bg.hasPendingCredentials(targetTabId), false, 'Background MUST flush credentials on consumption');
    assert.strictEqual(bg.getQueueSize(), 0);

    // STEP 4: Target tab fills DOM using mock elements
    class MockElement {
      constructor(attrs = {}) {
        this.attributes = { ...attrs };
        this._val = '';
        this.events = [];
      }
      get id() { return this.attributes.id || ''; }
      get name() { return this.attributes.name || ''; }
      get type() { return this.attributes.type || 'text'; }
      get value() { return this._val; }
      set value(v) { this._val = v; }
      dispatchEvent(e) { this.events.push(e.type); return true; }
      focus() {}
    }

    const userInput = new MockElement({ name: 'username', type: 'text' });
    const passInput = new MockElement({ name: 'password', type: 'password' });

    const mockDom = {
      querySelector(sel) {
        if (sel === "input[name='username']") return userInput;
        if (sel === "input[name='password']") return passInput;
        return null;
      },
      querySelectorAll() { return [userInput, passInput]; },
    };

    const autofillResult = autofill.executeAutofill(fetchRes.payload, mockDom, { showToast: false });

    assert.strictEqual(autofillResult.success, true);
    assert.strictEqual(userInput.value, 'tb_petugas_siti_fatimah');
    assert.strictEqual(passInput.value, 'SITB_SecretPass#2026');
    assert.ok(userInput.events.includes('input'));
    assert.ok(passInput.events.includes('change'));

    // STEP 5: Content script memory wipe check
    assert.strictEqual(fetchRes.payload.credentials, null, 'Credentials reference in content payload must be erased');
  });
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `node --test rs-extension/tests/e2e-simulation.test.js`
Expected: FAIL jika ada modul yang belum terintegrasi dengan sempurna.

- [x] **Step 3: Run test and ensure it passes, then write documentation**

1. Jalankan test:
Run: `node --test rs-extension/tests/e2e-simulation.test.js`
Expected: PASS.

2. Buat berkas panduan teknis `rs-extension/README.md`:

```markdown
# SIFAST Portal Autofill Extension (Manifest V3)

Ekstensi browser resmi Rumah Sakit Aisyiyah Siti Fatimah Tulangan untuk pengisian otomatis (*autofill*) kredensial login pada portal pelaporan eksternal Kementerian Kesehatan RI dan BKKBN secara aman (*Zero-Persistence*).

---

## Fitur Utama

1. **Zero-Persistence In-Memory Queue:**
   - Kredensial tidak pernah disimpan di disk atau storage browser (`chrome.storage.local`, `localStorage`, `cookies`).
   - Kredensial dikirim satu kali (*one-time transfer*) dari SIMRS, ditampung di RAM Service Worker dengan batas waktu (TTL) 30 detik, dan **langsung dihapus seketika** (*auto-flush*) saat form login target selesai diisi.
2. **Framework-Resilient Synthetic Dispatcher:**
   - Mendukung website berbasis SPA modern (React, Vue, Angular) melalui bypass native setter descriptor (`setNativeValue`) dan dispatch event `input`, `change`, serta `blur`.
3. **Runtime Heuristic Scanner:**
   - Mampu mengisi form login secara otomatis meskipun website target tidak memiliki atribut `id` atau `name` (misalnya pada SIRS Online).
4. **Manual CAPTCHA Safety:**
   - Tidak pernah membypass CAPTCHA secara ilegal. Kursor otomatis diarahkan (*auto-focus*) ke bidang input CAPTCHA agar pengguna dapat langsung mengetik CAPTCHA.
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
3. Aktifkan **Mode Pengembang** (*Developer Mode*) di pojok kanan atas.
4. Klik tombol **Muat yang belum dibongkar** (*Load unpacked*).
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
- Simulasi alur penuh (*End-to-End*) dari peluncuran hingga pembersihan memori (`e2e-simulation.test.js`).
```

- [x] **Step 4: Run full extension test suite to verify everything passes**

Run: `npm run test:extension`
Expected: PASS untuk seluruh 6 file test:
- `manifest-validation.test.js`
- `background.test.js`
- `content-simrs.test.js`
- `content-autofill.test.js`
- `popup-inspector.test.js`
- `e2e-simulation.test.js`

- [x] **Step 5: Commit**

```bash
git add rs-extension/tests/e2e-simulation.test.js rs-extension/README.md
git commit -m "feat(extension): add end-to-end lifecycle simulation test and extension documentation"
```

---

## Verification & Test Strategy

Untuk memastikan kepatuhan penuh terhadap prinsip kualitas dan keamanan, berikut matriks verifikasi pengujian:

| Modul | Berkas Uji | Skenario Verifikasi |
| :--- | :--- | :--- |
| **Manifest & Asset** | `manifest-validation.test.js` | Memverifikasi `manifest_version: 3`, izin perizinan (`tabs`, `scripting`, `storage`), kecocokan URL host domain SIMRS dan 8 portal target, serta integritas binary file icon 16/48/128 px. |
| **Background Worker** | `background.test.js` | 1. Ingestion payload dari SIMRS.<br>2. Isolasi data per-tab.<br>3. Pembersihan seketika (*auto-flush*) saat data diambil target tab.<br>4. Expiry timer 30 detik (*TTL*).<br>5. Pembersihan tab saat event `chrome.tabs.onRemoved`. |
| **Content SIMRS** | `content-simrs.test.js` | 1. Injeksi `data-sifast-extension-installed="true"` dan version pada `<html>`.<br>2. Pancaran event `SIFAST_EXTENSION_READY`.<br>3. Handshake `SIFAST_PING_EXTENSION` -> `SIFAST_PONG_EXTENSION`.<br>4. Relay event `SIFAST_PORTAL_LAUNCH` ke background worker. |
| **Content Autofill** | `content-autofill.test.js` | 1. Bypass React/Vue prototype setter (`setNativeValue`).<br>2. Resolusi CSS & XPath selector statis.<br>3. Heuristic Scanner saat selector statis tidak ada/berubah.<br>4. Deteksi CAPTCHA & auto-focus.<br>5. Penghapusan referensi variabel kredensial dari memori. |
| **Admin Inspector** | `popup-inspector.test.js` | 1. Parsing elemen input DOM target.<br>2. Identifikasi kandidat username/password.<br>3. Pembuatan JSON `form_config` valid sesuai standar spesifikasi Bagian 4.1. |
| **End-to-End Flow** | `e2e-simulation.test.js` | Simulasi terintegrasi dari pemicuan event peluncuran portal di SIMRS hingga formulir login terisi dan kursor fokus di CAPTCHA, membuktikan *zero-persistence* di seluruh siklus. |

Jalankan seluruh rangkaian pengujian kapan saja dengan perintah:
```bash
npm run test:extension
```
