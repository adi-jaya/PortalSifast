/**
 * SIFAST Portal Autofill Extension - Target Content Script
 *
 * Mengisi kredensial login pada portal eksternal:
 * 1. Menangani framework modern React/Vue via prototype setter (setNativeValue).
 * 2. Mencoba konfigurasi statis form_config terlebih dahulu.
 * 3. Fallback ke Runtime Heuristic Scanner jika selector berubah / tidak memiliki ID/Name.
 * 4. Mendeteksi CAPTCHA dan memindahkan fokus kursor ke input CAPTCHA.
 * 5. Menampilkan toast notifikasi floating yang accessible dan ramah pengguna.
 * 6. Mengosongkan variabel kredensial dari memori RAM setelah pengisian (zero memory leak).
 *
 * Catatan: Dieksekusi sebagai Classic Script di browser Chrome MV3 (bukan ES Module).
 */

/* global chrome, globalThis */

/**
 * Mengisi nilai input dengan membypass custom setter framework (React/Vue/Angular)
 * dan memancarkan event 'input', 'change', serta 'blur'.
 */
function setNativeValue(element, value) {
    if (!element) return;

    const valueSetter = Object.getOwnPropertyDescriptor(element, 'value')?.set;
    const prototype = Object.getPrototypeOf(element);
    const prototypeValueSetter = Object.getOwnPropertyDescriptor(
        prototype,
        'value',
    )?.set;

    if (prototypeValueSetter && valueSetter !== prototypeValueSetter) {
        prototypeValueSetter.call(element, value);
    } else if (valueSetter) {
        valueSetter.call(element, value);
    } else {
        element.value = value;
    }

    // Pastikan property value pada elemen juga ter-update jika ter-shadowing
    if (element.value !== value) {
        element.value = value;
    }

    const createEvent = (type) => {
        return typeof Event !== 'undefined'
            ? new Event(type, { bubbles: true })
            : { type, bubbles: true };
    };

    element.dispatchEvent(createEvent('input'));
    element.dispatchEvent(createEvent('change'));
    element.dispatchEvent(createEvent('blur'));
}

/**
 * Mencari elemen berdasarkan CSS selector atau XPath expression.
 */
function queryField(
    selector,
    root = typeof document !== 'undefined' ? document : null,
) {
    if (!selector || typeof selector !== 'string' || !root) return null;

    try {
        // Cek jika selector berupa XPath (diawali // atau ()
        if (selector.startsWith('//') || selector.startsWith('(')) {
            if (
                typeof document !== 'undefined' &&
                document.evaluate &&
                typeof XPathResult !== 'undefined'
            ) {
                const result = document.evaluate(
                    selector,
                    root,
                    null,
                    XPathResult.FIRST_ORDERED_NODE_TYPE,
                    null,
                );
                return result ? result.singleNodeValue : null;
            }
        }

        // Standard CSS selector
        if (typeof root.querySelector === 'function') {
            return root.querySelector(selector);
        }
        return null;
    } catch (err) {
        console.warn(`[SIFAST Autofill] Invalid selector: "${selector}"`, err);
        return null;
    }
}

/**
 * Mencari elemen berdasarkan daftar selector terurut prioritas.
 */
