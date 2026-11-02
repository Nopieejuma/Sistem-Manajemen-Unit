<footer class="bg-ink text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 md:grid-cols-[1.5fr_1fr_1fr] lg:px-10">
        <div>
            <x-brand />
            <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">Platform sederhana untuk menemukan gudang terbaik dan menjadwalkan kunjungan Anda.</p>
        </div>
        <div>
            <p class="text-sm font-bold">Navigasi</p>
            <div class="mt-4 flex flex-col gap-3 text-sm text-slate-300">
                <a href="{{ route('customer.home') }}#beranda" class="hover:text-white">Beranda</a>
                <a href="{{ route('customer.gudang.index') }}" class="hover:text-white">Daftar Gudang</a>
                <a href="{{ route('customer.home') }}#cara-pemesanan" class="hover:text-white">Cara Pemesanan</a>
            </div>
        </div>
        <div>
            <p class="text-sm font-bold">Butuh bantuan?</p>
            <p class="mt-4 text-sm leading-6 text-slate-300">Hubungi admin untuk informasi ketersediaan dan jadwal kunjungan gudang.</p>
        </div>
    </div>
    <div class="border-t border-white/10 px-5 py-5 text-center text-xs text-slate-400 sm:px-8">© {{ now()->year }} SARANA INDUSTRIAL POINT. <br>
         project by Khaila n Novia.</div>
</footer>
