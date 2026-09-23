<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Temukan gudang yang tepat untuk bisnis Anda bersama WAREHOUSE.BOOK.">
        <title>WAREHOUSE.BOOK — Warehouse Booking</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-customer.navbar />

        <main>
            <section id="beranda" class="relative isolate overflow-hidden bg-[linear-gradient(118deg,#f7fbff_0%,#ffffff_56%,#edf6ff_100%)]">
                <div class="absolute -right-28 top-8 -z-10 h-96 w-96 rounded-full bg-primary/8 blur-3xl"></div>
                <div class="mx-auto grid min-h-[600px] max-w-7xl items-center gap-12 px-5 py-18 sm:px-8 lg:grid-cols-[1.04fr_.96fr] lg:px-10 lg:py-24">
                    <div>
                        <p class="inline-flex rounded-full border border-primary/15 bg-primary/8 px-3.5 py-2 text-xs font-bold tracking-[0.12em] text-primary">KAWASAN INDUSTRI TERPADU</p>
                        <h1 class="mt-6 max-w-2xl text-4xl font-bold leading-[1.12] tracking-[-0.045em] text-ink sm:text-5xl lg:text-6xl">Temukan Gudang yang <span class="text-primary">Tepat</span> untuk Bisnis Anda</h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-muted sm:text-lg">Lihat informasi gudang, pilih gudang yang Anda minati, dan jadwalkan kunjungan langsung dengan admin.</p>
                        <form class="mt-9 flex max-w-2xl flex-col gap-3 rounded-2xl border border-line bg-white p-3 shadow-[0_16px_40px_rgba(25,103,210,0.10)] sm:flex-row" action="#gudang" method="get">
                            <label for="warehouse-search" class="sr-only">Cari nama gudang atau blok</label>
                            <div class="flex flex-1 items-center gap-3 px-3">
                                <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m20 20-4.2-4.2"></path></svg>
                                <input id="warehouse-search" name="q" type="search" placeholder="Cari nama gudang atau blok..." class="w-full bg-transparent py-3 text-sm text-ink outline-none placeholder:text-muted">
                            </div>
                            <button type="submit" class="rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-white transition hover:bg-primary-dark">Cari Gudang</button>
                        </form>
                    </div>
                    <div class="relative mx-auto w-full max-w-xl">
                        <div class="absolute -inset-4 rounded-[2rem] bg-primary/10 blur-2xl"></div>
                        <img src="{{ asset('images/warehouses/warehouse-a5.png') }}" alt="Bangunan gudang modern di kawasan industri" class="relative h-[340px] w-full rounded-[1.75rem] object-cover shadow-2xl sm:h-[410px]">
                        <div class="absolute -bottom-5 -left-3 rounded-xl border border-white/70 bg-white/95 px-5 py-4 shadow-lg backdrop-blur sm:left-6">
                            <p class="text-2xl font-bold tracking-tight text-ink">113<span class="text-primary">+</span></p>
                            <p class="mt-1 text-xs font-medium text-muted">Pilihan gudang terdaftar</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-primary text-white">
                <div class="mx-auto grid max-w-7xl divide-y divide-white/20 px-5 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:px-8 lg:px-10">
                    <div class="py-8 sm:py-10"><p class="text-4xl font-bold tracking-tight">113</p><p class="mt-2 text-sm text-white/75">Total Gudang</p></div>
                    <div class="py-8 sm:px-10 sm:py-10"><p class="text-4xl font-bold tracking-tight">85</p><p class="mt-2 text-sm text-white/75">Gudang Tersedia</p></div>
                    <div class="py-8 sm:px-10 sm:py-10"><p class="text-4xl font-bold tracking-tight">± 200 Ha</p><p class="mt-2 text-sm text-white/75">Area Kawasan</p></div>
                </div>
            </section>

            <section id="gudang" class="bg-surface py-20 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="flex items-end justify-between gap-6">
                        <div><p class="text-sm font-bold tracking-[0.13em] text-primary">PILIHAN TERBAIK</p><h2 class="mt-3 text-3xl font-bold tracking-[-0.035em] text-ink sm:text-4xl">Gudang Tersedia</h2><p class="mt-3 text-muted">Pilih gudang yang sesuai dengan kebutuhan bisnis Anda.</p></div>
                        <a href="#" class="hidden shrink-0 text-sm font-bold text-primary hover:text-primary-dark sm:block">Lihat Semua <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($warehouses as $warehouse)
                            <x-customer.warehouse-card :warehouse="$warehouse" />
                        @endforeach
                    </div>
                    <a href="#" class="mt-8 inline-flex text-sm font-bold text-primary hover:text-primary-dark sm:hidden">Lihat Semua <span class="ml-1" aria-hidden="true">→</span></a>
                </div>
            </section>

            <section id="cara-pemesanan" class="py-20 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="max-w-2xl"><p class="text-sm font-bold tracking-[0.13em] text-primary">MUDAH &amp; CEPAT</p><h2 class="mt-3 text-3xl font-bold tracking-[-0.035em] text-ink sm:text-4xl">Cara Pemesanan</h2><p class="mt-3 leading-7 text-muted">Empat langkah sederhana untuk menemukan ruang yang tepat bagi operasional bisnis Anda.</p></div>
                    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([['01', 'Pilih Gudang', 'Temukan gudang yang sesuai dengan kebutuhan dan lokasi bisnis Anda.'], ['02', 'Keep Gudang', 'Pilih gudang yang diminati untuk mendapatkan informasi lebih lanjut.'], ['03', 'Temu Janji', 'Atur jadwal kunjungan langsung bersama tim admin kami.'], ['04', 'Datang ke PT', 'Kunjungi lokasi dan lanjutkan proses sewa dengan nyaman.']] as [$number, $title, $description])
                            <article class="rounded-2xl border border-line p-6 transition hover:border-primary/30 hover:shadow-lg"><p class="text-4xl font-bold tracking-[-0.06em] text-primary/20">{{ $number }}</p><h3 class="mt-10 text-lg font-bold text-ink">{{ $title }}</h3><p class="mt-3 text-sm leading-6 text-muted">{{ $description }}</p></article>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <x-customer.footer />
    </body>
</html>
