<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail {{ $booking->kode_booking }} - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    @include('layouts.sidebar')

    <main class="flex-1 h-screen overflow-y-auto p-5 md:p-8">
        <x-admin-customer-link />
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('admin.temu-janji.index') }}" class="mb-3 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-700">← Kembali ke daftar booking</a>
                <h1 class="text-xl font-bold text-slate-900">Detail Booking</h1>
                <p class="font-mono text-xs text-slate-500">{{ $booking->kode_booking }}</p>
            </div>
            @php($statusClasses = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-800', 'completed' => 'bg-blue-100 text-blue-800', 'canceled' => 'bg-slate-100 text-slate-600'])
            <span class="rounded-full px-3 py-1.5 text-xs font-bold uppercase {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $booking->status }}</span>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <section class="space-y-5 xl:col-span-2">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">Informasi Customer</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-400">Nama Perusahaan</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $booking->nama_perusahaan }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Nama PIC</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $booking->nama_pic }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Email</dt><dd class="mt-1 break-all text-sm text-slate-700">{{ $booking->email }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Nomor Telepon</dt><dd class="mt-1 text-sm text-slate-700">{{ $booking->no_telepon }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">Kunjungan</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-400">Gudang</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $booking->gudang?->unit_code ? 'Unit '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</dd><dd class="text-xs text-slate-500">{{ $booking->gudang?->blok ? 'Blok '.$booking->gudang->blok : '' }}{{ $booking->gudang?->tipe_gudang ? ' · '.$booking->gudang->tipe_gudang : '' }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Tanggal Kunjungan</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $booking->tanggal_kunjungan->format('d F Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Jam</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Kapasitas Slot</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $usedCapacity }}{{ $capacityLimit !== null ? ' / '.$capacityLimit.' booking' : ' booking' }}</dd><dd class="text-xs text-slate-500">Status pending, approved, dan completed dihitung.</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-400">Catatan Customer</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $booking->catatan ?: 'Tidak ada catatan.' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="h-fit rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="mb-1 text-sm font-bold text-slate-900">Tindakan Admin</h2>
                <p class="mb-4 text-xs text-slate-500">Perubahan status disimpan secara aman.</p>
                <div class="space-y-2">
                    @foreach (['approve' => ['Setujui Booking', 'bg-emerald-600 hover:bg-emerald-700'], 'reject' => ['Tolak Booking', 'bg-red-600 hover:bg-red-700'], 'complete' => ['Tandai Selesai', 'bg-blue-600 hover:bg-blue-700'], 'cancel' => ['Batalkan Booking', 'bg-slate-700 hover:bg-slate-800']] as $action => [$label, $classes])
                        @if (in_array($action, $allowedActions, true))
                            <form method="POST" action="{{ route('admin.temu-janji.'.$action, $booking) }}" onsubmit="return confirm('{{ $label }}?')">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-xs font-semibold text-white transition {{ $classes }}">{{ $label }}</button>
                            </form>
                        @endif
                    @endforeach
                    @if (count($allowedActions) === 0)
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">Status final. Tidak ada tindakan lanjutan.</p>
                    @endif
                </div>
            </section>
        </div>
    </main>
</body>
</html>
