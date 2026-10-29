<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal &amp; Kuota - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    @include('layouts.sidebar')

    <main class="flex-1 h-screen overflow-y-auto p-5 md:p-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Jadwal &amp; Kuota</h1>
                <p class="text-xs text-slate-500">Atur slot kunjungan yang tersedia untuk Customer</p>
            </div>
            <a href="{{ route('admin.jadwal.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">+ Tambah Jadwal</a>
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

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Daftar Slot</h2>
                <span class="text-xs text-slate-400">{{ $schedules->count() }} jadwal</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-slate-600">
                    <thead class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Jam Kunjungan</th>
                            <th class="px-5 py-3">Kuota / Tanggal</th>
                            <th class="px-5 py-3">Pemakaian Kuota</th>
                            <th class="px-5 py-3">Status Customer</th>
                            <th class="px-5 py-3">Status Jadwal</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($schedules as $schedule)
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ substr($schedule->jam_mulai, 0, 5) }}–{{ substr($schedule->jam_selesai, 0, 5) }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-800">{{ $schedule->kuota }} <span class="text-xs font-normal text-slate-400">booking</span></td>
                                <td class="px-5 py-4">
                                    @if ($schedule->usage_by_date->isEmpty())
                                        <span class="text-xs text-slate-400">Belum ada pemakaian</span>
                                    @else
                                        <details class="max-w-xs">
                                            <summary class="cursor-pointer text-xs font-semibold text-blue-700">{{ $schedule->usage_by_date->count() }} tanggal tercatat</summary>
                                            <ul class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded-lg bg-slate-50 p-2 text-[11px] text-slate-600">
                                                @foreach ($schedule->usage_by_date as $usage)
                                                    <li class="flex justify-between gap-4"><span>{{ \Illuminate\Support\Carbon::parse($usage->tanggal_kunjungan)->format('d M Y') }}</span><span class="font-semibold">{{ $usage->used_capacity }} / {{ $schedule->kuota }}</span></li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500">Pending, approved, completed</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $schedule->status_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $schedule->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.jadwal.edit', $schedule) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">Edit</a>
                                        <form method="POST" action="{{ route($schedule->status_aktif ? 'admin.jadwal.deactivate' : 'admin.jadwal.activate', $schedule) }}" onsubmit="return confirm('{{ $schedule->status_aktif ? 'Nonaktifkan jadwal ini?' : 'Aktifkan jadwal ini?' }}')">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold {{ $schedule->status_aktif ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">{{ $schedule->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada jadwal kunjungan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="mt-3 text-[11px] text-slate-400">Pemakaian ditampilkan per tanggal dan pasangan jam; hanya status pending, approved, dan completed yang dihitung.</p>
    </main>
</body>
</html>
