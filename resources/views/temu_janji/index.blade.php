<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Temu Janji - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    @include('layouts.sidebar')

    <main class="flex-1 h-screen overflow-y-auto p-5 md:p-8">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900">Manajemen Temu Janji</h1>
            <p class="text-slate-500 text-xs">Kelola booking kunjungan dan permohonan pelanggan</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.temu-janji.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <label class="md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-slate-600">Cari booking</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kode booking, perusahaan, atau PIC" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <label>
                    <span class="mb-1 block text-xs font-semibold text-slate-600">Status</span>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs font-semibold text-slate-600">Tanggal kunjungan</span>
                    <input type="date" name="tanggal_kunjungan" value="{{ $filters['tanggal_kunjungan'] ?? '' }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex gap-2 md:col-span-4 md:justify-end">
                    <a href="{{ route('admin.temu-janji.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Terapkan Filter</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Daftar Booking</h2>
                <span class="text-xs text-slate-400">{{ $bookings->total() }} booking</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-slate-600">
                    <thead class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Booking</th>
                            <th class="px-5 py-3">Customer / PIC</th>
                            <th class="px-5 py-3">Gudang</th>
                            <th class="px-5 py-3">Jadwal Kunjungan</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bookings as $booking)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $booking->kode_booking }}</td>
                                <td class="px-5 py-4">
                                    <span class="block font-semibold text-slate-900">{{ $booking->nama_perusahaan }}</span>
                                    <span class="text-xs text-slate-500">{{ $booking->nama_pic }}</span>
                                </td>
                                <td class="px-5 py-4">{{ $booking->gudang?->unit_code ? 'Unit '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</td>
                                <td class="px-5 py-4 text-xs text-slate-500">
                                    <span class="block">{{ $booking->tanggal_kunjungan->format('d M Y') }}</span>
                                    {{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-5 py-4">
                                    @php($statusClasses = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-800', 'completed' => 'bg-blue-100 text-blue-800', 'canceled' => 'bg-slate-100 text-slate-600'])
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $booking->status }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.temu-janji.show', $booking) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Tidak ada booking yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bookings->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $bookings->links() }}</div>
            @endif
        </section>
    </main>
</body>
</html>
