<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Jadwalkan kunjungan ke Gudang {{ $warehouse->unit_code }} bersama admin WAREHOUSE.BOOK.">
        <title>Buat Temu Janji — WAREHOUSE.BOOK</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-customer.navbar />

        <main class="min-h-screen bg-[#f8f9fa] px-5 py-8 sm:px-8 sm:py-10">
            <div class="mx-auto flex max-w-[880px] flex-col gap-6 pb-10">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-gray-400">
                    <a href="{{ $detailUrl }}" class="transition hover:text-blue-700">Detail Gudang</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page" class="text-gray-600">Buat Temu Janji</span>
                </nav>

                <header class="pt-3 pb-2 sm:pt-4 sm:pb-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-[28px]">Buat Temu Janji</h1>
                    <p class="mt-1 text-base text-gray-500">Isi data di bawah untuk menjadwalkan kunjungan.</p>
                </header>

                <section aria-label="Ringkasan gudang terpilih" data-unit-id="{{ $warehouse->id }}" class="flex flex-wrap items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                    @if ($warehouse->firstPhotoUrl())
                        <img src="{{ $warehouse->firstPhotoUrl() }}" alt="Gudang {{ $warehouse->unit_code }}" referrerpolicy="no-referrer" class="h-[68px] w-[84px] shrink-0 rounded-xl object-cover">
                    @else
                        <div class="flex h-[68px] w-[84px] shrink-0 items-center justify-center rounded-xl bg-blue-100/60 p-2 text-center text-xs text-slate-500">Foto belum tersedia</div>
                    @endif
                    <div class="min-w-0 flex-1 basis-40">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-gray-900">Gudang {{ $warehouse->unit_code }}</h2>
                            <span class="rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">{{ strtoupper($warehouse->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">
                            Blok {{ $warehouse->blok ?? '—' }} ·
                            {{ $warehouse->grand_total ? number_format((float) $warehouse->grand_total, 0, ',', '.') : ($warehouse->total_luas_bangunan ? number_format((float) $warehouse->total_luas_bangunan, 0, ',', '.') : '—') }} m² ·
                            @if ((float) $warehouse->harga_sewa > 0)
                                Rp{{ number_format((float) $warehouse->harga_sewa, 0, ',', '.') }} / bulan
                            @else
                                Harga belum tersedia
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-blue-100 px-3 py-2 text-xs font-bold text-blue-700">Menunggu Pengajuan</span>
                </section>

                <p class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-700">Permohonan memerlukan persetujuan admin. Gudang baru di-keep setelah permohonan disetujui.</p>

                <form id="appointment-form" method="post" action="{{ $storeUrl }}" class="flex flex-col gap-5" aria-describedby="booking-unavailable">
                    @csrf
                    <input type="hidden" name="gudang_id" value="{{ $warehouse->id }}">
                    <section aria-labelledby="customer-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <h2 id="customer-heading" class="border-b border-gray-100 px-6 py-6 text-base font-bold text-gray-900">Data Customer</h2>
                        <div class="grid grid-cols-1 gap-x-5 gap-y-5 p-6 sm:grid-cols-2">
                            <div>
                                <label for="company-name" class="mb-2 block text-sm font-semibold text-gray-700">Nama Perusahaan <span class="text-red-500">*</span></label>
                                <input id="company-name" name="nama_perusahaan" type="text" autocomplete="organization" required maxlength="150" placeholder="PT / CV / UD ..." class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm outline-none placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="contact-name" class="mb-2 block text-sm font-semibold text-gray-700">Nama PIC <span class="text-red-500">*</span></label>
                                <input id="contact-name" name="nama_pic" type="text" autocomplete="name" required maxlength="100" placeholder="Nama penanggung jawab" class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm outline-none placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                                <input id="email" name="email" type="email" autocomplete="email" required maxlength="150" placeholder="email@perusahaan.com" class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm outline-none placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="phone" class="mb-2 block text-sm font-semibold text-gray-700">Nomor Telepon <span class="text-red-500">*</span></label>
                                <input id="phone" name="no_telepon" type="tel" autocomplete="tel" required maxlength="30" placeholder="08xx-xxxx-xxxx" class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm outline-none placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>
                    </section>

                    <section aria-labelledby="schedule-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <h2 id="schedule-heading" class="border-b border-gray-100 px-6 py-6 text-base font-bold text-gray-900">Jadwal Kunjungan</h2>
                        <div class="flex flex-col gap-6 p-6">
                            <div>
                                <label for="visit-date" class="mb-2 block text-sm font-semibold text-gray-700">Tanggal Kunjungan <span class="text-red-500">*</span></label>
                                <input id="visit-date" name="tanggal_kunjungan" type="date" min="{{ $today }}" required class="h-11 w-full min-w-0 rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <fieldset>
                                <legend class="mb-3 text-sm font-semibold text-gray-700">Pilih Slot Waktu <span class="text-red-500">*</span></legend>
                                <div class="grid grid-cols-1 gap-3 min-[400px]:grid-cols-2 sm:grid-cols-3">
                                    @forelse ($timeSlots as $timeSlot)
                                        <label class="relative">
                                            <input type="radio" name="jadwal_id" value="{{ $timeSlot['id'] }}" required @disabled($timeSlot['is_full']) class="peer sr-only">
                                            <span class="flex cursor-pointer flex-col gap-1 rounded-xl border border-gray-200 bg-white px-3 py-4 text-center text-sm text-gray-700 transition peer-checked:border-blue-700 peer-checked:bg-blue-700 peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-blue-600 peer-disabled:cursor-not-allowed peer-disabled:border-gray-100 peer-disabled:bg-gray-100 peer-disabled:text-gray-400">
                                                <span class="font-semibold">{{ $timeSlot['start'] }} – {{ $timeSlot['end'] }}</span>
                                                <span data-slot-status class="text-xs">{{ $timeSlot['is_full'] ? 'Penuh' : 'Tersedia' }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="col-span-full rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm leading-6 text-gray-500" role="status">Jadwal kunjungan belum tersedia. Silakan hubungi admin untuk informasi jadwal.</p>
                                    @endforelse
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-5 text-xs text-gray-500" aria-label="Keterangan slot waktu">
                                    <span class="inline-flex items-center gap-2"><span aria-hidden="true" class="size-3 rounded-sm bg-blue-700"></span>Dipilih</span>
                                    <span class="inline-flex items-center gap-2"><span aria-hidden="true" class="size-3 rounded-sm border border-gray-200 bg-gray-100"></span>Penuh</span>
                                    <span class="inline-flex items-center gap-2"><span aria-hidden="true" class="size-3 rounded-sm border border-gray-300 bg-white"></span>Tersedia</span>
                                </div>
                            </fieldset>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <label for="notes" class="block border-b border-gray-100 px-6 py-6 text-base font-bold text-gray-900">Catatan / Kebutuhan Tambahan <span class="text-sm font-normal text-gray-500">(opsional)</span></label>
                        <div class="p-6">
                            <textarea id="notes" name="catatan" rows="4" maxlength="2000" placeholder="Contoh: ingin melihat kondisi lantai, kapasitas listrik, dll." class="block min-h-28 w-full resize-y rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"></textarea>
                        </div>
                    </section>

                    <p id="booking-unavailable" class="text-sm leading-6 text-gray-500">Permohonan akan diperiksa admin. Pengajuan belum berarti gudang di-keep.</p>
                    <p id="booking-error" role="alert" tabindex="-1" hidden class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"></p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ $detailUrl }}" class="rounded-xl border border-gray-200 bg-white px-6 py-3 text-center text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Batal</a>
                        <button type="submit" disabled aria-describedby="booking-unavailable" id="confirm-appointment" class="rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white transition enabled:cursor-pointer enabled:hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60">Konfirmasi Temu Janji</button>
                    </div>
                </form>
            </div>
        </main>

            <dialog id="appointment-success" aria-labelledby="success-title" aria-describedby="success-description" class="fixed inset-0 m-auto w-[calc(100%-2.5rem)] max-w-lg rounded-2xl border border-gray-200 bg-white p-6 text-gray-900 shadow-xl backdrop:bg-slate-900/50 sm:p-8">
                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Menunggu Persetujuan</span>
                <h2 id="success-title" class="mt-4 text-xl font-bold">Permohonan Temu Janji Berhasil Dikirim</h2>
                <p id="success-description" class="mt-3 text-sm leading-6 text-gray-600">Permohonan temu janji Anda telah dikirim dan akan diperiksa terlebih dahulu oleh admin. Silakan menunggu konfirmasi dari admin.</p>
                <p class="mt-3 text-sm font-semibold">Kode Booking: <span id="booking-code"></span></p>
                <form method="dialog" class="mt-6 flex justify-end">
                    <button autofocus class="rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800">Mengerti</button>
                </form>
            </dialog>

        <script>
            const appointmentForm = document.getElementById('appointment-form');
            const confirmButton = document.getElementById('confirm-appointment');
            const successDialog = document.getElementById('appointment-success');
            const errorMessage = document.getElementById('booking-error');
            let submitting = false;
            let submitted = false;
            const requiredFields = [...appointmentForm.querySelectorAll('[required]')];
            const timeSlots = [...appointmentForm.querySelectorAll('input[name="jadwal_id"]')];

            function updateAppointmentForm() {
                timeSlots.forEach((slot) => {
                    slot.closest('label').querySelector('[data-slot-status]').textContent =
                        slot.disabled ? 'Penuh' : (slot.checked ? 'Dipilih' : 'Tersedia');
                });
                const validFields = requiredFields.every((field) =>
                    field.disabled || (field.validity.valid && field.value.trim() !== '')
                );
                confirmButton.disabled = submitting || submitted || !validFields ||
                    !timeSlots.some((slot) => slot.checked && !slot.disabled);
            }

            appointmentForm.addEventListener('input', updateAppointmentForm);
            appointmentForm.addEventListener('change', updateAppointmentForm);
            window.addEventListener('pageshow', updateAppointmentForm);
            appointmentForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                updateAppointmentForm();
                if (confirmButton.disabled || !appointmentForm.reportValidity()) {
                    return;
                }
                submitting = true;
                errorMessage.hidden = true;
                confirmButton.textContent = 'Mengirim…';
                updateAppointmentForm();
                try {
                    const response = await fetch(appointmentForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                        body: new FormData(appointmentForm),
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const message = response.status === 419
                            ? 'Sesi kedaluwarsa. Muat ulang halaman sebelum mengirim kembali.'
                            : (result.errors ? Object.values(result.errors).flat().join(' ') : result.message);
                        throw new Error(message || 'Permohonan belum dapat disimpan. Silakan coba kembali.');
                    }
                    if (response.status !== 201 || result.status !== 'pending' || !result.kode_booking) {
                        throw new Error('Konfirmasi penyimpanan tidak diterima. Hubungi admin sebelum mengirim ulang.');
                    }
                    submitted = true;
                    document.getElementById('booking-code').textContent = result.kode_booking;
                    successDialog.showModal();
                } catch (error) {
                    errorMessage.textContent = error instanceof TypeError
                        ? 'Koneksi terputus. Status pengiriman belum dapat dipastikan. Hubungi admin sebelum mengirim ulang.'
                        : error.message;
                    errorMessage.hidden = false;
                    errorMessage.focus();
                } finally {
                    submitting = false;
                    confirmButton.textContent = submitted ? 'Permohonan Terkirim' : 'Konfirmasi Temu Janji';
                    updateAppointmentForm();
                }
            });
            successDialog.addEventListener('close', () => {
                if (submitted) {
                    window.location.assign(@json($detailUrl));
                }
            });
            updateAppointmentForm();
        </script>
    </body>
</html>
