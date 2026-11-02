<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal &amp; Kuota - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-ink">Jadwal &amp; Kuota</h1>
                <p class="text-xs text-muted">Atur slot kunjungan yang tersedia untuk Customer</p>
            </div>
            <a href="{{ route('admin.jadwal.create') }}" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-dark">+ Tambah Jadwal</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-line/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-sm font-bold text-ink">Daftar Slot</h2>
                <span class="text-xs text-muted">{{ $schedules->count() }} jadwal</span>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-muted">
                    <thead class="border-b border-line bg-surface/80 text-xs font-semibold uppercase tracking-wider text-muted">
                        <tr>
                            <th class="px-5 py-3">Jam Kunjungan</th>
                            <th class="px-5 py-3">Kuota / Tanggal</th>
                            <th class="px-5 py-3">Pemakaian Kuota</th>
                            <th class="px-5 py-3">Status Customer</th>
                            <th class="px-5 py-3">Status Jadwal</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-light">
                        @forelse ($schedules as $schedule)
                            <tr class="align-top transition hover:bg-surface/70">
                                <td class="px-5 py-4 font-semibold text-ink">{{ substr($schedule->jam_mulai, 0, 5) }}–{{ substr($schedule->jam_selesai, 0, 5) }}</td>
                                <td class="px-5 py-4 font-semibold text-ink">{{ $schedule->kuota }} <span class="text-xs font-normal text-muted">booking</span></td>
                                <td class="px-5 py-4">
                                    @if ($schedule->usage_by_date->isEmpty())
                                        <span class="text-xs text-muted">Belum ada pemakaian</span>
                                    @else
                                        <details class="max-w-xs">
                                            <summary class="cursor-pointer text-xs font-semibold text-primary-dark">{{ $schedule->usage_by_date->count() }} tanggal tercatat</summary>
                                            <ul class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded-lg bg-surface p-2 text-xs text-muted">
                                                @foreach ($schedule->usage_by_date as $usage)
                                                    <li class="flex justify-between gap-4"><span>{{ \Illuminate\Support\Carbon::parse($usage->tanggal_kunjungan)->format('d M Y') }}</span><span class="font-semibold">{{ $usage->used_capacity }} / {{ $schedule->kuota }}</span></li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-muted">Pending, approved, completed</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold uppercase {{ $schedule->status_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-primary-light text-muted' }}">{{ $schedule->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.jadwal.edit', $schedule) }}" class="rounded-lg border border-line px-3 py-2 text-xs font-semibold text-primary-dark hover:bg-primary-light">Edit</a>
                                        <form method="POST" action="{{ route($schedule->status_aktif ? 'admin.jadwal.deactivate' : 'admin.jadwal.activate', $schedule) }}" onsubmit="return confirm('{{ $schedule->status_aktif ? 'Nonaktifkan jadwal ini?' : 'Aktifkan jadwal ini?' }}')">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $schedule->status_aktif ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">{{ $schedule->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-muted">Belum ada jadwal kunjungan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="mt-3 text-xs text-muted">Pemakaian ditampilkan per tanggal dan pasangan jam; hanya status pending, approved, dan completed yang dihitung.</p>
    </main>
</body>
</html>
