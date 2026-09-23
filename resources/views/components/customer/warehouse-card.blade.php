@props(['warehouse'])

<article {{ $attributes->merge(['class' => 'group overflow-hidden rounded-2xl border border-line bg-white shadow-[0_12px_32px_rgba(23,32,51,0.05)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_38px_rgba(23,32,51,0.10)]']) }}>
    <div class="relative h-52 overflow-hidden">
        <img src="{{ asset($warehouse['image']) }}" alt="{{ $warehouse['name'] }} di {{ $warehouse['block'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        <span class="absolute left-4 top-4 rounded-full bg-[#e8f7ee] px-3 py-1.5 text-[11px] font-bold tracking-wide text-[#167548]">TERSEDIA</span>
    </div>

    <div class="p-5">
        <p class="text-sm text-muted">{{ $warehouse['block'] }}</p>
        <h3 class="mt-1 text-xl font-bold tracking-tight text-ink">{{ $warehouse['name'] }}</h3>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($warehouse['facilities'] as $facility)
                <span class="rounded-md bg-surface px-2.5 py-1.5 text-xs font-medium text-muted">{{ $facility }}</span>
            @endforeach
        </div>

        <div class="mt-5 flex items-end justify-between border-t border-line pt-4">
            <div>
                <p class="text-sm font-semibold text-ink">{{ $warehouse['area'] }}</p>
                <p class="mt-1 text-sm font-bold text-primary">{{ $warehouse['price'] }} <span class="font-medium text-muted">/ bulan</span></p>
            </div>
            <a href="#" class="text-sm font-semibold text-primary transition hover:text-primary-dark">Lihat Detail <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>
