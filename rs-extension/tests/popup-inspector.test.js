/* global globalThis */
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

  await t.test('handles fallback username and default selectors when inputs are empty or generic', () => {
    const inputs = [
      new MockInput({ id: 'custom_input', name: 'custom', type: 'text', placeholder: 'Ketik sesuatu' }),
      new MockInput({ id: 'pwd', name: 'pwd', type: 'password', placeholder: 'Sandi' }),
    ];

    const inspection = popupModule.analyzePageInputs(inputs);
    assert.ok(inspection.usernameCandidate, 'Fallback username candidate should be selected from text inputs');
    assert.strictEqual(inspection.usernameCandidate.id, 'custom_input');
    assert.strictEqual(inspection.passwordCandidate.id, 'pwd');
    assert.strictEqual(inspection.captchaCandidate, null);

    // When empty
    const emptyInspection = popupModule.analyzePageInputs([]);
    assert.strictEqual(emptyInspection.usernameCandidate, null);
    assert.strictEqual(emptyInspection.passwordCandidate, null);
    const emptyConfig = popupModule.generateFormConfigJson(emptyInspection);
    assert.deepStrictEqual(emptyConfig.username_field.selectors, ["#username", "input[name='username']", "input[type='email']"]);
    assert.deepStrictEqual(emptyConfig.password_field.selectors, ["#password", "input[name='password']", "input[type='password']"]);
  });

  await t.test('filters non-text input types like hidden, submit, button, checkbox', () => {
    const inputs = [
      new MockInput({ id: 'csrf_token', name: 'user_token', type: 'hidden', placeholder: '' }),
      new MockInput({ id: 'remember_me', name: 'remember_user', type: 'checkbox', placeholder: '' }),
      new MockInput({ id: 'btn_login', name: 'login_btn', type: 'submit', placeholder: '' }),
      new MockInput({ id: 'real_username', name: 'identity', type: 'text', placeholder: 'Masukkan username' }),
      new MockInput({ id: 'real_password', name: 'password', type: 'password', placeholder: 'Masukkan password' }),
    ];

    const inspection = popupModule.analyzePageInputs(inputs);
    assert.strictEqual(inspection.usernameCandidate.id, 'real_username', 'Must not select hidden or checkbox as username');
    assert.strictEqual(inspection.passwordCandidate.id, 'real_password');
  });

  await t.test('escapeHtml safely neutralizes HTML markup', () => {
    const dangerous = `<script>alert('xss')</script>&"test"`;
    const escaped = popupModule.escapeHtml(dangerous);
    assert.strictEqual(escaped, '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;&amp;&quot;test&quot;');
    assert.strictEqual(popupModule.escapeHtml(''), '');
    assert.strictEqual(popupModule.escapeHtml(null), '');
  });

  await t.test('scanDomInTab extracts input fields from DOM document', () => {
    const originalDocument = globalThis.document;
    try {
      globalThis.document = {
        querySelectorAll: (selector) => {
          assert.strictEqual(selector, 'input, select, textarea');
          return [
            { tagName: 'INPUT', id: 'txtUser', name: 'username', type: 'text', placeholder: 'User', className: 'form-control' },
            { tagName: 'INPUT', id: 'txtPass', name: 'password', type: 'password', placeholder: 'Pass', className: 'form-control' },
          ];
        },
      };

      const extracted = popupModule.scanDomInTab();
      assert.strictEqual(extracted.length, 2);
      assert.strictEqual(extracted[0].id, 'txtUser');
      assert.strictEqual(extracted[0].name, 'username');
      assert.strictEqual(extracted[1].id, 'txtPass');
      assert.strictEqual(extracted[1].type, 'password');
    } finally {
      globalThis.document = originalDocument;
    }
  });

  await t.test('initPopup attaches events and handles DOM inspection and copy flow with sanitization', async () => {
    const originalChrome = globalThis.chrome;
    const originalDoc = globalThis.document;

    const elements = {
      'active-tab-domain': { textContent: '' },
      'btn-inspect-form': {
        listeners: {},
        disabled: false,
        textContent: '',
        addEventListener(event, fn) { this.listeners[event] = fn; },
      },
      'inspector-results': {
        classList: {
          classes: new Set(['hidden']),
          remove(c) { this.classes.delete(c); },
          add(c) { this.classes.add(c); },
          contains(c) { return this.classes.has(c); },
        },
      },
      'detected-summary': { innerHTML: '' },
      'json-code-block': { textContent: '' },
      'btn-copy-json': {
        listeners: {},
        textContent: '',
        addEventListener(event, fn) { this.listeners[event] = fn; },
      },
    };

    let copiedText = '';
    const desc = Object.getOwnPropertyDescriptor(globalThis.navigator, 'clipboard');

    try {
      globalThis.document = {
        getElementById: (id) => elements[id] || null,
      };

      Object.defineProperty(globalThis.navigator, 'clipboard', {
        value: {
          writeText: async (text) => {
            copiedText = text;
            return Promise.resolve();
          },
        },
        configurable: true,
        writable: true,
      });

      globalThis.chrome = {
        tabs: {
          query: (queryInfo, callback) => {
            callback([{ id: 42, url: 'https://sirs.kemkes.go.id/fo/login' }]);
          },
        },
        scripting: {
          executeScript: (options, callback) => {
            assert.strictEqual(options.target.tabId, 42);
            callback([
              {
                result: [
                  { tagName: 'INPUT', id: '<img src=x onerror=1>', name: 'user_login', type: 'text', placeholder: 'Username / Email' },
                  { tagName: 'INPUT', id: 'txtPassword', name: 'user_password', type: 'password', placeholder: 'Password' },
                ],
              },
            ]);
          },
        },
      };

      // Call initPopup
      popupModule.initPopup();

      // Check active tab domain text
      assert.strictEqual(elements['active-tab-domain'].textContent, 'sirs.kemkes.go.id (/fo/login)');

      // Simulate clicking inspect button
      assert.ok(elements['btn-inspect-form'].listeners['click']);
      elements['btn-inspect-form'].listeners['click']();

      // Verify results container is shown and JSON is generated
      assert.strictEqual(elements['inspector-results'].classList.contains('hidden'), false);
      assert.ok(elements['json-code-block'].textContent.includes('txtPassword'));
      assert.ok(elements['detected-summary'].innerHTML.includes('Hasil Deteksi:'));
      // Verify malicious markup is escaped
      assert.ok(elements['detected-summary'].innerHTML.includes('&lt;img src=x onerror=1&gt;'));
      assert.strictEqual(elements['detected-summary'].innerHTML.includes('<img src=x onerror=1>'), false);

      // Simulate clicking copy button
      assert.ok(elements['btn-copy-json'].listeners['click']);
      elements['btn-copy-json'].listeners['click']();

      await new Promise((resolve) => setTimeout(resolve, 10));
      assert.strictEqual(copiedText, elements['json-code-block'].textContent);
      assert.strictEqual(elements['btn-copy-json'].textContent, 'Tersalin!');
    } finally {
      globalThis.chrome = originalChrome;
      globalThis.document = originalDoc;
      if (desc) {
        Object.defineProperty(globalThis.navigator, 'clipboard', desc);
      } else {
        delete globalThis.navigator.clipboard;
      }
    }
  });

  await t.test('initPopup handles chrome.runtime.lastError when executeScript fails', () => {
    const originalChrome = globalThis.chrome;
    const originalDoc = globalThis.document;
    const originalAlert = globalThis.alert;

    let alertMsg = '';
    globalThis.alert = (msg) => { alertMsg = msg; };

    const elements = {
      'active-tab-domain': { textContent: '' },
      'btn-inspect-form': {
        listeners: {},
        disabled: false,
        textContent: '',
        addEventListener(event, fn) { this.listeners[event] = fn; },
      },
      'inspector-results': {
        classList: { classes: new Set(['hidden']) },
      },
      'detected-summary': { innerHTML: '' },
      'json-code-block': { textContent: '' },
      'btn-copy-json': { listeners: {}, addEventListener() {} },
    };

    try {
      globalThis.document = { getElementById: (id) => elements[id] || null };
      globalThis.chrome = {
        runtime: {
          lastError: { message: 'Cannot access chrome:// page' },
        },
        tabs: {
          query: (queryInfo, callback) => {
            callback([{ id: 10, url: 'chrome://extensions' }]);
          },
        },
        scripting: {
          executeScript: (options, callback) => {
            callback(undefined);
          },
        },
      };

      popupModule.initPopup();
      elements['btn-inspect-form'].listeners['click']();

      assert.ok(alertMsg.includes('Cannot access chrome:// page'));
      assert.strictEqual(elements['btn-inspect-form'].disabled, false);
      assert.strictEqual(elements['btn-inspect-form'].textContent, 'Scan Form Login Halaman Ini');
    } finally {
      globalThis.chrome = originalChrome;
      globalThis.document = originalDoc;
      globalThis.alert = originalAlert;
    }
  });
});
