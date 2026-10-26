<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <title>Cek Status Temu Janji — WAREHOUSE.BOOK</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-customer.navbar />

        <main class="min-h-screen bg-surface px-5 py-10 sm:px-8 sm:py-16">
            <div class="mx-auto flex max-w-2xl flex-col gap-6">
                <header>
                    <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Cek Status Temu Janji</h1>
                    <p class="mt-3 text-sm leading-7 text-muted">Masukkan kode booking dan email yang digunakan saat mengajukan temu janji untuk melihat status permohonan Anda.</p>
                </header>

                <form method="POST" action="{{ route('customer.cek-booking.store') }}" class="rounded-2xl border border-line bg-white p-6 shadow-sm sm:p-8">
                    @csrf
                    @if (isset($validationErrors) && $validationErrors->any())
                        <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <ul class="list-inside list-disc">
                                @foreach ($validationErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div>
                        <label for="kode-booking" class="mb-2 block text-sm font-semibold text-ink">Kode Booking</label>
                        <input id="kode-booking" name="kode_booking" type="text" required maxlength="30" autocomplete="off" spellcheck="false" placeholder="BK-..." class="w-full rounded-xl border border-line bg-white px-4 py-3 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>
                    <div class="mt-5">
                        <label for="email-customer" class="mb-2 block text-sm font-semibold text-ink">Email Customer</label>
                        <input id="email-customer" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="email@perusahaan.com" class="w-full rounded-xl border border-line bg-white px-4 py-3 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>
                    <button type="submit" class="mt-6 w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Cek Status</button>
                </form>

                @isset($notice)
                    <p role="alert" class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-800">{{ $notice }}</p>
                @endisset

                @if ($booking ?? null)
                    <section aria-labelledby="booking-result" class="rounded-2xl border border-line bg-white p-6 shadow-sm sm:p-8">
                        <h2 id="booking-result" class="text-lg font-bold text-ink">Ringkasan Temu Janji</h2>
                        <p class="mt-4 inline-flex rounded-full bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">{{ $statusLabel }}</p>
                        <p class="mt-3 text-sm leading-7 text-muted">{{ $statusMessage }}</p>
                        <dl class="mt-6 grid gap-5 border-t border-line pt-6 sm:grid-cols-2">
                            <div><dt class="text-sm text-muted">Kode Booking</dt><dd class="mt-1 break-all font-semibold text-ink">{{ $booking->kode_booking }}</dd></div>
                            <div><dt class="text-sm text-muted">Unit Gudang</dt><dd class="mt-1 font-semibold text-ink">Gudang {{ $booking->unit_code }}</dd></div>
                            <div><dt class="text-sm text-muted">Tanggal Kunjungan</dt><dd class="mt-1 font-semibold text-ink">{{ $booking->tanggal_kunjungan }}</dd></div>
                            <div><dt class="text-sm text-muted">Jam Kunjungan</dt><dd class="mt-1 font-semibold text-ink">{{ substr($booking->jam_mulai, 0, 5) }} – {{ substr($booking->jam_selesai, 0, 5) }}</dd></div>
                        </dl>
                        <p class="mt-6 text-xs leading-6 text-muted">Untuk melihat pembaruan status, lakukan pengecekan kembali menggunakan kode booking dan email Anda.</p>
                    </section>
                @endif
            </div>
        </main>

        <x-customer.footer />
    </body>
</html>
