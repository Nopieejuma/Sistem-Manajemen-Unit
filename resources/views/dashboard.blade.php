<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    @include('layouts.sidebar')

    <main class="flex-1 h-screen overflow-y-auto p-5 md:p-8">
        <x-admin-customer-link />
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-slate-500 text-sm">Ringkasan pengelolaan gudang</p>
        </div>

        @php
            $warehousePresentation = [
                'tersedia' => ['Tersedia', 'bg-emerald-50/60 border-emerald-100 text-emerald-800', 'text-emerald-600', 'bg-emerald-500'],
                'sedang di-keep' => ['Sedang di-keep', 'bg-amber-50/60 border-amber-100 text-amber-800', 'text-amber-600', 'bg-amber-500'],
                'tidak tersedia' => ['Tidak tersedia', 'bg-slate-100 border-slate-200 text-slate-700', 'text-slate-600', 'bg-slate-500'],
                'digunakan' => ['Digunakan', 'bg-blue-50/60 border-blue-100 text-blue-800', 'text-blue-600', 'bg-blue-600'],
            ];
            $bookingPresentation = [
                'pending' => ['Menunggu', 'bg-amber-50 border-amber-100 text-amber-700'],
                'approved' => ['Disetujui', 'bg-emerald-50 border-emerald-100 text-emerald-700'],
                'rejected' => ['Ditolak', 'bg-red-50 border-red-100 text-red-700'],
                'completed' => ['Selesai', 'bg-blue-50 border-blue-100 text-blue-700'],
                'canceled' => ['Dibatalkan', 'bg-slate-100 border-slate-200 text-slate-700'],
            ];
            $bookingStatusClasses = [
                'pending' => 'bg-amber-100/70 text-amber-800',
                'approved' => 'bg-emerald-100/70 text-emerald-800',
                'rejected' => 'bg-red-100/70 text-red-800',
                'completed' => 'bg-blue-100/70 text-blue-800',
                'canceled' => 'bg-slate-100 text-slate-600',
            ];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-blue-600 rounded-2xl p-6 text-white flex flex-col justify-between shadow-sm">
                    <span class="text-3xl font-bold">{{ $totalWarehouses }}</span>
                    <span class="text-sm font-medium opacity-90">Total Gudang</span>
                </div>

                @foreach ($warehousePresentation as $status => [$label, $cardClasses, $numberClasses])
                    <div class="rounded-2xl border p-6 flex flex-col justify-between {{ $cardClasses }}">
                        <span class="text-3xl font-bold {{ $numberClasses }}">{{ $warehouseCounts[$status] }}</span>
                        <span class="text-sm font-medium">Gudang {{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <h2 class="text-sm font-bold text-slate-800 mb-2">Distribusi Status Gudang</h2>
                <div class="relative w-full h-44 flex items-center justify-center">
                    <canvas id="warehouseChart" aria-label="Grafik distribusi status gudang"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs pt-4 border-t border-slate-100">
                    @foreach ($warehousePresentation as $status => [$label, $cardClasses, $numberClasses, $dotClass])
                        <div class="flex items-start gap-1.5">
                            <span class="w-2.5 h-2.5 mt-0.5 rounded-full {{ $dotClass }}"></span>
                            <span class="text-slate-600">{{ $label }} ({{ number_format($warehousePercentages[$status], 1, ',', '.') }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <section class="mb-8">
            <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Ringkasan Booking</h2>
                    <p class="text-xs text-slate-500">Jumlah booking berdasarkan status</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach ($bookingPresentation as $status => [$label, $classes])
                    <div class="rounded-xl border p-4 {{ $classes }}">
                        <span class="block text-2xl font-bold">{{ $bookingCounts[$status] }}</span>
                        <span class="text-xs font-semibold">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <span class="text-2xl font-bold text-slate-900">{{ $activeAppointmentsToday }}</span>
                <span class="block text-xs font-medium text-slate-500 mt-1">Janji temu aktif hari ini</span>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <span class="text-2xl font-bold text-slate-900">{{ $activeAppointmentsThisWeek }}</span>
                <span class="block text-xs font-medium text-slate-500 mt-1">Janji temu aktif minggu ini</span>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
            <div class="p-5 md:p-6 flex justify-between items-center border-b border-slate-100">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Temu Janji Terbaru</h2>
                    <p class="text-xs text-slate-500 mt-1">Lima booking terakhir yang tercatat</p>
                </div>
                <a href="{{ route('admin.temu-janji.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">Lihat Semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Booking</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Gudang</th>
                            <th class="px-6 py-4">Jadwal</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentBookings as $booking)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    <a href="{{ route('admin.temu-janji.show', $booking) }}" class="hover:text-blue-600">{{ $booking->kode_booking }}</a>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $booking->nama_perusahaan }}</td>
                                <td class="px-6 py-4">{{ $booking->gudang?->unit_code ? 'Gudang '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $booking->tanggal_kunjungan->format('d M Y') }}, {{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider {{ $bookingStatusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $booking->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">Belum ada data janji temu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const chartElement = document.getElementById('warehouseChart');
        const chartLabels = {{ Illuminate\Support\Js::from($warehouseChartLabels) }};
        const chartValues = {{ Illuminate\Support\Js::from($warehouseChartValues) }};

        new Chart(chartElement, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: ['#10b981', '#f59e0b', '#64748b', '#2563eb'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
