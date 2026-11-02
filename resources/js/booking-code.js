export async function copyBookingCode(code, clipboard = globalThis.navigator?.clipboard) {
    if (!code || !clipboard?.writeText) {
        return 'Penyalinan otomatis tidak tersedia. Pilih kode booking lalu salin secara manual.';
    }

    try {
        await clipboard.writeText(code);
        return 'Kode booking berhasil disalin.';
    } catch {
        return 'Kode booking gagal disalin. Pilih kode booking lalu salin secara manual.';
    }
}
