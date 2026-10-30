/* global chrome */
/**
 * SIFAST Portal Autofill Extension - Popup & Admin Form Inspector Script
 */

/**
 * Meng-escape karakter khusus HTML untuk mencegah injeksi DOM (XSS).
 */
export function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Menganalisis daftar elemen input untuk menentukan kandidat form login.
 */
export function analyzePageInputs(inputs) {
    let usernameCandidate = null;
    let passwordCandidate = null;
    let captchaCandidate = null;
    const extraCandidates = [];
    const ignoredTypes = [
        'hidden',
        'submit',
        'button',
        'checkbox',
        'radio',
        'file',
        'image',
        'reset',
    ];

    for (const el of inputs) {
        const type = (el.type || 'text').toLowerCase();
        const id = (el.id || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        const placeholder = (el.placeholder || '').toLowerCase();

        // Abaikan input non-data seperti tombol, radio, checkbox, atau hidden token
        if (ignoredTypes.includes(type)) {
            continue;
        }

        if (type === 'password' && !passwordCandidate) {
            passwordCandidate = el;
            continue;
        }

        if (
            id.includes('captcha') ||
            name.includes('captcha') ||
            placeholder.includes('captcha')
        ) {
            captchaCandidate = el;
            continue;
        }

        if (
            !usernameCandidate &&
            (type === 'email' ||
                id.includes('user') ||
                name.includes('user') ||
                id.includes('email') ||
                name.includes('email') ||
                id.includes('login') ||
                name.includes('login'))
        ) {
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
        if (el.placeholder)
            usernameSelectors.push(`input[placeholder*='${el.placeholder}' i]`);
    } else {
        usernameSelectors.push(
            '#username',
            "input[name='username']",
            "input[type='email']",
        );
    }

    const passwordSelectors = [];
    if (analysis.passwordCandidate) {
        const el = analysis.passwordCandidate;
        if (el.id) passwordSelectors.push(`#${el.id}`);
        if (el.name) passwordSelectors.push(`input[name='${el.name}']`);
        passwordSelectors.push("input[type='password']");
    } else {
        passwordSelectors.push(
            '#password',
            "input[name='password']",
            "input[type='password']",
        );
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
export function scanDomInTab() {
    const allInputs = Array.from(
        document.querySelectorAll('input, select, textarea'),
    );
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
            if (activeTabDomainEl)
                activeTabDomainEl.textContent = currentTab.url || 'Tab Lokal';
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

                        if (chrome.runtime && chrome.runtime.lastError) {
                            const errMsg =
                                chrome.runtime.lastError.message ||
                                'Halaman dibatasi atau tidak dapat diakses.';
                            alert(`Gagal memindai tab aktif: ${errMsg}`);
                            return;
                        }

                        if (!results || !results[0] || !results[0].result) {
                            alert('Gagal memindai tab aktif.');
                            return;
                        }

                        const inputs = results[0].result;
                        const analysis = analyzePageInputs(inputs);
                        const config = generateFormConfigJson(analysis);
                        const configJson = JSON.stringify(config, null, 2);

                        if (summaryEl) {
                            const usernameText = escapeHtml(
                                analysis.usernameCandidate
                                    ? analysis.usernameCandidate.id ||
                                          analysis.usernameCandidate.name ||
                                          'Ditemukan'
                                    : 'Tidak terdeteksi',
                            );
                            const passwordText = escapeHtml(
                                analysis.passwordCandidate
                                    ? analysis.passwordCandidate.id ||
                                          analysis.passwordCandidate.name ||
                                          'Ditemukan'
                                    : 'Tidak terdeteksi',
                            );
                            const captchaText = escapeHtml(
                                analysis.captchaCandidate
                                    ? 'Terdeteksi (Manual Fokus)'
                                    : 'Tidak terdeteksi',
                            );

                            summaryEl.innerHTML = `
                <div><strong>Hasil Deteksi:</strong></div>
                <div>Username: <code>${usernameText}</code></div>
                <div>Password: <code>${passwordText}</code></div>
                <div>CAPTCHA: <code>${captchaText}</code></div>
              `;
                        }

                        if (codeBlock) {
                            codeBlock.textContent = configJson;
                        }

                        if (resultsContainer) {
                            resultsContainer.classList.remove('hidden');
                        }
                    },
                );
            });
        }

        if (btnCopy && codeBlock) {
            btnCopy.addEventListener('click', () => {
                navigator.clipboard
                    .writeText(codeBlock.textContent)
                    .then(() => {
                        btnCopy.textContent = 'Tersalin!';
                        setTimeout(() => {
                            btnCopy.textContent = 'Salin JSON';
                        }, 2000);
                    })
                    .catch((err) => {
                        console.error(
                            '[SIFAST Popup] Failed to copy JSON to clipboard:',
                            err,
                        );
                        btnCopy.textContent = 'Gagal menyalin';
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
