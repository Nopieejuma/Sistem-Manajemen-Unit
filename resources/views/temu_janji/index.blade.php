<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Temu Janji - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-6">
            <h1 class="text-xl font-bold text-ink">Manajemen Temu Janji</h1>
            <p class="text-muted text-xs">Kelola booking kunjungan dan permohonan pelanggan</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="mb-5 rounded-2xl border border-line/80 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.temu-janji.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <label class="md:col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-muted">Cari booking</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Kode booking, perusahaan, atau PIC" class="w-full rounded-xl border border-line px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                </label>
                <label>
                    <span class="mb-1 block text-xs font-semibold text-muted">Status</span>
                    <select name="status" class="w-full rounded-xl border border-line px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs font-semibold text-muted">Tanggal kunjungan</span>
                    <input type="date" name="tanggal_kunjungan" value="{{ $filters['tanggal_kunjungan'] ?? '' }}" class="w-full rounded-xl border border-line px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                </label>
                <div class="flex gap-2 md:col-span-4 md:justify-end">
                    <a href="{{ route('admin.temu-janji.index') }}" class="rounded-xl border border-line px-4 py-2 text-xs font-semibold text-muted hover:bg-surface">Reset</a>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-dark">Terapkan Filter</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-line/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-sm font-bold text-ink">Daftar Booking</h2>
                <span class="text-xs text-muted">{{ $bookings->total() }} booking</span>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-muted">
                    <thead class="border-b border-line bg-surface/80 text-xs font-semibold uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-5 py-3">Booking</th>
                            <th class="px-5 py-3">Customer / PIC</th>
                            <th class="px-5 py-3">Gudang</th>
                            <th class="px-5 py-3">Jadwal Kunjungan</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-light">
                        @forelse ($bookings as $booking)
                            <tr class="transition hover:bg-surface/70">
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-ink">{{ $booking->kode_booking }}</td>
                                <td class="px-5 py-4">
                                    <span class="block font-semibold text-ink">{{ $booking->nama_perusahaan }}</span>
                                    <span class="text-xs text-muted">{{ $booking->nama_pic }}</span>
                                </td>
                                <td class="px-5 py-4">{{ $booking->gudang?->unit_code ? 'Unit '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</td>
                                <td class="px-5 py-4 text-xs text-muted">
                                    <span class="block">{{ $booking->tanggal_kunjungan->format('d M Y') }}</span>
                                    {{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-5 py-4">
                                    @php($statusClasses = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-800', 'completed' => 'bg-primary-light text-primary-dark', 'canceled' => 'bg-primary-light text-muted'])
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold uppercase {{ $statusClasses[$booking->status] ?? 'bg-primary-light text-muted' }}">{{ $booking->status }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.temu-janji.show', $booking) }}" class="rounded-lg border border-line px-3 py-2 text-xs font-semibold text-primary-dark hover:bg-primary-light">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-muted">Tidak ada booking yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bookings->hasPages())
                <div class="border-t border-line px-5 py-4">{{ $bookings->links('components.admin-pagination') }}</div>
            @endif
        </section>
    </main>
</body>
</html>
