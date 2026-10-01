<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Daftar gudang WAREHOUSE.BOOK.">
        <title>Daftar Gudang — WAREHOUSE.BOOK</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-customer.navbar />

        <main class="min-h-screen bg-surface py-8 sm:py-10">
            <section class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <form action="{{ route('customer.gudang.index') }}" class="flex flex-wrap items-center gap-3" method="GET">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">

                    <div class="relative min-w-0 grow basis-72 xl:basis-[36rem]">
                        <label for="search" class="sr-only">Cari nama gudang atau blok</label>
                        <svg class="pointer-events-none absolute left-5 top-1/2 size-5 -translate-y-1/2 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m20 20-4.2-4.2"></path></svg>
                        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari nama gudang atau blok..." class="w-full rounded-2xl border border-line bg-white py-3.5 pl-12 pr-4 text-sm text-ink outline-none transition placeholder:text-muted focus:border-primary focus:ring-2 focus:ring-primary/15">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-muted">Status:</span>
                        @foreach ([['', 'Semua'], ['tersedia', 'Tersedia'], ['sedang di-keep', 'Sedang Di-keep'], ['tidak tersedia', 'Tidak Tersedia']] as [$value, $label])
                            <a href="{{ route('customer.gudang.index', array_filter(['search' => $search, 'blok' => $filters['blok'], 'tipe_gudang' => $filters['tipe_gudang'], 'status' => $value], fn (string $filter): bool => $filter !== '')) }}" @class(['rounded-xl px-4 py-3 text-sm font-semibold transition' => true, 'bg-primary text-white shadow-sm' => $filters['status'] === $value, 'border border-line bg-white text-muted hover:border-primary/30 hover:text-primary' => $filters['status'] !== $value])>{{ $label }}</a>
                        @endforeach
                    </div>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-line bg-white px-4 py-3 text-sm font-semibold text-muted transition hover:border-primary/30 hover:text-primary">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7z"></path></svg>
                            Filter
                        </summary>
                        <div class="absolute right-0 z-10 mt-2 w-72 rounded-2xl border border-line bg-white p-4 shadow-xl">
                            <div>
                                <label for="blok" class="text-sm font-semibold text-ink">Blok</label>
                                <select id="blok" name="blok" class="mt-2 w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                                    <option value="">Semua blok</option>
                                    @foreach ($blokOptions as $option)
                                        <option value="{{ $option }}" @selected($filters['blok'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-4">
                                <label for="tipe_gudang" class="text-sm font-semibold text-ink">Tipe gudang</label>
                                <select id="tipe_gudang" name="tipe_gudang" class="mt-2 w-full rounded-xl border border-line bg-white px-3 py-2.5 text-sm text-ink outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
                                    <option value="">Semua tipe</option>
                                    @foreach ($tipeGudangOptions as $option)
                                        <option value="{{ $option }}" @selected($filters['tipe_gudang'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="mt-4 w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary-dark">Terapkan Filter</button>
                        </div>
                    </details>
                </form>

                <p class="mt-5 text-sm text-muted">{{ $warehouseGroups->count() }} kelompok gudang ditemukan</p>

                @if ($warehouseGroups->isEmpty())
                    <div class="mt-6 rounded-2xl border border-dashed border-line bg-white px-6 py-16 text-center">
                        <p class="text-lg font-bold text-ink">Gudang tidak ditemukan</p>
                        <p class="mt-2 text-sm text-muted">Coba ubah kata kunci atau pilihan filter Anda.</p>
                    </div>
                @else
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($warehouseGroups as $group)
                            @php($warehouse = $group['warehouse'])
                            <article class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_12px_32px_rgba(23,32,51,0.05)] transition hover:-translate-y-1 hover:shadow-[0_18px_38px_rgba(23,32,51,0.10)]">
                                @if ($photoUrl = $warehouse->firstPhotoUrl())
                                    <img src="{{ $photoUrl }}" alt="Gudang {{ $warehouse->unit_code }}" class="h-52 w-full object-cover" referrerpolicy="no-referrer">
                                @else
                                    <img src="{{ asset('images/warehouses/warehouse-a5.png') }}" alt="Foto representatif gudang" class="h-52 w-full object-cover">
                                @endif
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h2 class="text-xl font-bold tracking-tight text-ink">{{ $group['unitCount'] === 1 ? "Gudang {$warehouse->unit_code}" : "Gudang Blok {$warehouse->blok}" }}</h2>
                                            <p class="mt-1 text-sm text-muted">{{ $warehouse->tipe_gudang ?? 'Tipe belum tersedia' }} · {{ $group['unitCount'] }} unit</p>
                                        </div>
                                        @if ($group['status'])
                                            <span class="rounded-full bg-[#e8f7ee] px-3 py-1.5 text-[11px] font-bold tracking-wide text-[#167548]">{{ strtoupper($group['status']) }}</span>
                                        @endif
                                    </div>
                                    @if ($group['facilities']->isNotEmpty())
                                        <div class="mt-5 flex flex-wrap gap-2">
                                            @foreach ($group['facilities']->take(2) as $facility)
                                                <span class="rounded-md border border-line bg-surface px-2.5 py-1.5 text-xs font-medium text-muted">{{ $facility }}</span>
                                            @endforeach
                                            @if ($group['facilities']->count() > 2)
                                                <span class="self-center text-sm font-semibold text-muted">+{{ $group['facilities']->count() - 2 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="mt-5 flex items-end justify-between border-t border-line pt-4">
                                        <p class="text-base font-semibold text-ink">{{ $warehouse->grand_total ? number_format((float) $warehouse->grand_total, 0, ',', '.') : ($warehouse->total_luas_bangunan ? number_format((float) $warehouse->total_luas_bangunan, 0, ',', '.') : '—') }} m²</p>
                                        <div class="text-right">
                                            @if ((float) $warehouse->harga_sewa > 0)
                                                <p class="text-base font-bold text-ink">Rp{{ number_format((float) $warehouse->harga_sewa, 0, ',', '.') }} <span class="font-medium text-muted">/ bln</span></p>
                                            @else
                                                <p class="text-sm text-muted">Harga sewa belum tersedia</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($group['availableUnitCount'] > 0)
                                        <a href="{{ route('customer.gudang.show', ['group' => $warehouse->groupIdentifier()]) }}" class="mt-5 block w-full rounded-xl bg-primary px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-primary-dark">Lihat Detail</a>
                                    @else
                                        <span aria-disabled="true" class="mt-5 block w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-slate-400">Tidak Tersedia</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>

        <x-customer.footer />
    </body>
</html>
