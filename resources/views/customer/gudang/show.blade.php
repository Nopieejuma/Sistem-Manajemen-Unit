<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Detail gudang {{ $selectedUnit->unit_code }} di WAREHOUSE.BOOK.">
        <title>Gudang {{ $selectedUnit->unit_code }} — WAREHOUSE.BOOK</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <x-customer.navbar />

        <main class="min-h-screen bg-surface py-8 sm:py-10">
            <section class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <nav aria-label="Breadcrumb" class="text-sm text-muted">
                    <a href="{{ route('customer.gudang.index') }}" class="hover:text-primary">Daftar Gudang</a>
                    <span class="mx-2">/</span>
                    <span class="font-semibold text-ink">Gudang {{ $selectedUnit->unit_code }}</span>
                </nav>

                <div class="mt-8 grid items-start gap-10 lg:grid-cols-2 lg:gap-12">
                    <section aria-label="Gallery foto gudang">
                        @php($developmentGalleryImages = [
                            asset('images/warehouses/warehouse-a5.png'),
                            asset('images/warehouses/warehouse-exterior.png'),
                            asset('images/warehouses/warehouse-loading-dock.png'),
                            asset('images/warehouses/warehouse-storage.png'),
                        ])
                        @php($galleryImages = match (count($photoUrls)) {
                            0 => $developmentGalleryImages,
                            1 => array_merge($photoUrls, array_slice($developmentGalleryImages, 1)),
                            default => $photoUrls,
                        })
                        <img data-gallery-main src="{{ $galleryImages[0] }}" alt="Gudang {{ $selectedUnit->unit_code }}" class="h-72 w-full rounded-2xl object-cover shadow-[0_12px_32px_rgba(23,32,51,0.10)] sm:h-96 lg:h-[450px]" @if ($photoUrls !== []) referrerpolicy="no-referrer" @endif>

                        <div class="mt-3 grid grid-cols-4 gap-3">
                            @foreach ($galleryImages as $image)
                                <button type="button" data-gallery-thumbnail data-image-url="{{ $image }}" @class(['overflow-hidden rounded-xl border-2 transition', 'border-primary' => $loop->first, 'border-transparent opacity-70 hover:opacity-100' => ! $loop->first]) aria-label="Tampilkan foto gudang {{ $loop->iteration }}">
                                    <img src="{{ $image }}" alt="Thumbnail gudang {{ $selectedUnit->unit_code }}" class="h-24 w-full object-cover sm:h-[100px]" @if ($photoUrls !== []) referrerpolicy="no-referrer" @endif>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section>
                        <div class="flex items-start justify-between gap-4">
                            <h1 class="text-3xl font-bold tracking-[-0.035em] text-ink sm:text-4xl">Gudang {{ $selectedUnit->unit_code }}</h1>
                            @if ($selectedUnit->status)
                                <span class="shrink-0 rounded-full bg-[#e8f7ee] px-3 py-1.5 text-[11px] font-bold tracking-wide text-[#167548]">{{ strtoupper($selectedUnit->status) }}</span>
                            @endif
                        </div>

                        <dl class="mt-10 grid grid-cols-2 gap-x-8 gap-y-8 text-sm">
                            <div><dt class="text-muted">Kode Gudang</dt><dd class="mt-1 text-lg font-bold text-ink">{{ $selectedUnit->unit_code }}</dd></div>
                            <div><dt class="text-muted">Blok</dt><dd class="mt-1 text-lg font-bold text-ink">{{ $selectedUnit->blok ?? '—' }}</dd></div>
                            <div><dt class="text-muted">Luas Kavling</dt><dd class="mt-1 text-lg font-bold text-ink">{{ $selectedUnit->luas_kavling ? number_format((float) $selectedUnit->luas_kavling, 0, ',', '.') : '—' }} m²</dd></div>
                            <div><dt class="text-muted">Luas Bangunan</dt><dd class="mt-1 text-lg font-bold text-ink">{{ $selectedUnit->total_luas_bangunan ? number_format((float) $selectedUnit->total_luas_bangunan, 0, ',', '.') : '—' }} m²</dd></div>
                            <div><dt class="text-muted">Grand Total</dt><dd class="mt-1 text-lg font-bold text-ink">{{ $selectedUnit->grand_total ? number_format((float) $selectedUnit->grand_total, 0, ',', '.') : '—' }} m²</dd></div>
                            <div class="rounded-2xl bg-primary-light p-4"><dt class="text-primary">Harga Sewa</dt><dd class="mt-1 text-lg font-bold text-primary">@if ((float) $selectedUnit->harga_sewa > 0) Rp{{ number_format((float) $selectedUnit->harga_sewa, 0, ',', '.') }} <span class="text-sm font-medium">/ bulan</span> @else Harga belum tersedia @endif</dd></div>
                        </dl>

                        <section class="mt-10 border-t border-line pt-8">
                            <h2 class="text-sm font-bold tracking-[0.08em] text-muted">PILIH UNIT GUDANG</h2>
                            @if ($unitSelectionError)
                                <p role="alert" class="mt-3 text-sm font-medium text-red-600">{{ $unitSelectionError }}</p>
                            @endif
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($units as $unit)
                                    <a href="{{ route('customer.gudang.show', ['group' => $groupIdentifier, 'unit' => $unit->id]) }}" @class(['rounded-xl border px-4 py-2.5 text-sm font-bold transition', 'border-primary bg-primary text-white' => ($hasSelectedUnit && $selectedUnit->is($unit)), 'border-line bg-white text-ink hover:border-primary hover:text-primary' => ! ($hasSelectedUnit && $selectedUnit->is($unit)) && $unit->status === 'tersedia', 'pointer-events-none cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400' => ! ($hasSelectedUnit && $selectedUnit->is($unit)) && $unit->status !== 'tersedia']) aria-disabled="{{ $unit->status === 'tersedia' ? 'false' : 'true' }}">{{ $unit->unit_code }}</a>
                                @endforeach
                            </div>
                        </section>

                        <section class="mt-10 border-t border-line pt-8">
                            <h2 class="text-sm font-bold tracking-[0.08em] text-muted">DESKRIPSI</h2>
                            <p class="mt-3 leading-7 text-muted">Deskripsi gudang belum tersedia.</p>
                        </section>

                        <section class="mt-8">
                            <h2 class="text-sm font-bold tracking-[0.08em] text-muted">FASILITAS GUDANG</h2>
                            @if (filled($selectedUnit->fasilitas))
                                <div class="mt-4 flex flex-wrap gap-3">
                                    @foreach ($selectedUnit->fasilitas as $facility)
                                        <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800"><span aria-hidden="true">✓</span>{{ $facility }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 text-sm text-muted">Informasi fasilitas belum tersedia.</p>
                            @endif
                        </section>

                        <section class="mt-8 rounded-2xl border border-line bg-white p-5">
                            <h2 class="text-sm font-bold tracking-[0.08em] text-muted">DOKUMEN GUDANG</h2>
                            <p class="mt-4 text-sm text-muted">Dokumen gudang belum tersedia.</p>
                        </section>

                        <div id="keep-gudang" class="mt-8 border-t border-line pt-8">
                            @if ($selectedUnit->status === 'tersedia')
                                <a href="{{ route('customer.temu-janji.create', ['group' => $groupIdentifier, 'unit' => $hasSelectedUnit ? $selectedUnit->id : null]) }}" data-unit-id="{{ $selectedUnit->id }}" class="block w-full rounded-2xl bg-primary px-5 py-4 text-center text-sm font-bold text-white transition hover:bg-primary-dark">KEEP GUDANG &amp; BUAT TEMU JANJI</a>
                            @else
                                <span aria-disabled="true" class="block w-full cursor-not-allowed rounded-2xl bg-slate-200 px-5 py-4 text-center text-sm font-bold text-slate-500">UNIT TIDAK TERSEDIA</span>
                            @endif
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <x-customer.footer />

        <script>
            document.querySelectorAll('[data-gallery-thumbnail]').forEach((thumbnail) => {
                thumbnail.addEventListener('click', () => {
                    document.querySelector('[data-gallery-main]').src = thumbnail.dataset.imageUrl;
                    document.querySelectorAll('[data-gallery-thumbnail]').forEach((item) => {
                        item.classList.remove('border-primary');
                        item.classList.add('border-transparent', 'opacity-70');
                    });
                    thumbnail.classList.add('border-primary');
                    thumbnail.classList.remove('border-transparent', 'opacity-70');
                });
            });
        </script>
    </body>
</html>
