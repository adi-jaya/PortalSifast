/* global globalThis, global */
import assert from 'node:assert';
import { test } from 'node:test';

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

        get id() {
            return this.attributes.id || '';
        }
        set id(val) {
            this.attributes.id = val;
        }
        get name() {
            return this.attributes.name || '';
        }
        set name(val) {
            this.attributes.name = val;
        }
        get type() {
            return this.attributes.type || 'text';
        }
        set type(val) {
            this.attributes.type = val;
        }
        get placeholder() {
            return this.attributes.placeholder || '';
        }
        set placeholder(val) {
            this.attributes.placeholder = val;
        }
        get value() {
            return this._value;
        }
        set value(val) {
            this._value = val;
        }

        getAttribute(name) {
            return this.attributes[name] || null;
        }
        setAttribute(name, val) {
            this.attributes[name] = val;
        }

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

        remove() {
            if (
                this.parentElement &&
                Array.isArray(this.parentElement.children)
            ) {
                const idx = this.parentElement.children.indexOf(this);
                if (idx !== -1) this.parentElement.children.splice(idx, 1);
            }
            this.parentElement = null;
        }

        appendChild(child) {
            child.parentElement = this;
            this.children.push(child);
            return child;
        }
    }

    // Setup prototype descriptor setter for testing React/Vue bypass
    let nativeSetterCalled = false;
    Object.defineProperty(MockElement.prototype, 'value', {
        get() {
            return this._value;
        },
        set(val) {
            nativeSetterCalled = true;
            this._value = val;
        },
        configurable: true,
    });

    await import('../content-autofill.js');
    const autofillEngine = globalThis.__sifastAutofill;

    assert.ok(autofillEngine, 'globalThis.__sifastAutofill must be defined');

    await t.test(
        'setNativeValue bypasses framework wrappers and dispatches input/change/blur',
        () => {
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
            assert.strictEqual(
                nativeSetterCalled,
                true,
                'Prototype native setter must be called',
            );
            assert.ok(
                input.eventsDispatched.includes('input'),
                'Must dispatch input event',
            );
            assert.ok(
                input.eventsDispatched.includes('change'),
                'Must dispatch change event',
            );
            assert.ok(
                input.eventsDispatched.includes('blur'),
                'Must dispatch blur event',
            );
        },
    );

    await t.test('finds form fields using static CSS selectors', () => {
        const userInput = new MockElement('input', {
            id: 'email',
            name: 'email',
            type: 'text',
        });
        const passInput = new MockElement('input', {
            id: 'password',
            name: 'password',
            type: 'password',
        });

        const root = {
            querySelector(selector) {
                if (selector === '#email' || selector === "input[name='email']")
                    return userInput;
                if (
                    selector === '#password' ||
                    selector === "input[name='password']"
                )
                    return passInput;
                return null;
            },
            querySelectorAll() {
                return [];
            },
        };

        const formConfig = {
            username_field: {
                selectors: ['#c', "input[name='email']", '#email'],
            },
            password_field: {
                selectors: ['#password', "input[name='password']"],
            },
        };

        const resolved = autofillEngine.resolveFormFields(formConfig, root);
        assert.strictEqual(resolved.usernameElement, userInput);
        assert.strictEqual(resolved.passwordElement, passInput);
    });

    await t.test(
        'heuristic scanner finds fields when static selectors fail (e.g. SIRS Online without id/name)',
        () => {
            // Construct DOM structure without id and name
            const form = new MockElement('form');
            const emailInput = new MockElement('input', {
                type: 'email',
                placeholder: 'Masukkan Email Pengguna',
            });
            const passwordInput = new MockElement('input', {
                type: 'password',
                placeholder: 'Kata Sandi',
            });
            const captchaInput = new MockElement('input', {
                type: 'text',
                placeholder: 'Kode Captcha',
            });

            emailInput.parentElement = form;
            passwordInput.parentElement = form;
            captchaInput.parentElement = form;
            form.children = [emailInput, passwordInput, captchaInput];

            const allInputs = [emailInput, passwordInput, captchaInput];

            const root = {
                querySelector() {
                    return null;
                },
                querySelectorAll(selector) {
                    if (selector.includes("type='password'"))
                        return [passwordInput];
                    if (selector === 'input, select, textarea')
                        return allInputs;
                    return allInputs;
                },
            };

            const detected = autofillEngine.runHeuristicScanner(root);
            assert.strictEqual(detected.passwordElement, passwordInput);
            assert.strictEqual(detected.usernameElement, emailInput);
        },
    );

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

    await t.test(
        'executes autofill, fills extra fields, focuses captcha, and wipes memory variables',
        () => {
            const userInput = new MockElement('input', {
                id: 'uname',
                type: 'text',
            });
            const passInput = new MockElement('input', {
                id: 'pwd',
                type: 'password',
            });
            const satkerInput = new MockElement('input', {
                id: 'satker',
                type: 'text',
            });
            const captchaInput = new MockElement('input', {
                id: 'captcha',
                type: 'text',
            });

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
                        extra_fields: [
                            { key: 'kode_satker', selectors: ['#satker'] },
                        ],
                    },
                },
                credentials: {
                    username: 'rsasf_simar',
                    password: 'PasswordSimar123!',
                    extra_fields: { kode_satker: '3515002' },
                },
            };

            const result = autofillEngine.executeAutofill(payload, root, {
                showToast: false,
            });

            assert.strictEqual(result.success, true);
            assert.strictEqual(userInput.value, 'rsasf_simar');
            assert.strictEqual(passInput.value, 'PasswordSimar123!');
            assert.strictEqual(satkerInput.value, '3515002');
            assert.strictEqual(
                captchaInput.focused,
                true,
                'CAPTCHA input must receive focus',
            );

            // Zero-leakage check: payload credentials must be erased
            assert.strictEqual(payload.credentials, null);
        },
    );

    await t.test('resolves fields using XPath expression in queryField', () => {
        const userInput = new MockElement('input', {
            name: 'username',
            type: 'text',
        });

        // Mock document.evaluate and XPathResult
        const originalEvaluate = global.document?.evaluate;
        const originalXPathResult = global.XPathResult;

        global.XPathResult = { FIRST_ORDERED_NODE_TYPE: 9 };
        const mockDoc = {
            evaluate(xpath) {
                if (xpath === "//input[@name='username']") {
                    return { singleNodeValue: userInput };
                }
                return { singleNodeValue: null };
            },
        };
        global.document = mockDoc;

        const result = autofillEngine.queryField(
            "//input[@name='username']",
            mockDoc,
        );
        assert.strictEqual(result, userInput);

        // Clean up
        if (originalEvaluate) global.document.evaluate = originalEvaluate;
        if (originalXPathResult) global.XPathResult = originalXPathResult;
        else delete global.XPathResult;
    });

    await t.test(
        'renders floating autofill toast in DOM and closes on button click',
        () => {
            let appendedChild = null;

            const mockBody = {
                children: [],
                appendChild(child) {
                    appendedChild = child;
                    child.parentElement = this;
                    this.children.push(child);
                    return child;
                },
            };

            const mockDocument = {
                body: mockBody,
                createElement(tag) {
                    const el = new MockElement(tag);
                    el.listeners = {};
                    el.addEventListener = (event, fn) => {
                        el.listeners[event] = fn;
                    };
                    el.click = () => {
                        if (el.listeners.click) el.listeners.click();
                    };
                    return el;
                },
                getElementById(id) {
                    if (id === 'sifast-autofill-toast') return appendedChild;
                    if (id === 'sifast-toast-close' && appendedChild) {
                        const btn = mockDocument.createElement('button');
                        btn.id = 'sifast-toast-close';
                        return btn;
                    }
                    return null;
                },
            };

            global.document = mockDocument;

            autofillEngine.showAutofillToast('SIRS Online');
            assert.ok(appendedChild, 'Toast element must be appended to body');
            assert.strictEqual(appendedChild.id, 'sifast-autofill-toast');
            assert.ok(
                appendedChild.innerHTML.includes('SIRS Online'),
                'Toast should mention portal name',
            );
        },
    );

    await t.test(
        'initAutofill queries background for credentials and executes autofill',
        () => {
            let messageSent = null;
            const userInput = new MockElement('input', {
                id: 'user',
                type: 'text',
            });
            const passInput = new MockElement('input', {
                id: 'pass',
                type: 'password',
            });

            const mockDoc = {
                body: new MockElement('body'),
                querySelector(sel) {
                    if (sel === '#user') return userInput;
                    if (sel === '#pass') return passInput;
                    return null;
                },
                querySelectorAll() {
                    return [userInput, passInput];
                },
                getElementById() {
                    return null;
                },
                createElement(tag) {
                    return new MockElement(tag);
                },
            };

            global.document = mockDoc;
            global.chrome = {
                runtime: {
                    sendMessage(msg, cb) {
                        messageSent = msg;
                        cb({
                            success: true,
                            payload: {
                                portal: {
                                    name: 'Kemenkes Test',
                                    form_config: {
                                        username_field: {
                                            selectors: ['#user'],
                                        },
                                        password_field: {
                                            selectors: ['#pass'],
                                        },
                                    },
                                },
                                credentials: {
                                    username: 'admin_test',
                                    password: 'SecretPassword123',
                                },
                            },
                        });
                    },
                },
            };

            autofillEngine.initAutofill();

            assert.ok(messageSent, 'chrome.runtime.sendMessage must be called');
            assert.strictEqual(messageSent.type, 'SIFAST_GET_CREDENTIALS');
            assert.strictEqual(userInput.value, 'admin_test');
            assert.strictEqual(passInput.value, 'SecretPassword123');
        },
    );

    await t.test(
        'executeAutofill handles missing or empty credentials gracefully',
        () => {
            const result = autofillEngine.executeAutofill(null);
            assert.strictEqual(result.success, false);
            assert.strictEqual(result.reason, 'NO_CREDENTIALS');

            const result2 = autofillEngine.executeAutofill({
                credentials: null,
            });
            assert.strictEqual(result2.success, false);
            assert.strictEqual(result2.reason, 'NO_CREDENTIALS');
        },
    );
});
