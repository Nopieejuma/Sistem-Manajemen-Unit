<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    @include('layouts.sidebar')

    <main class="flex-1 h-screen overflow-y-auto p-5 md:p-8">
        <x-admin-customer-link />
        <div class="mb-6">
            <a href="{{ route('admin.jadwal.index') }}" class="mb-3 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-700">← Kembali ke jadwal</a>
            <h1 class="text-xl font-bold text-slate-900">Edit Jadwal</h1>
            <p class="text-xs text-slate-500">Perbarui jam dan kuota slot kunjungan.</p>
        </div>

        <section class="max-w-2xl rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm md:p-7">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if ($hasBookingHistory)
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900">Slot ini memiliki riwayat booking. Jam mulai dan jam selesai dikunci; kuota masih dapat disesuaikan selama tidak di bawah pemakaian tertinggi per tanggal.</div>
            @endif
            <form method="POST" action="{{ route('admin.jadwal.update', $schedule) }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Jam Mulai</span>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', substr($schedule->jam_mulai, 0, 5)) }}" required @readonly($hasBookingHistory) class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 read-only:bg-slate-100">
                        @error('jam_mulai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Jam Selesai</span>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', substr($schedule->jam_selesai, 0, 5)) }}" required @readonly($hasBookingHistory) class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 read-only:bg-slate-100">
                        @error('jam_selesai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <label class="block max-w-xs">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Kuota per tanggal</span>
                    <input type="number" name="kuota" min="1" step="1" value="{{ old('kuota', $schedule->kuota) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('kuota')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <p class="text-xs text-slate-500">Status jadwal saat ini: <span class="font-semibold">{{ $schedule->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>. Gunakan tombol pada daftar jadwal untuk mengubah status.</p>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('admin.jadwal.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-700">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
