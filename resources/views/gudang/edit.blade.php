<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Gudang - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <x-admin-customer-link />
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Edit Data Gudang</h1>
                <p class="text-xs text-slate-500">Ubah rincian unit gudang dan kelola foto pendukung.</p>
            </div>
            <a href="{{ route('admin.gudang.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl px-3.5 py-2 transition">
                ← Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.gudang.update', $gudang->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Identitas Unit -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Unit Code *</label>
                    <input type="text" name="unit_code" required value="{{ old('unit_code', $gudang->unit_code) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Blok *</label>
                    <input type="text" name="blok" required value="{{ old('blok', $gudang->blok) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Gudang *</label>
                    <select name="tipe_gudang" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @foreach ($warehouseTypes as $warehouseType)
                            <option value="{{ $warehouseType }}" @selected(old('tipe_gudang', (string) $gudang->tipe_gudang) === $warehouseType)>{{ $warehouseType }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($gudang->status === 'sedang di-keep')
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                    Status sedang di-keep dikelola oleh booking approved. Selesaikan atau batalkan booking untuk melepaskan keep.
                </div>
            @else
                <div class="max-w-sm">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status Gudang *</label>
                    <select name="status" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        @foreach ($manualStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $gudang->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Dimensi & Harga -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tinggi (M) *</label>
                    <input type="number" step="0.01" name="tinggi" required value="{{ old('tinggi', $gudang->tinggi) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Harga Sewa per tahun</label>
                    <input type="number" name="harga_sewa" value="{{ old('harga_sewa', $gudang->harga_sewa) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kavling Lebar (m) *</label>
                    <input type="number" step="0.01" name="kavling_l" required value="{{ old('kavling_l', $gudang->kavling_l) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kavling Panjang (m) *</label>
                    <input type="number" step="0.01" name="kavling_p" required value="{{ old('kavling_p', $gudang->kavling_p) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 1 Lebar (m) *</label>
                    <input type="number" step="0.01" name="lt1_l" required value="{{ old('lt1_l', $gudang->lt1_l) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 1 Panjang (m) *</label>
                    <input type="number" step="0.01" name="lt1_p" required value="{{ old('lt1_p', $gudang->lt1_p) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 2 Lebar (m) *</label>
                    <input type="number" step="0.01" name="lt2_l" required value="{{ old('lt2_l', $gudang->lt2_l) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 2 Panjang (m) *</label>
                    <input type="number" step="0.01" name="lt2_p" required value="{{ old('lt2_p', $gudang->lt2_p) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Fasilitas -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Fasilitas Gudang</label>
                @php
                    $fasilitas = is_array($gudang->fasilitas) ? $gudang->fasilitas : json_decode($gudang->fasilitas, true) ?? [];
                @endphp
                <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="CCTV 24 Jam" {{ in_array('CCTV 24 Jam', $fasilitas) ? 'checked' : '' }} class="rounded text-blue-600"> CCTV 24 Jam
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Area Parkir Luas" {{ in_array('Area Parkir Luas', $fasilitas) ? 'checked' : '' }} class="rounded text-blue-600"> Area Parkir Luas
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Kanopi Depan" {{ in_array('Kanopi Depan', $fasilitas) ? 'checked' : '' }} class="rounded text-blue-600"> Kanopi Depan
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Keamanan 24 Jam" {{ in_array('Keamanan 24 Jam', $fasilitas) ? 'checked' : '' }} class="rounded text-blue-600"> Keamanan 24 Jam
                    </label>
                </div>
            </div>

            <!-- Bagian Kelola Foto Lama (Hapus Foto Spesifik) -->
            @php
                $fotoList = is_array($gudang->foto) ? $gudang->foto : json_decode($gudang->foto, true) ?? [];
            @endphp
            @if(count($fotoList) > 0)
                <div class="pt-3 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Foto Terpasang Saat Ini</label>
                    <p class="text-[11px] text-slate-400 mb-3">Centang pada foto yang ingin kamu hapus dari database.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($fotoList as $idx => $path)
                            <div class="relative border border-slate-200 rounded-xl overflow-hidden bg-slate-50 p-1 group">
                                <img src="{{ $gudang->photoUrl($path) }}" alt="Foto gudang {{ $gudang->unit_code }} {{ $idx + 1 }}" class="w-full h-24 object-cover rounded-lg">
                                <label class="flex items-center gap-1.5 text-[11px] font-semibold text-red-600 mt-2 p-1 cursor-pointer">
                                    <input type="checkbox" name="hapus_foto[]" value="{{ $path }}" class="rounded text-red-600"> Hapus Foto
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Upload Tambah Foto Baru dengan Antrian JS -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700">Tambah Foto Baru</label>

                <div class="flex items-center gap-3">
                    <label for="foto" class="cursor-pointer bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-600 px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2">
                        📷 Pilih / Tambah Foto Baru
                    </label>
                    <input type="file" id="foto" name="foto[]" multiple accept="image/*" class="hidden">
                    <span id="file-count" class="text-xs text-slate-500 font-medium">Belum ada foto baru dipilih</span>
                </div>

                <!-- Preview Grid Foto Baru -->
                <div id="preview-container" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3"></div>
            </div>

            <!-- Submit -->
            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl shadow-sm transition">
                    Perbarui Gudang
                </button>
            </div>
        </form>
    </div>

    <!-- Script Manage Antrian Foto Baru (DataTransfer) -->
    <script>
        const fotoInput = document.getElementById('foto');
        const previewContainer = document.getElementById('preview-container');
        const fileCount = document.getElementById('file-count');
        const dt = new DataTransfer();

        fotoInput.addEventListener('change', function(e) {
            const files = e.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                let isDuplicate = false;

                for (let j = 0; j < dt.items.length; j++) {
                    if (dt.items[j].getAsFile().name === file.name && dt.items[j].getAsFile().size === file.size) {
                        isDuplicate = true;
                        break;
                    }
                }

                if (!isDuplicate) {
                    dt.items.add(file);

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const card = document.createElement('div');
                        card.className = 'relative border border-slate-200 rounded-xl overflow-hidden bg-slate-100 h-28 shadow-sm';

                        card.innerHTML = `
                            <img src="${event.target.result}" class="w-full h-full object-cover">
                            <button type="button"
                                onclick="removeQueueFile('${file.name}', ${file.size}, this)"
                                class="absolute top-1.5 right-1.5 bg-red-600 hover:bg-red-700 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-md transition">
                                ✕
                            </button>
                        `;
                        previewContainer.appendChild(card);
                    };
                    reader.readAsDataURL(file);
                }
            }

            fotoInput.files = dt.files;
            updateFileCount();
        });

        function removeQueueFile(fileName, fileSize, btnElement) {
            for (let i = 0; i < dt.items.length; i++) {
                const file = dt.items[i].getAsFile();
                if (file.name === fileName && file.size === fileSize) {
                    dt.items.remove(i);
                    break;
                }
            }

            fotoInput.files = dt.files;
            btnElement.parentElement.remove();
            updateFileCount();
        }

        function updateFileCount() {
            const count = dt.files.length;
            fileCount.textContent = count > 0 ? `${count} foto baru dalam antrian` : 'Belum ada foto baru dipilih';
        }
    </script>
</body>
</html>