function resolveElementFromList(
    selectors,
    root = typeof document !== 'undefined' ? document : null,
) {
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
function resolveFormFields(
    formConfig,
    root = typeof document !== 'undefined' ? document : null,
) {
    if (!formConfig)
        return {
            usernameElement: null,
            passwordElement: null,
            extraElements: [],
        };

    const usernameElement = resolveElementFromList(
        formConfig.username_field?.selectors,
        root,
    );
    const passwordElement = resolveElementFromList(
        formConfig.password_field?.selectors,
        root,
    );

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
function runHeuristicScanner(
    root = typeof document !== 'undefined' ? document : null,
) {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return { usernameElement: null, passwordElement: null };
    }

    let passwordElement = null;
    let usernameElement = null;

    // 1. Cari input bertipe password
    const passwordInputs = Array.from(
        root.querySelectorAll("input[type='password']"),
    );
    const visiblePasswords = passwordInputs.filter((input) => {
        if (input.disabled) return false;
        if (
            typeof input.getAttribute === 'function' &&
            input.getAttribute('type') === 'hidden'
        ) {
            return false;
        }
        if (
            input.style &&
            (input.style.display === 'none' ||
                input.style.visibility === 'hidden')
        ) {
            return false;
        }
        return true;
    });
    passwordElement = visiblePasswords[0] || passwordInputs[0] || null;

    // 2. Cari seluruh input di dalam scope dokumen/form
    const allInputs = Array.from(
        root.querySelectorAll('input, select, textarea'),
    );
    const candidateUsernames = [];

    for (const input of allInputs) {
        const type = (input.type || 'text').toLowerCase();
        if (
            type === 'hidden' ||
            type === 'password' ||
            type === 'submit' ||
            type === 'button' ||
            type === 'checkbox' ||
            type === 'radio'
        ) {
            continue;
        }

        let score = 0;
        const name = (input.name || '').toLowerCase();
        const id = (input.id || '').toLowerCase();
        const placeholder = (input.placeholder || '').toLowerCase();
        const autocomplete = (
            typeof input.getAttribute === 'function'
                ? input.getAttribute('autocomplete') || ''
                : ''
        ).toLowerCase();

        if (type === 'email') score += 50;
        if (autocomplete.includes('username') || autocomplete.includes('email'))
            score += 50;

        const keywords = [
            'user',
            'email',
            'uname',
            'login',
            'id',
            'nik',
            'akun',
            'nama',
        ];
        for (const kw of keywords) {
            if (name.includes(kw)) score += 30;
            if (id.includes(kw)) score += 30;
            if (placeholder.includes(kw)) score += 25;
        }

        // Input yang berada tepat sebelum password mendapat bobot tambahan
        if (passwordElement) {
            const form =
                typeof passwordElement.closest === 'function'
                    ? passwordElement.closest('form')
                    : null;
            if (
                form &&
                typeof input.closest === 'function' &&
                input.closest('form') === form
            ) {
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
 * Sanitasi string teks untuk mencegah HTML injection pada tampilan UI toast.
 */
function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Mendeteksi bidang input CAPTCHA jika ada pada halaman.
 */
function detectCaptcha(
    root = typeof document !== 'undefined' ? document : null,
) {
    if (!root || typeof root.querySelectorAll !== 'function') return null;

    const captchaCodeRegex = /kode\s*(keamanan|verifikasi|captcha|acak|unik)/i;
    const inputs = Array.from(root.querySelectorAll('input'));

    for (const input of inputs) {
        const type = (input.type || 'text').toLowerCase();
        if (type === 'hidden' || type === 'password' || type === 'submit')
            continue;

        const id = (input.id || '').toLowerCase();
        const name = (input.name || '').toLowerCase();
        const placeholder = (input.placeholder || '').toLowerCase();
        const className = (
            typeof input.className === 'string' ? input.className : ''
        ).toLowerCase();

        if (
            id.includes('captcha') ||
            name.includes('captcha') ||
            placeholder.includes('captcha') ||
            className.includes('captcha') ||
            captchaCodeRegex.test(placeholder) ||
            captchaCodeRegex.test(name) ||
            captchaCodeRegex.test(id)
        ) {
            return input;
        }
    }
    return null;
}

/**
 * Menampilkan floating toast status pada halaman web target.
 */
function showAutofillToast(portalName = 'Portal Pelaporan') {
    if (
        typeof document === 'undefined' ||
        !document.body ||
        typeof document.createElement !== 'function'
    )
        return;

    const existingToast =
        typeof document.getElementById === 'function'
            ? document.getElementById('sifast-autofill-toast')
            : null;
    if (existingToast && typeof existingToast.remove === 'function')
        existingToast.remove();

    const safePortalName = escapeHtml(portalName);

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
      <div id="sifast-toast-title" style="font-weight: 600; font-size: 14px; margin-bottom: 2px; color: #a7f3d0;">
        SIFAST Autofill: ${safePortalName}
      </div>
      <div style="color: #ecfdf5;">
        Kredensial berhasil diisi otomatis. Silakan lengkapi <strong>CAPTCHA</strong> jika ada lalu klik tombol login.
      </div>
    </div>
    <button id="sifast-toast-close" style="background: none; border: none; color: #a7f3d0; cursor: pointer; padding: 0; margin-left: 4px; font-size: 16px; line-height: 1;">
      &times;
    </button>
  `;

    const titleEl =
        typeof toast.querySelector === 'function'
            ? toast.querySelector('#sifast-toast-title')
            : null;
    if (titleEl) {
        titleEl.textContent = `SIFAST Autofill: ${portalName}`;
    }

    document.body.appendChild(toast);

    const closeBtn =
        typeof document.getElementById === 'function'
            ? document.getElementById('sifast-toast-close')
            : null;
    if (closeBtn && typeof closeBtn.addEventListener === 'function') {
        closeBtn.addEventListener('click', () => {
            if (typeof toast.remove === 'function') toast.remove();
        });
    }

    setTimeout(() => {
        if (toast.parentElement && typeof toast.remove === 'function')
            toast.remove();
    }, 6000);
}

/**
 * Menjalankan proses pengisian autofill dan mengosongkan kredensial dari memori.
 */
function executeAutofill(
    payload,
    root = typeof document !== 'undefined' ? document : null,
    options = { showToast: true },
) {
    if (!payload || !payload.credentials) {
        return { success: false, reason: 'NO_CREDENTIALS' };
    }

    const credentials = { ...payload.credentials };
    const portal = payload.portal || {};
    const formConfig = portal.form_config || {};

    // 1. Coba konfigurasi statis
    let { usernameElement, passwordElement, extraElements } = resolveFormFields(
        formConfig,
        root,
    );

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
            if (
                extraVal !== undefined &&
                extraVal !== null &&
                String(extraVal).trim() !== '' &&
                item.element
            ) {
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
    if (filledCount > 0 && options && options.showToast !== false) {
        showAutofillToast(portal.name || 'Portal Pelaporan');
    }

    // ZERO-PERSISTENCE MEMORY WIPE: Hanya bersihkan kredensial jika proses autofill berhasil mengisi form.
    // Jika elemen form belum dimuat (SPA), simpan kredensial untuk percobaan berikutnya pada MutationObserver.
    if (filledCount > 0) {
        payload.credentials = null;
    }

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
function initAutofill() {
    if (
        typeof chrome === 'undefined' ||
        !chrome.runtime ||
        typeof chrome.runtime.sendMessage !== 'function'
    ) {
        return;
    }

    chrome.runtime.sendMessage(
        { type: 'SIFAST_GET_CREDENTIALS' },
        (response) => {
            if (!response || !response.success || !response.payload) {
                return;
            }

            const payload = response.payload;
            const formConfig = payload.portal?.form_config || {};
            const waitTimeout = formConfig.wait_timeout_ms || 10000;

            const rootDoc = typeof document !== 'undefined' ? document : null;
            if (!rootDoc) return;

            // Coba langsung isi
            const immediateResult = executeAutofill(payload, rootDoc, {
                showToast: true,
            });
            if (immediateResult.success) return;

            // Jika belum ditemukan (halaman SPA masih rendering DOM), pasang MutationObserver
            let observer = null;
            let timeoutId = null;
            let fillScheduled = false;

            const tryFill = () => {
                const retryResult = executeAutofill(payload, rootDoc, {
                    showToast: true,
                });
                if (retryResult.success) {
                    if (observer) observer.disconnect();
                    if (timeoutId) clearTimeout(timeoutId);
                }
            };

            const scheduleFill = () => {
                if (fillScheduled) return;
                fillScheduled = true;
                setTimeout(() => {
                    fillScheduled = false;
                    tryFill();
                }, 100);
            };

            if (typeof MutationObserver !== 'undefined' && rootDoc.body) {
                observer = new MutationObserver(() => {
                    scheduleFill();
                });
                observer.observe(rootDoc.body, {
                    childList: true,
                    subtree: true,
                });
            }

            // Batasi durasi pengamatan SPA agar observer tidak menggantung dan bersihkan kredensial jika gagal
            timeoutId = setTimeout(() => {
                if (observer) observer.disconnect();
                if (payload) payload.credentials = null;
            }, waitTimeout);
        },
    );
}

// Eksekusi otomatis di target tab browser
if (typeof window !== 'undefined') {
    if (
        typeof document !== 'undefined' &&
        document.readyState === 'loading' &&
        typeof document.addEventListener === 'function'
    ) {
        document.addEventListener('DOMContentLoaded', initAutofill);
    } else {
        initAutofill();
    }
}

// Expose untuk testing di lingkungan Node.js (MV3 classic script pattern)
if (typeof globalThis !== 'undefined') {
    globalThis.__sifastAutofill = {
        setNativeValue,
        queryField,
        resolveElementFromList,
        resolveFormFields,
        runHeuristicScanner,
        detectCaptcha,
        showAutofillToast,
        executeAutofill,
        initAutofill,
        escapeHtml,
    };
}
