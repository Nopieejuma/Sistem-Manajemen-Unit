<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jadwal - WAREHOUSE.BOOK</title>
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
            <h1 class="text-xl font-bold text-slate-900">Tambah Jadwal</h1>
            <p class="text-xs text-slate-500">Slot aktif akan langsung tersedia pada Customer Side.</p>
        </div>

        <section class="max-w-2xl rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm md:p-7">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.jadwal.store') }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Jam Mulai</span>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai') }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('jam_mulai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Jam Selesai</span>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai') }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('jam_selesai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <label class="block max-w-xs">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Kuota per tanggal</span>
                    <input type="number" name="kuota" min="1" step="1" value="{{ old('kuota', 1) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('kuota')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <div>
                    <input type="hidden" name="status_aktif" value="0">
                    <label class="flex w-fit cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', '1') === '1') class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Aktifkan jadwal setelah disimpan
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('admin.jadwal.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-700">Simpan Jadwal</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
