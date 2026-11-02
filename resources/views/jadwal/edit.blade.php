<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-6">
            <a href="{{ route('admin.jadwal.index') }}" class="mb-3 inline-flex text-xs font-semibold text-primary hover:text-primary-dark">← Kembali ke jadwal</a>
            <h1 class="text-xl font-bold text-ink">Edit Jadwal</h1>
            <p class="text-xs text-muted">Perbarui jam dan kuota slot kunjungan.</p>
        </div>

        <section class="max-w-2xl rounded-2xl border border-line/80 bg-white p-5 shadow-sm md:p-7">
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
                        <span class="mb-1.5 block text-xs font-semibold text-ink">Jam Mulai</span>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', substr($schedule->jam_mulai, 0, 5)) }}" required @readonly($hasBookingHistory) class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light read-only:bg-primary-light">
                        @error('jam_mulai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-ink">Jam Selesai</span>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', substr($schedule->jam_selesai, 0, 5)) }}" required @readonly($hasBookingHistory) class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light read-only:bg-primary-light">
                        @error('jam_selesai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <label class="block max-w-xs">
                    <span class="mb-1.5 block text-xs font-semibold text-ink">Kuota per tanggal</span>
                    <input type="number" name="kuota" min="1" step="1" value="{{ old('kuota', $schedule->kuota) }}" required class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                    @error('kuota')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <p class="text-xs text-muted">Status jadwal saat ini: <span class="font-semibold">{{ $schedule->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>. Gunakan tombol pada daftar jadwal untuk mengubah status.</p>
                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <a href="{{ route('admin.jadwal.index') }}" class="rounded-xl border border-line px-4 py-2.5 text-xs font-semibold text-muted hover:bg-surface">Batal</a>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-white hover:bg-primary-dark">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
