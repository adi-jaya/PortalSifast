import assert from 'node:assert';
import fs from 'node:fs';
import path from 'node:path';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const EXTENSION_DIR = path.resolve(__dirname, '..');
const MANIFEST_PATH = path.join(EXTENSION_DIR, 'manifest.json');

test('Manifest V3 validation', async (t) => {
    await t.test('manifest.json exists and is valid JSON', () => {
        assert.strictEqual(
            fs.existsSync(MANIFEST_PATH),
            true,
            'manifest.json must exist',
        );
        const raw = fs.readFileSync(MANIFEST_PATH, 'utf-8');
        const manifest = JSON.parse(raw);
        assert.strictEqual(manifest.manifest_version, 3);
        assert.strictEqual(typeof manifest.name, 'string');
        assert.strictEqual(typeof manifest.version, 'string');
        assert.strictEqual(manifest.version, '1.0.0');
    });

    await t.test(
        'permissions and host_permissions conform to specification',
        () => {
            const manifest = JSON.parse(
                fs.readFileSync(MANIFEST_PATH, 'utf-8'),
            );
            assert.deepStrictEqual(
                manifest.permissions.sort(),
                ['scripting', 'storage', 'tabs'].sort(),
            );

            // Check host permissions include SIMRS domains and target agencies
            const hosts = manifest.host_permissions || [];
            assert.ok(
                hosts.includes('*://*.rsaisyiyahsitifatimah.com/*'),
                'Must include production SIMRS domain',
            );
            assert.ok(
                hosts.includes('http://localhost/*'),
                'Must include local development domain',
            );
            assert.ok(
                hosts.includes('http://127.0.0.1/*'),
                'Must include local 127.0.0.1 domain',
            );
            assert.ok(
                hosts.includes('https://*.kemkes.go.id/*'),
                'Must include Kemenkes domains',
            );
            assert.ok(
                hosts.includes('https://*.bkkbn.go.id/*'),
                'Must include BKKBN domains',
            );
            assert.ok(
                hosts.includes('https://*.kemendukbangga.go.id/*'),
                'Must include Kemendukbangga domains',
            );
            assert.ok(
                hosts.includes('https://*.sitb.id/*'),
                'Must include SITB domains',
            );
        },
    );

    await t.test('service worker and content scripts are registered', () => {
        const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf-8'));
        assert.strictEqual(manifest.background.service_worker, 'background.js');
        assert.strictEqual(manifest.background.type, 'module');

        assert.ok(
            Array.isArray(manifest.content_scripts),
            'content_scripts must be an array',
        );
        assert.strictEqual(manifest.content_scripts.length, 2);

        const simrsScript = manifest.content_scripts.find((s) =>
            s.js.includes('content-simrs.js'),
        );
        assert.ok(simrsScript, 'content-simrs.js must be registered');
        assert.strictEqual(simrsScript.run_at, 'document_start');

        const targetScript = manifest.content_scripts.find((s) =>
            s.js.includes('content-autofill.js'),
        );
        assert.ok(targetScript, 'content-autofill.js must be registered');
        assert.strictEqual(targetScript.run_at, 'document_idle');
    });

    await t.test(
        'icons and action popup are properly registered and files exist',
        () => {
            const manifest = JSON.parse(
                fs.readFileSync(MANIFEST_PATH, 'utf-8'),
            );
            assert.strictEqual(
                manifest.action.default_popup,
                'popup/popup.html',
            );

            const iconSizes = ['16', '48', '128'];
            for (const size of iconSizes) {
                const relPath = manifest.icons[size];
                assert.ok(relPath, `manifest.icons[${size}] must be defined`);
                const fullPath = path.join(EXTENSION_DIR, relPath);
                assert.strictEqual(
                    fs.existsSync(fullPath),
                    true,
                    `Icon file ${relPath} must exist`,
                );

                // Verify file is a non-empty PNG
                const buffer = fs.readFileSync(fullPath);
                assert.ok(
                    buffer.length > 50,
                    `Icon ${relPath} must not be empty`,
                );
                // PNG magic number: 89 50 4E 47 0D 0A 1A 0A
                assert.strictEqual(buffer[0], 0x89);
                assert.strictEqual(buffer[1], 0x50);
                assert.strictEqual(buffer[2], 0x4e);
                assert.strictEqual(buffer[3], 0x47);
            }
        },
    );
});
