export function validateWarehousePhotos(files, retainedCount) {
    if (retainedCount + files.length > 20) {
        throw new Error('Maksimal 20 foto per gudang, termasuk foto lama yang dipertahankan.');
    }
    for (const file of files) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size < 1 || file.size > 2097152) {
            throw new Error('Gunakan foto JPEG, PNG, atau WebP, masing-masing maksimal 2 MB.');
        }
    }
}

export function uploadHeaders(headers) {
    return Object.fromEntries(Object.entries(headers)
        .filter(([name]) => !['host', 'content-length', 'connection'].includes(name.toLowerCase()))
        .map(([name, value]) => [name, Array.isArray(value) ? value.join(', ') : value]));
}

async function responseData(response) {
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(Object.values(data.errors ?? {}).flat().join('\n') || 'Upload gagal. Silakan coba kembali.');
    }
    return data;
}

export function initWarehousePhotoUpload() {
    document.querySelectorAll('[data-warehouse-photo-form]').forEach((form) => {
        let busy = false;
        form.addEventListener('submit', async (event) => {
            if (busy) { event.preventDefault(); return; }
            const input = form.querySelector('input[type=file]');
            const files = Array.from(input.files);
            try {
                validateWarehousePhotos(files, Number(form.dataset.existingPhotos)
                    - form.querySelectorAll('[name="hapus_foto[]"]:checked').length);
            } catch (error) {
                event.preventDefault();
                window.alert(error.message);
                return;
            }
            const endpoint = form.dataset.directUploadUrl;
            if (!endpoint) { return; }
            event.preventDefault();
            busy = true;
            const buttons = form.querySelectorAll('button[type=submit]');
            buttons.forEach((button) => { button.disabled = true; });
            input.disabled = true;
            let uploads = [];
            let saved = false;
            const csrf = form.querySelector('[name=_token]').value;
            const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf };
            try {
                if (files.length) {
                    const result = await responseData(await fetch(endpoint, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { ...headers, 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            warehouse_id: form.dataset.warehouseId || null,
                            files: files.map((file) => ({ mime: file.type, size: file.size })),
                        }),
                    }));
                    uploads = result.uploads;
                    for (let i = 0; i < files.length; i++) {
                        const result = await fetch(uploads[i].url, {
                            method: 'PUT', headers: uploadHeaders(uploads[i].headers),
                            body: files[i], credentials: 'omit', referrerPolicy: 'no-referrer',
                        });
                        if (!result.ok) { throw new Error('Upload foto ke storage gagal. Silakan coba kembali.'); }
                    }
                }
                const data = new FormData(form);
                data.delete('foto[]');
                uploads.forEach((upload) => data.append('photo_uploads[]', upload.id));
                const result = await fetch(form.action, { method: 'POST', headers, body: data, credentials: 'same-origin' });
                if (!result.ok) { await responseData(result); }
                if (!result.redirected) { throw new Error('Penyimpanan gudang belum terkonfirmasi.'); }
                saved = true;
                window.location.assign(result.url);
            } catch (error) {
                window.alert(error.message || 'Penyimpanan gagal. Silakan coba kembali.');
            } finally {
                if (!saved) {
                    await Promise.allSettled(uploads.map((upload) => fetch(`${endpoint}/${upload.id}`, {
                        method: 'DELETE', credentials: 'same-origin', headers,
                    })));
                }
                input.disabled = false;
                buttons.forEach((button) => { button.disabled = false; });
                busy = false;
            }
        });
    });
}
