<header class="border-b border-line bg-white">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
        <a href="{{ route('customer.home') }}" class="text-lg font-extrabold tracking-[-0.06em] text-ink" aria-label="WAREHOUSE.BOOK Beranda">
            WAREHOUSE<span class="text-primary">.BOOK</span>
        </a>

        <nav class="hidden items-center gap-9 text-sm font-medium text-muted md:flex" aria-label="Navigasi utama">
            <a href="{{ route('customer.home') }}" @class(['border-b-2 border-primary py-7 text-primary' => request()->routeIs('customer.home'), 'py-7 transition hover:text-primary' => ! request()->routeIs('customer.home')])>Beranda</a>
            <a href="{{ route('customer.gudang.index') }}" @class(['border-b-2 border-primary py-7 text-primary' => request()->routeIs('customer.gudang.*'), 'py-7 transition hover:text-primary' => ! request()->routeIs('customer.gudang.*')])>Daftar Gudang</a>
            <a href="{{ route('customer.home') }}#cara-pemesanan" class="transition hover:text-primary">Cara Pemesanan</a>
            <a href="{{ route('customer.cek-booking.index') }}" @class(['border-b-2 border-primary py-7 text-primary' => request()->routeIs('customer.cek-booking.*'), 'py-7 transition hover:text-primary' => ! request()->routeIs('customer.cek-booking.*')])>Cek Booking</a>
        </nav>

        <a href="{{ route('admin.login') }}" class="rounded-lg border border-primary px-4 py-2.5 text-sm font-semibold text-primary">Admin</a>
    </div>
    <nav class="flex flex-wrap gap-5 border-t border-line px-5 py-3 text-sm font-semibold text-primary md:hidden" aria-label="Navigasi booking">
        <a href="{{ route('customer.gudang.index') }}" class="inline-flex py-1 hover:text-primary-dark">Daftar Gudang</a>
        <a href="{{ route('customer.cek-booking.index') }}" class="inline-flex py-1 hover:text-primary-dark">Cek Booking</a>
    </nav>
</header>
