<div class="flex h-full flex-col gap-8 px-6 py-7">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex text-white" aria-label="SARANA INDUSTRIAL POINT Dashboard">
            <x-brand />
        </a>
        <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-accent">Admin Portal</p>
    </div>
    <nav aria-label="Menu Admin" class="flex flex-col gap-2">
        @foreach ([
            ['admin.dashboard', 'admin.dashboard', 'Dashboard', 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],
            ['admin.gudang.index', 'admin.gudang.*', 'Data Gudang', 'M3 21V8l9-5 9 5v13 M3 9l9 5 9-5 M12 14v7 M7 11v7 M17 11v7'],
            ['admin.jadwal.index', 'admin.jadwal.*', 'Jadwal & Kuota', 'M8 3v4 M16 3v4 M4 11h16 M5 5h14a1 1 0 011 1v14H4V6a1 1 0 011-1 M8 15h3 M14 15h2'],
            ['admin.temu-janji.index', 'admin.temu-janji.*', 'Temu Janji', 'M8 7h8 M8 11h5 M5 3h14v14H9l-4 4V3'],
        ] as [$routeName, $activePattern, $label, $iconPath])
            <a href="{{ route($routeName) }}" @if (request()->routeIs($activePattern)) aria-current="page" @endif
               @class(['admin-nav-link', 'admin-nav-link-active' => request()->routeIs($activePattern)])>
                <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $iconPath }}" /></svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>
    <div class="mt-auto border-t border-accent/20 pt-5">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="admin-nav-link w-full text-left">
                <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 4H4v16h5 M13 8l4 4-4 4 M8 12h13" /></svg>
                Logout
            </button>
        </form>
    </div>
</div>
