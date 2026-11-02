import { test } from 'node:test';
import assert from 'node:assert/strict';
import { copyBookingCode } from '../../resources/js/booking-code.js';

test('copies the saved booking code using Clipboard API', async () => {
    let copied;
    const message = await copyBookingCode('BK-ABC123', { writeText: async (code) => { copied = code; } });
    assert.equal(copied, 'BK-ABC123');
    assert.equal(message, 'Kode booking berhasil disalin.');
});

test('explains manual copying when clipboard permission is denied', async () => {
    const message = await copyBookingCode('BK-ABC123', { writeText: async () => { throw new Error('Denied'); } });
    assert.match(message, /gagal disalin/);
    assert.match(message, /secara manual/);
});

test('explains manual copying when Clipboard API is unavailable', async () => {
    assert.match(await copyBookingCode('BK-ABC123', null), /tidak tersedia/);
});
