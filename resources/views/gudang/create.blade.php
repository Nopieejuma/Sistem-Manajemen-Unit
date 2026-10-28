<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Gudang - WAREHOUSE.BOOK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 min-h-screen p-6">

    <div class="max-w-4xl mx-auto bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Tambah Data Gudang</h1>
                <p class="text-xs text-slate-500">Isi formulir berikut untuk menambahkan unit gudang baru.</p>
            </div>
            <a href="{{ route('gudang.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl px-3.5 py-2 transition">
                ← Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('gudang.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Identitas Unit -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Unit Code *</label>
                    <input type="text" name="unit_code" required value="{{ old('unit_code') }}" placeholder="Contoh: A-01" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Blok *</label>
                    <input type="text" name="blok" required value="{{ old('blok') }}" placeholder="Contoh: Blok A" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Gudang *</label>
                    <select name="tipe_gudang" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">Pilih tipe gudang</option>
                        @foreach ($warehouseTypes as $warehouseType)
                            <option value="{{ $warehouseType }}" @selected(old('tipe_gudang') === $warehouseType)>{{ $warehouseType }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="max-w-sm">
                <label class="block text-xs font-bold text-slate-700 mb-1">Status Gudang *</label>
                <select name="status" required class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    @foreach ($manualStatuses as $status)
                        <option value="{{ $status }}" @selected(old('status', 'tersedia') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dimensi & Harga -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tinggi (M) *</label>
                    <input type="number" step="0.01" name="tinggi" required value="{{ old('tinggi') }}" placeholder="8.5" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Harga Sewa / Thn</label>
                    <input type="number" name="harga_sewa" value="{{ old('harga_sewa') }}" placeholder="50000000" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kavling Lebar (m) *</label>
                    <input type="number" step="0.01" name="kavling_l" required value="{{ old('kavling_l') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kavling Panjang (m) *</label>
                    <input type="number" step="0.01" name="kavling_p" required value="{{ old('kavling_p') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 1 Lebar (m) *</label>
                    <input type="number" step="0.01" name="lt1_l" required value="{{ old('lt1_l') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 1 Panjang (m) *</label>
                    <input type="number" step="0.01" name="lt1_p" required value="{{ old('lt1_p') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 2 Lebar (m) *</label>
                    <input type="number" step="0.01" name="lt2_l" required value="{{ old('lt2_l') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lt 2 Panjang (m) *</label>
                    <input type="number" step="0.01" name="lt2_p" required value="{{ old('lt2_p') }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pos Lebar (m)</label>
                    <input type="number" step="0.01" name="pos_l" value="{{ old('pos_l', 3.00) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pos Panjang (m)</label>
                    <input type="number" step="0.01" name="pos_p" value="{{ old('pos_p', 4.00) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Extra Space (m²)</label>
                    <input type="number" step="0.01" name="extra_m2" value="{{ old('extra_m2', 10.00) }}" class="w-full text-xs px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Fasilitas -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Fasilitas Gudang</label>
                <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="CCTV 24 Jam" @checked(in_array('CCTV 24 Jam', old('fasilitas', []), true)) class="rounded text-blue-600"> CCTV 24 Jam
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Area Parkir Luas" @checked(in_array('Area Parkir Luas', old('fasilitas', []), true)) class="rounded text-blue-600"> Area Parkir Luas
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Kanopi Depan" @checked(in_array('Kanopi Depan', old('fasilitas', []), true)) class="rounded text-blue-600"> Kanopi Depan
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="fasilitas[]" value="Keamanan 24 Jam" @checked(in_array('Keamanan 24 Jam', old('fasilitas', []), true)) class="rounded text-blue-600"> Keamanan 24 Jam
                    </label>
                </div>
            </div>

            <!-- Upload Foto dengan Preview & Hapus Antrian -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700">Foto Gudang</label>

                <div class="flex items-center gap-3">
                    <label for="foto" class="cursor-pointer bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-600 px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-2">
                        📷 Pilih / Tambah Foto
                    </label>
                    <input type="file" id="foto" name="foto[]" multiple accept="image/*" class="hidden">
                    <span id="file-count" class="text-xs text-slate-500 font-medium">Belum ada foto dipilih</span>
                </div>

                <!-- Preview Grid -->
                <div id="preview-container" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3"></div>
            </div>

            <!-- Submit -->
            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl shadow-sm transition">
                    Simpan Gudang
                </button>
            </div>
        </form>
    </div>

    <!-- Script Manage Antrian Foto (DataTransfer) -->
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
            fileCount.textContent = count > 0 ? `${count} foto dalam antrian` : 'Belum ada foto dipilih';
        }
    </script>
</body>
</html>
