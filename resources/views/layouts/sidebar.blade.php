<header class="sticky top-0 z-30 flex h-18 shrink-0 items-center justify-between gap-4 border-b border-accent/25 bg-ink px-5 text-white md:hidden">
    <a href="{{ route('admin.dashboard') }}" class="min-w-0 text-white" aria-label="SARANA INDUSTRIAL POINT Dashboard"><x-brand /></a>
    <button id="admin-menu-toggle" type="button" aria-label="Buka menu Admin" aria-controls="admin-mobile-navigation" aria-expanded="false" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-accent/40 text-accent hover:bg-accent/10">
        <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>
</header>
<aside class="sticky top-0 hidden h-dvh w-64 shrink-0 overflow-y-auto border-r border-accent/20 bg-ink text-white md:block">
    <x-admin-navigation />
</aside>
<dialog id="admin-mobile-navigation" aria-label="Navigasi Admin" class="admin-mobile-drawer">
    <div class="flex h-full flex-col bg-ink text-white">
        <button id="admin-menu-close" type="button" aria-label="Tutup menu Admin" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-lg text-accent hover:bg-accent/10">
            <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6" /></svg>
        </button>
        <x-admin-navigation />
    </div>
</dialog>
