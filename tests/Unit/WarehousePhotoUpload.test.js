import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validateWarehousePhotos, uploadHeaders, initWarehousePhotoUpload } from '../../resources/js/warehouse-photo-upload.js';

test('photo count includes retained photos and allows replacing a removed photo', () => {
    const file = { type: 'image/png', size: 2097152 };
    assert.throws(() => validateWarehousePhotos([file], 20), /20 foto/);
    assert.doesNotThrow(() => validateWarehousePhotos([file], 19));
    for (const invalid of [{ type: 'image/svg+xml', size: 10 }, { type: 'image/png', size: 2097153 }, { type: 'image/png', size: 0 }]) {
        assert.throws(() => validateWarehousePhotos([invalid], 0), /2 MB/);
    }
});

test('signed headers preserve content type and omit forbidden browser headers', () => {
    assert.deepEqual(uploadHeaders({ Host: ['storage.example.test'], 'Content-Length': ['100'], 'Content-Type': ['image/png'] }), {
        'Content-Type': 'image/png',
    });
});

async function submitPhotoForm(failUpload = false) {
    const originals = Object.fromEntries(['document', 'window', 'fetch', 'FormData'].map((key) => [key, globalThis[key]]));
    const calls = [];
    const alerts = [];
    const input = { files: [{ type: 'image/png', size: 100 }], disabled: false };
    const button = { disabled: false };
    let submit;
    const form = {
        action: '/admin/gudang', dataset: { existingPhotos: '0', directUploadUrl: '/admin/warehouse-photo-uploads' },
        addEventListener: (_, listener) => { submit = listener; },
        querySelector: (selector) => selector.includes('file') ? input : { value: 'csrf-test' },
        querySelectorAll: (selector) => selector.includes('submit') ? [button] : [],
    };
    try {
        globalThis.document = { querySelectorAll: () => [form] };
        globalThis.window = { alert: (value) => alerts.push(value), location: { assign: (url) => calls.push({ navigate: url }) } };
        globalThis.FormData = class {
            constructor() { this.entries = { 'foto[]': input.files, _token: 'csrf-test' }; }
            delete(key) { delete this.entries[key]; }
            append(key, value) { this.entries[key] = value; }
        };
        globalThis.fetch = async (url, options) => {
            calls.push({ url, ...options });
            if (options.method === 'PUT') { return { ok: !failUpload }; }
            if (url === '/admin/warehouse-photo-uploads') {
                return { ok: true, json: async () => ({ uploads: [{ id: 'upload-test', url: 'https://storage.example.test/signed', headers: { 'Content-Type': ['image/png'] } }] }) };
            }
            return { ok: true, redirected: true, url: '/admin/gudang' };
        };
        initWarehousePhotoUpload();
        await submit({ preventDefault() {} });
        return { calls, alerts, input, button };
    } finally {
        for (const [key, value] of Object.entries(originals)) {
            if (value === undefined) { delete globalThis[key]; } else { globalThis[key] = value; }
        }
    }
}

test('file bytes go only to signed storage URL; Laravel receives upload IDs', async () => {
    const { calls, alerts } = await submitPhotoForm();
    assert.deepEqual(alerts, []);
    const put = calls.find((call) => call.method === 'PUT');
    assert.equal(put.url, 'https://storage.example.test/signed');
    assert.equal(put.credentials, 'omit');
    const save = calls.find((call) => call.url === '/admin/gudang');
    assert.equal(save.body.entries['foto[]'], undefined);
    assert.equal(save.body.entries['photo_uploads[]'], 'upload-test');
    assert.equal(save.headers['X-CSRF-TOKEN'], 'csrf-test');
});

test('failed storage upload cancels the intent and restores form controls', async () => {
    const { calls, alerts, input, button } = await submitPhotoForm(true);
    assert.equal(calls.some((call) => call.url === '/admin/gudang'), false);
    assert.equal(calls.some((call) => call.method === 'DELETE'), true);
    assert.match(alerts[0], /Upload foto ke storage gagal/);
    assert.equal(input.disabled, false);
    assert.equal(button.disabled, false);
});
