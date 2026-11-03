<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail {{ $booking->kode_booking }} - SARANA INDUSTRIAL POINT</title>
    <x-admin-head />
</head>
<body class="admin-site flex min-h-dvh flex-col bg-surface md:flex-row">
    @include('layouts.sidebar')

    <main id="admin-content" class="admin-main">
        <x-admin-customer-link />
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('admin.temu-janji.index') }}" class="mb-3 inline-flex text-xs font-semibold text-primary hover:text-primary-dark">← Kembali ke daftar booking</a>
                <h1 class="text-xl font-bold text-ink">Detail Booking</h1>
                <p class="font-mono text-xs text-muted">{{ $booking->kode_booking }}</p>
            </div>
            @php($statusClasses = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-800', 'completed' => 'bg-primary-light text-primary-dark', 'canceled' => 'bg-primary-light text-muted'])
            <span class="rounded-full px-3 py-1.5 text-xs font-bold uppercase {{ $statusClasses[$booking->status] ?? 'bg-primary-light text-muted' }}">{{ $booking->status }}</span>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <section class="space-y-5 xl:col-span-2">
                <div class="rounded-2xl border border-line/80 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-ink">Informasi Customer</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-muted">Nama Perusahaan</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ $booking->nama_perusahaan }}</dd></div>
                        <div><dt class="text-xs text-muted">Nama PIC</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ $booking->nama_pic }}</dd></div>
                        <div><dt class="text-xs text-muted">Email</dt><dd class="mt-1 break-all text-sm text-ink">{{ $booking->email }}</dd></div>
                        <div><dt class="text-xs text-muted">Nomor Telepon</dt><dd class="mt-1 text-sm text-ink">{{ $booking->no_telepon }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-line/80 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-ink">Kunjungan</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-muted">Gudang</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ $booking->gudang?->unit_code ? 'Unit '.$booking->gudang->unit_code : 'Gudang tidak ditemukan' }}</dd><dd class="text-xs text-muted">{{ $booking->gudang?->blok ? 'Blok '.$booking->gudang->blok : '' }}{{ $booking->gudang?->tipe_gudang ? ' · '.$booking->gudang->tipe_gudang : '' }}</dd></div>
                        <div><dt class="text-xs text-muted">Tanggal Kunjungan</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ $booking->tanggal_kunjungan->format('d F Y') }}</dd></div>
                        <div><dt class="text-xs text-muted">Jam</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ substr($booking->jam_mulai, 0, 5) }}–{{ substr($booking->jam_selesai, 0, 5) }}</dd></div>
                        <div><dt class="text-xs text-muted">Kapasitas Slot</dt><dd class="mt-1 text-sm font-semibold text-ink">{{ $usedCapacity }}{{ $capacityLimit !== null ? ' / '.$capacityLimit.' booking' : ' booking' }}</dd><dd class="text-xs text-muted">Status pending, approved, dan completed dihitung.</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-muted">Barang yang akan disimpan di gudang</dt><dd class="mt-1 whitespace-pre-line break-words text-sm text-ink">{{ $booking->jenis_barang_disimpan ?: 'Belum diisi (booking lama).' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-muted">Catatan Customer</dt><dd class="mt-1 whitespace-pre-line text-sm text-ink">{{ $booking->catatan ?: 'Tidak ada catatan.' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="h-fit rounded-2xl border border-line/80 bg-white p-5 shadow-sm">
                <h2 class="mb-1 text-sm font-bold text-ink">Tindakan Admin</h2>
                <p class="mb-4 text-xs text-muted">Perubahan status disimpan secara aman.</p>
                <div class="space-y-2">
                    @foreach (['approve' => ['Setujui Booking', 'bg-emerald-600 hover:bg-emerald-700'], 'reject' => ['Tolak Booking', 'bg-red-600 hover:bg-red-700'], 'complete' => ['Tandai Selesai', 'bg-primary hover:bg-primary-dark'], 'cancel' => ['Batalkan Booking', 'bg-ink hover:bg-ink'], 'revoke' => ['Batalkan Persetujuan', 'bg-ink hover:bg-ink'], 'destroy' => ['Hapus', 'bg-red-600 hover:bg-red-700']] as $action => [$label, $classes])
                        @if (in_array($action, $allowedActions, true))
                            <form method="POST" action="{{ route('admin.temu-janji.'.$action, $booking) }}" onsubmit="return confirm('{{ in_array($action, ['cancel', 'destroy'], true) ? 'Hapus booking ini secara permanen? Data tidak dapat dikembalikan.' : $label.'?' }}')">
                                @csrf
                                @if ($action === 'destroy')
                                    @method('DELETE')
                                @endif
                                <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-xs font-semibold text-white transition {{ $classes }}">{{ $label }}</button>
                            </form>
                        @endif
                    @endforeach
                    @if (count($allowedActions) === 0)
                        <p class="rounded-xl bg-surface px-4 py-3 text-xs text-muted">Status final. Tidak ada tindakan lanjutan.</p>
                    @endif
                </div>
            </section>
        </div>
    </main>
</body>
</html>
