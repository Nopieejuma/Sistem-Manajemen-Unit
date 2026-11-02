<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-ink">Dashboard</h1>
            <p class="text-muted text-sm">Ringkasan pengelolaan gudang</p>
        </div>

        @php
            $warehousePresentation = [
                'tersedia' => ['Tersedia', 'bg-emerald-50/60 border-emerald-100 text-emerald-800', 'text-emerald-600', 'bg-emerald-500'],
                'sedang di-keep' => ['Sedang di-keep', 'bg-amber-50/60 border-amber-100 text-amber-800', 'text-amber-600', 'bg-accent'],
                'tidak tersedia' => ['Tidak tersedia', 'bg-primary-light border-line text-ink', 'text-muted', 'bg-muted'],
                'digunakan' => ['Digunakan', 'bg-white border-primary/25 text-primary-dark', 'text-primary', 'bg-primary'],
            ];
            $bookingPresentation = [
                'pending' => ['Menunggu', 'bg-amber-50 border-amber-100 text-amber-700'],
                'approved' => ['Disetujui', 'bg-emerald-50 border-emerald-100 text-emerald-700'],
                'rejected' => ['Ditolak', 'bg-red-50 border-red-100 text-red-700'],
                'completed' => ['Selesai', 'bg-white border-line text-primary-dark'],
                'canceled' => ['Dibatalkan', 'bg-primary-light border-line text-ink'],
            ];
            $bookingStatusClasses = [
                'pending' => 'bg-amber-100/70 text-amber-800',
                'approved' => 'bg-emerald-100/70 text-emerald-800',
                'rejected' => 'bg-red-100/70 text-red-800',
                'completed' => 'bg-primary-light/70 text-primary-dark',
                'canceled' => 'bg-primary-light text-muted',
            ];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-ink rounded-2xl border border-accent/30 p-6 text-white flex flex-col justify-between shadow-sm">
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

            <div class="bg-white rounded-2xl p-6 border border-line/80 shadow-sm flex flex-col justify-between">
                <h2 class="text-sm font-bold text-ink mb-2">Distribusi Status Gudang</h2>
                <div class="relative w-full h-44 flex items-center justify-center">
                    <canvas id="warehouseChart" aria-label="Grafik distribusi status gudang"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs pt-4 border-t border-line">
                    @foreach ($warehousePresentation as $status => [$label, $cardClasses, $numberClasses, $dotClass])
                        <div class="flex items-start gap-1.5">
                            <span class="w-2.5 h-2.5 mt-0.5 rounded-full {{ $dotClass }}"></span>
                            <span class="text-muted">{{ $label }} ({{ number_format($warehousePercentages[$status], 1, ',', '.') }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <section class="mb-8">
            <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
                <div>
                    <h2 class="font-bold text-ink text-base">Ringkasan Booking</h2>
                    <p class="text-xs text-muted">Jumlah booking berdasarkan status</p>
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
            <div class="bg-white rounded-2xl border border-line/80 p-5 shadow-sm">
                <span class="text-2xl font-bold text-ink">{{ $activeAppointmentsToday }}</span>
                <span class="block text-xs font-medium text-muted mt-1">Janji temu aktif hari ini</span>
            </div>
            <div class="bg-white rounded-2xl border border-line/80 p-5 shadow-sm">
                <span class="text-2xl font-bold text-ink">{{ $activeAppointmentsThisWeek }}</span>
                <span class="block text-xs font-medium text-muted mt-1">Janji temu aktif minggu ini</span>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-line/80 shadow-sm overflow-hidden mb-8">
            <div class="p-5 md:p-6 flex justify-between items-center border-b border-line">
                <div>
                    <h2 class="font-bold text-ink text-base">Temu Janji Terbaru</h2>
                    <p class="text-xs text-muted mt-1">Lima booking terakhir yang tercatat</p>
                </div>
                <a href="{{ route('admin.temu-janji.index') }}" class="text-primary hover:text-primary-dark text-sm font-semibold">Lihat Semua</a>
            </div>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-muted">
                    <thead class="bg-surface/80 text-xs font-semibold text-muted uppercase tracking-wider border-b border-line">
                        <tr>
                            <th class="px-6 py-4">Booking</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Gudang</th>
                            <th class="px-6 py-4">Jadwal</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-light">
                        @forelse ($recentBookings as $booking)
                            <tr class="hover:bg-surface/50 transition">
                                <td class="px-6 py-4 font-mono text-xs text-muted">
                                    <a href="{{ route('admin.temu-janji.show', $booking) }}" class="hover:text-primary">{{ $booking->kode_booking }}</a>
                                </td>
                                <td class="px-6 py-4 font-semibold text-ink">{{ $booking->nama_perusahaan }}</td>
                                <td class="px-6 py-4">{{ $booking->gudang?->unit_code ? 'Gudang '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</td>
                                <td class="px-6 py-4 text-muted">{{ $booking->tanggal_kunjungan->format('d M Y') }}, {{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider {{ $bookingStatusClasses[$booking->status] ?? 'bg-primary-light text-muted' }}">{{ $booking->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-muted">Belum ada data janji temu.</td>
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
        const chartTheme = getComputedStyle(document.documentElement);
        const chartValues = {{ Illuminate\Support\Js::from($warehouseChartValues) }};

        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

        new Chart(chartElement, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: ['--color-emerald-500', '--color-accent', '--color-muted', '--color-primary'].map(color => chartTheme.getPropertyValue(color).trim()),
                    borderWidth: 2,
                    borderColor: chartTheme.getPropertyValue('--color-white').trim()
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
