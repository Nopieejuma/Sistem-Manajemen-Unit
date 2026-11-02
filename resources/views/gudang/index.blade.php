<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Gudang - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">

    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
            <div>
                <h1 class="text-xl font-bold text-ink">Katalog Gudang</h1>
                <p class="text-muted text-xs">Daftar unit gudang, harga sewa, dan spesifikasi fasilitas</p>
            </div>
            <a href="{{ route('admin.gudang.create') }}" class="bg-primary hover:bg-primary-dark text-white font-semibold px-3.5 py-2 rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                <span>+</span> Tambah Gudang
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
            @forelse($gudang as $item)
                @php
                    // Pengaman jika data foto tersimpan sebagai string JSON atau Array
                    $fotoList = is_array($item->foto) ? $item->foto : json_decode($item->foto, true) ?? [];
                @endphp

                <div class="bg-white rounded-xl border border-line/80 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- SLIDER CONTAINER -->
                        <div class="relative h-44 bg-primary-light group overflow-hidden">
                            @if(count($fotoList) > 0)
                                <!-- Track Foto Horizontal -->
                                <div id="slider-{{ $item->id }}" class="flex h-full overflow-x-auto snap-x snap-mandatory scroll-smooth no-scrollbar">
                                    @foreach($fotoList as $idx => $fotoPath)
                                        <div class="w-full h-full flex-shrink-0 snap-start relative">
                                            <img src="{{ $item->photoUrl($fotoPath) }}" alt="Foto {{ $idx + 1 }}" class="w-full h-full object-cover">
                                            <span class="absolute bottom-2 right-2 bg-ink/70 backdrop-blur-sm text-white text-xs px-2 py-0.5 rounded-md font-semibold">
                                                {{ $idx + 1 }}/{{ count($fotoList) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Tombol Navigasi Kiri & Kanan (Muncul jika foto > 1) -->
                                @if(count($fotoList) > 1)
                                    <button onclick="slide('slider-{{ $item->id }}', 'left')" class="absolute left-2 top-1/2 -translate-y-1/2 bg-ink/60 hover:bg-ink/90 text-white w-7 h-7 rounded-full flex items-center justify-center text-sm opacity-0 group-hover:opacity-100 transition z-10">
                                        ‹
                                    </button>
                                    <button onclick="slide('slider-{{ $item->id }}', 'right')" class="absolute right-2 top-1/2 -translate-y-1/2 bg-ink/60 hover:bg-ink/90 text-white w-7 h-7 rounded-full flex items-center justify-center text-sm opacity-0 group-hover:opacity-100 transition z-10">
                                        ›
                                    </button>
                                @endif
                            @else
                                <div class="w-full h-full flex items-center justify-center text-muted text-xs italic">
                                    Tanpa Foto
                                </div>
                            @endif

                            <div class="absolute top-2 left-2 bg-primary text-white text-xs font-bold px-2 py-0.5 rounded-md shadow-sm z-10">
                                {{ $item->tipe_gudang }}
                            </div>
                        </div>

                        <!-- Info Gudang -->
                        <div class="p-3.5 space-y-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-ink text-sm">Unit {{ $item->unit_code }}</h3>
                                    <span class="text-xs font-medium text-muted">Blok {{ $item->blok }}</span>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-bold uppercase {{ $item->status === 'tersedia' ? 'bg-emerald-100 text-emerald-800' : ($item->status === 'sedang di-keep' ? 'bg-amber-100 text-amber-800' : 'bg-primary-light text-muted') }}">{{ $item->status }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-muted block uppercase font-bold">Sewa per tahun</span>
                                    <span class="text-xs font-bold text-emerald-600">
                                        {{ $item->harga_sewa > 0 ? 'Rp ' . number_format($item->harga_sewa, 0, ',', '.') : 'Harga belum tersedia' }}
                                    </span>
                                </div>
                            </div>

                            <hr class="border-line">

                            <div class="grid grid-cols-2 gap-1.5 text-xs">
                                <div class="bg-surface p-2 rounded-lg border border-line">
                                    <span class="text-muted block font-medium">Utama</span>
                                    <span class="font-bold text-ink text-xs">{{ number_format($item->total_luas_bangunan ?? 0, 2) }} m²</span>
                                </div>
                                <div class="bg-surface p-2 rounded-lg border border-line">
                                    <span class="text-muted block font-medium">Tinggi</span>
                                    <span class="font-bold text-ink text-xs">{{ number_format($item->tinggi, 2) }} M</span>
                                </div>
                            </div>

                            @php $fasilitas = is_array($item->fasilitas) ? $item->fasilitas : json_decode($item->fasilitas, true) ?? []; @endphp
                            @if(count($fasilitas) > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($fasilitas as $fas)
                                        <span class="text-xs bg-primary-light text-muted px-1.5 py-0.5 rounded font-medium border border-line/50">
                                            ✓ {{ $fas }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 pt-0 flex gap-1.5">
                        <a href="{{ route('admin.gudang.edit', $item->id) }}" class="flex-1 text-center py-1.5 px-2 rounded-lg border border-line text-ink font-semibold text-xs hover:bg-surface transition">
                            Edit
                        </a>
                        <form action="{{ route('admin.gudang.destroy', $item->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Hapus gudang ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-1.5 px-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-xs transition">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-xl border border-line/80">
                    <p class="text-muted text-xs font-medium">Belum ada data gudang yang ditambahkan.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- SCRIPT GESER SLIDER FOTO -->
    <script>
        function slide(sliderId, direction) {
            const container = document.getElementById(sliderId);
            const width = container.clientWidth;
            if (direction === 'left') {
                container.scrollBy({ left: -width, behavior: 'smooth' });
            } else {
                container.scrollBy({ left: width, behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
