<header class="border-b border-accent/30 bg-ink text-white">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
        <a href="{{ route('customer.home') }}" class="min-w-0 text-white" aria-label="SARANA INDUSTRIAL POINT Beranda">
            <x-brand />
        </a>

        <nav class="hidden items-center gap-9 text-sm font-medium text-stone-200 md:flex" aria-label="Navigasi utama">
            <a href="{{ route('customer.home') }}" @class(['border-b-2 border-accent py-7 text-accent' => request()->routeIs('customer.home'), 'py-7 transition hover:text-accent' => ! request()->routeIs('customer.home')])>Beranda</a>
            <a href="{{ route('customer.gudang.index') }}" @class(['border-b-2 border-accent py-7 text-accent' => request()->routeIs('customer.gudang.*'), 'py-7 transition hover:text-accent' => ! request()->routeIs('customer.gudang.*')])>Daftar Gudang</a>
            <a href="{{ route('customer.home') }}#cara-pemesanan" class="transition hover:text-accent">Cara Pemesanan</a>
            <a href="{{ route('customer.cek-booking.index') }}" @class(['border-b-2 border-accent py-7 text-accent' => request()->routeIs('customer.cek-booking.*'), 'py-7 transition hover:text-accent' => ! request()->routeIs('customer.cek-booking.*')])>Cek Booking</a>
        </nav>

        <a href="{{ route('admin.login') }}" class="rounded-lg border border-accent px-4 py-2.5 text-sm font-semibold text-accent">Admin</a>
    </div>
    <nav class="flex flex-wrap gap-5 border-t border-line px-5 py-3 text-sm font-semibold text-accent md:hidden" aria-label="Navigasi booking">
        <a href="{{ route('customer.gudang.index') }}" class="inline-flex py-1 hover:text-white">Daftar Gudang</a>
        <a href="{{ route('customer.cek-booking.index') }}" class="inline-flex py-1 hover:text-white">Cek Booking</a>
    </nav>
</header>
