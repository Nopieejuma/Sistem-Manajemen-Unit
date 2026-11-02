<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jadwal - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-6">
            <a href="{{ route('admin.jadwal.index') }}" class="mb-3 inline-flex text-xs font-semibold text-primary hover:text-primary-dark">← Kembali ke jadwal</a>
            <h1 class="text-xl font-bold text-ink">Tambah Jadwal</h1>
            <p class="text-xs text-muted">Slot aktif akan langsung tersedia pada Customer Side.</p>
        </div>

        <section class="max-w-2xl rounded-2xl border border-line/80 bg-white p-5 shadow-sm md:p-7">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.jadwal.store') }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-ink">Jam Mulai</span>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                        @error('jam_mulai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-ink">Jam Selesai</span>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                        @error('jam_selesai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <label class="block max-w-xs">
                    <span class="mb-1.5 block text-xs font-semibold text-ink">Kuota per tanggal</span>
                    <input type="number" name="kuota" min="1" step="1" value="{{ old('kuota', 1) }}" required class="w-full rounded-xl border border-line px-3 py-2.5 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light">
                    @error('kuota')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <div>
                    <input type="hidden" name="status_aktif" value="0">
                    <label class="flex w-fit cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                        <input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', '1') === '1') class="rounded border-line text-primary focus:ring-primary">
                        Aktifkan jadwal setelah disimpan
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <a href="{{ route('admin.jadwal.index') }}" class="rounded-xl border border-line px-4 py-2.5 text-xs font-semibold text-muted hover:bg-surface">Batal</a>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-white hover:bg-primary-dark">Simpan Jadwal</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
