<aside class="w-64 bg-white border-r border-slate-200/80 h-screen flex flex-col justify-between p-6 shrink-0 hidden md:flex">
    <div>
        <!-- Brand Header -->
        <div class="mb-8">
            <h1 class="font-bold text-blue-600 text-xl tracking-tight">WAREHOUSE.BOOK</h1>
            <span class="text-[11px] font-semibold tracking-wider uppercase text-slate-400 mt-1 block">ADMIN</span>
        </div>

        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                Dashboard
            </a>

            <!-- Data Gudang -->
            <a href="{{ route('admin.gudang.index') }}"
               class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.gudang.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                Data Gudang
            </a>

            <a href="{{ route('admin.jadwal.index') }}"
               class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.jadwal.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"></path>
                </svg>
                Jadwal &amp; Kuota
            </a>

            <!-- Temu Janji -->
            <a href="{{ route('admin.temu-janji.index') }}"
               class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.temu-janji.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Temu Janji
            </a>
        </nav>
    </div>

    <!-- Bottom Logout Section -->
    <div class="border-t border-slate-100 pt-4">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3.5 px-4 py-3 text-slate-600 hover:bg-red-50 hover:text-red-600 text-sm font-medium rounded-xl transition-all">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>
