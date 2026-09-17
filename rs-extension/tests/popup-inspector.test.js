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

  await t.test('initPopup attaches events and handles DOM inspection and copy flow', async () => {
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
                  { tagName: 'INPUT', id: 'txtEmail', name: 'user_login', type: 'text', placeholder: 'Username / Email' },
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
      assert.ok(elements['json-code-block'].textContent.includes('txtEmail'));
      assert.ok(elements['detected-summary'].innerHTML.includes('Hasil Deteksi:'));

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
});
