@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-4 text-sm text-muted">
        <p>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} booking</p>
        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="rounded-xl border border-line px-3 py-2 opacity-50">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-xl border border-line bg-white px-3 py-2 font-semibold text-primary hover:bg-primary-light">Sebelumnya</a>
            @endif
            <div class="hidden flex-wrap gap-2 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 py-2">{{ $element }}</span>
                    @else
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <span aria-current="page" class="rounded-xl border border-ink bg-ink px-3 py-2 font-semibold text-accent">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="rounded-xl border border-line bg-white px-3 py-2 hover:bg-primary-light">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-xl border border-line bg-white px-3 py-2 font-semibold text-primary hover:bg-primary-light">Berikutnya</a>
            @else
                <span aria-disabled="true" class="rounded-xl border border-line px-3 py-2 opacity-50">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
