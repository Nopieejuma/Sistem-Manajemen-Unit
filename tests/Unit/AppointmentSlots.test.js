import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const blade = readFileSync(new URL('../../resources/views/customer/temu-janji/create.blade.php', import.meta.url), 'utf8');
const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("@json($availabilityUrl ?? route('customer.temu-janji.availability'))", '"http://localhost/availability"')
    .replace('@json($detailUrl)', '"http://localhost/detail"');

function setup() {
    const listeners = {};
    const date = { value: '2026-11-02', validity: { valid: true }, addEventListener: (event, fn) => { listeners[event] = fn; } };
    function slot() {
        const label = { textContent: '' };
        const input = { checked: false, disabled: false, dataset: {}, closest: () => ({ querySelector: () => label }) };
        return { input, label, title: { textContent: '' } };
    }
    const container = {
        slots: [slot()],
        querySelectorAll() { return this.slots.map((item) => item.input); },
        replaceChildren() { this.slots = []; },
        append(fragment) { this.slots.push(fragment.slot); },
    };
    const template = { content: { cloneNode() {
        const item = slot();
        return { slot: item, querySelector: (selector) => selector === 'input' ? item.input : item.title };
    } } };
    const button = {};
    const message = {};
    const formListeners = {};
    const form = { querySelectorAll: () => [date], addEventListener(event, fn) { formListeners[event] = fn; } };
    const nodes = {
        'appointment-form': form, 'confirm-appointment': button,
        'appointment-success': { addEventListener() {} }, 'booking-error': {},
        'visit-date': date, 'appointment-slots': container,
        'appointment-slot-template': template, 'slot-availability-message': message,
    };
    const requests = [];
    vm.runInNewContext(script, {
        document: { getElementById: (id) => nodes[id] },
        window: { location: { origin: 'http://localhost' }, addEventListener() {} },
        URL,
        fetch: (url) => new Promise((resolve, reject) => { requests.push({ url, resolve, reject }); }),
    });
    const resolve = (index, slots) => requests[index].resolve({ ok: true, json: async () => ({ slots }) });
    return { date, button, message, container, requests, resolve, refresh: () => listeners.change(), update: () => formListeners.input() };
}

const available = { id: 'slot-1', start: '09:00', end: '09:45', is_full: false, is_past: false };

test('changing date clears selection and disables slots until fresh availability arrives', async () => {
    const ui = setup();
    ui.container.slots[0].input.checked = true;
    ui.date.value = '2026-11-03';
    const pending = ui.refresh();
    assert.equal(ui.container.slots[0].input.checked, false);
    assert.equal(ui.container.slots[0].input.disabled, true);
    assert.equal(ui.button.disabled, true);
    assert.equal(ui.requests[0].url.searchParams.get('tanggal_kunjungan'), '2026-11-03');
    ui.resolve(0, [{ ...available, is_full: true }]);
    await pending;
    assert.equal(ui.container.slots[0].input.disabled, true);
    assert.equal(ui.container.slots[0].label.textContent, 'Penuh');
    assert.equal(ui.button.disabled, true);
    ui.date.value = '2026-11-04';
    const next = ui.refresh();
    ui.resolve(1, [available]);
    await next;
    assert.equal(ui.container.slots[0].input.disabled, false);
    assert.equal(ui.container.slots[0].input.checked, false);
    assert.equal(ui.container.slots[0].label.textContent, 'Tersedia');
});

test('late response for old date cannot replace the newest date availability', async () => {
    const ui = setup();
    const oldRequest = ui.refresh();
    ui.date.value = '2026-11-05';
    const newRequest = ui.refresh();
    ui.resolve(1, [{ ...available, is_full: true }]);
    await newRequest;
    ui.resolve(0, [available]);
    await oldRequest;
    assert.equal(ui.container.slots[0].label.textContent, 'Penuh');
    assert.equal(ui.container.slots[0].input.disabled, true);
});

test('failed availability requests keep slots and submit disabled', async () => {
    const ui = setup();
    const pending = ui.refresh();
    ui.requests[0].reject(new Error('Network failed'));
    await pending;
    assert.equal(ui.container.slots[0].input.disabled, true);
    assert.equal(ui.button.disabled, true);
    assert.equal(ui.message.textContent, 'Network failed');
});

test('past slots are disabled with a distinct label', async () => {
    const ui = setup();
    const pending = ui.refresh();
    ui.resolve(0, [{ ...available, is_past: true }]);
    await pending;
    assert.equal(ui.container.slots[0].input.disabled, true);
    assert.equal(ui.container.slots[0].label.textContent, 'Sudah lewat');
});

test('selected available slot gets Dipilih label and enables submit', async () => {
    const ui = setup();
    const pending = ui.refresh();
    ui.resolve(0, [available]);
    await pending;
    ui.container.slots[0].input.checked = true;
    ui.update();
    assert.equal(ui.container.slots[0].label.textContent, 'Dipilih');
    assert.equal(ui.button.disabled, false);
});

test('clearing date leaves slots disabled and does not fetch availability', async () => {
    const ui = setup();
    ui.date.value = '';
    await ui.refresh();
    assert.equal(ui.requests.length, 0);
    assert.equal(ui.button.disabled, true);
    assert.equal(ui.container.slots[0].input.disabled, true);
});
