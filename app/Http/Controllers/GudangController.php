<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GudangController extends Controller
{
    private const array Facilities = [
        'CCTV 24 Jam',
        'Area Parkir Luas',
        'Kanopi Depan',
        'Keamanan 24 Jam',
    ];

    public function index(): View
    {
        $gudang = Gudang::query()->orderByDesc('created_at')->get();

        return view('gudang.index', compact('gudang'));
    }

    public function create(): View
    {
        return view('gudang.create', [
            'warehouseTypes' => Gudang::WarehouseTypes,
            'manualStatuses' => Gudang::ManualStatuses,
            'facilities' => self::Facilities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, null);
        $this->validateCalculatedAreas($data);
        $photos = $this->storeUploadedPhotos($request);

        try {
            Gudang::query()->create($this->warehouseAttributes($data, $photos));
        } catch (Throwable $exception) {
            $this->deleteOwnedPhotos($photos);

            throw $exception;
        }

        return to_route('gudang.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit(Gudang $gudang): View
    {
        return view('gudang.edit', [
            'gudang' => $gudang,
            'hasApprovedBooking' => $gudang->hasApprovedBookings(),
            'warehouseTypes' => Gudang::WarehouseTypes,
            'manualStatuses' => Gudang::ManualStatuses,
            'facilities' => self::Facilities,
        ]);
    }

    public function update(Request $request, Gudang $gudang): RedirectResponse
    {
        $data = $this->validatedData($request, $gudang);
        $this->validateCalculatedAreas($data);
        $newPhotos = $this->storeUploadedPhotos($request);

        try {
            $removedPhotos = DB::transaction(function () use ($data, $gudang, $newPhotos): array {
                $warehouse = Gudang::query()->whereKey($gudang->getKey())->lockForUpdate()->firstOrFail();
                $hasApprovedBooking = $warehouse->temuJanji()->where('status', 'approved')->exists();
                $requestedStatus = $data['status'] ?? null;

                if ($hasApprovedBooking && $requestedStatus !== null && $requestedStatus !== $warehouse->status) {
                    throw ValidationException::withMessages([
                        'status' => 'Status gudang dikunci selama masih ada booking approved. Selesaikan atau batalkan booking terlebih dahulu.',
                    ]);
                }

                $existingPhotos = $this->photoList($warehouse->foto);
                $photosToRemove = $data['hapus_foto'] ?? [];

                if (array_diff($photosToRemove, $existingPhotos) !== []) {
                    throw ValidationException::withMessages([
                        'hapus_foto' => 'Foto yang dipilih sudah tidak terpasang pada gudang ini.',
                    ]);
                }

                $remainingPhotos = array_values(array_filter(
                    $existingPhotos,
                    fn (string $photo): bool => ! in_array($photo, $photosToRemove, true),
                ));
                $photos = array_values(array_merge($remainingPhotos, $newPhotos));

                $status = $requestedStatus ?? $warehouse->status;

                if ($hasApprovedBooking && $status === 'tersedia') {
                    $status = 'sedang di-keep';
                }

                if (! $hasApprovedBooking && $requestedStatus === null && $status === 'sedang di-keep') {
                    $status = 'tersedia';
                }

                $warehouse->update($this->warehouseAttributes($data, $photos, $status));

                return $photosToRemove;
            });
        } catch (Throwable $exception) {
            $this->deleteOwnedPhotos($newPhotos);

            throw $exception;
        }

        $this->deleteOwnedPhotos($removedPhotos);

        return to_route('gudang.index')->with('success', 'Data gudang berhasil diperbarui.');
    }

    public function destroy(Gudang $gudang): RedirectResponse
    {
        $photos = DB::transaction(function () use ($gudang): array {
            $warehouse = Gudang::query()->whereKey($gudang->getKey())->lockForUpdate()->firstOrFail();

            if ($warehouse->temuJanji()->exists()) {
                throw ValidationException::withMessages([
                    'gudang' => 'Gudang tidak dapat dihapus karena memiliki riwayat booking.',
                ]);
            }

            $photos = $this->photoList($warehouse->foto);
            $warehouse->delete();

            return $photos;
        });

        $this->deleteOwnedPhotos($photos);

        return to_route('gudang.index')->with('success', 'Gudang berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Gudang $warehouse): array
    {
        $unitCodeRule = Rule::unique('gudang', 'unit_code');

        if ($warehouse !== null) {
            $unitCodeRule->ignore($warehouse->getKey(), 'id');
        }

        $statusRules = $warehouse === null
            ? ['required', Rule::in(Gudang::ManualStatuses)]
            : ['sometimes', 'nullable', Rule::in(Gudang::ManualStatuses)];

        return $request->validate([
            'unit_code' => ['required', 'string', 'max:20', $unitCodeRule],
            'blok' => ['required', 'string', 'max:10'],
            'tipe_gudang' => ['required', 'string', Rule::in(Gudang::WarehouseTypes)],
            'tinggi' => ['required', 'numeric', 'min:0', 'max:999.99', 'decimal:0,2'],
            'kavling_l' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'kavling_p' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'lt1_l' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'lt1_p' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'lt2_l' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'lt2_p' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'pos_l' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'pos_p' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'extra_m2' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'harga_sewa' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'status' => $statusRules,
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['string', Rule::in(self::Facilities), 'distinct'],
            'foto' => ['nullable', 'array', 'max:20'],
            'foto.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['string', 'max:2048', 'distinct'],
        ], [
            'unit_code.unique' => 'Kode unit sudah digunakan.',
            'unit_code.max' => 'Kode unit maksimal 20 karakter.',
            'tipe_gudang.in' => 'Tipe gudang tidak sesuai dengan pilihan yang tersedia.',
            'status.in' => 'Status gudang tidak valid atau dikelola otomatis oleh booking.',
            'harga_sewa.min' => 'Harga sewa tidak boleh negatif.',
            'fasilitas.*.in' => 'Fasilitas yang dipilih tidak valid.',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function validateCalculatedAreas(array $data): void
    {
        $extraSpace = (float) ($data['extra_m2'] ?? 10);
        $posWidth = (float) ($data['pos_l'] ?? 3);
        $posLength = (float) ($data['pos_p'] ?? 4);
        $buildingArea = ((float) $data['lt1_l'] * (float) $data['lt1_p'])
            + ((float) $data['lt2_l'] * (float) $data['lt2_p'])
            + $extraSpace;
        $grandTotal = $buildingArea + ($posWidth * $posLength);
        $maximumArea = 9999999999.99;

        if ((float) $data['kavling_l'] * (float) $data['kavling_p'] > $maximumArea
            || $buildingArea > $maximumArea
            || $grandTotal > $maximumArea) {
            throw ValidationException::withMessages([
                'kavling_l' => 'Hasil perhitungan luas melebihi kapasitas kolom database.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $photos
     * @return array<string, mixed>
     */
    private function warehouseAttributes(array $data, array $photos, ?string $status = null): array
    {
        $extraSpace = (float) ($data['extra_m2'] ?? 10);
        $posWidth = (float) ($data['pos_l'] ?? 3);
        $posLength = (float) ($data['pos_p'] ?? 4);
        $lotArea = (float) $data['kavling_l'] * (float) $data['kavling_p'];
        $levelOneArea = (float) $data['lt1_l'] * (float) $data['lt1_p'];
        $levelTwoArea = ((float) $data['lt2_l'] * (float) $data['lt2_p']) + $extraSpace;
        $parkingArea = $posWidth * $posLength;
        $buildingArea = $levelOneArea + $levelTwoArea;

        return [
            'unit_code' => $data['unit_code'],
            'blok' => $data['blok'],
            'tipe_gudang' => $data['tipe_gudang'],
            'tinggi' => $data['tinggi'],
            'kavling_l' => $data['kavling_l'],
            'kavling_p' => $data['kavling_p'],
            'luas_kavling' => $lotArea,
            'lt1_l' => $data['lt1_l'],
            'lt1_p' => $data['lt1_p'],
            'luas_lt1' => $levelOneArea,
            'lt2_l' => $data['lt2_l'],
            'lt2_p' => $data['lt2_p'],
            'extra_m2' => $extraSpace,
            'luas_lt2' => $levelTwoArea,
            'pos_l' => $posWidth,
            'pos_p' => $posLength,
            'luas_pos' => $parkingArea,
            'total_luas_bangunan' => $buildingArea,
            'grand_total' => $buildingArea + $parkingArea,
            'harga_sewa' => $data['harga_sewa'] ?? 0,
            'fasilitas' => array_values($data['fasilitas'] ?? []),
            'status' => $status ?? $data['status'] ?? 'tersedia',
            'foto' => $photos,
        ];
    }

    /** @return array<int, string> */
    private function storeUploadedPhotos(Request $request): array
    {
        $photos = [];

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('foto', []);

        try {
            foreach ($files as $file) {
                $path = $file->store('gudang', 'public');

                if (! is_string($path)) {
                    throw new \RuntimeException('Foto gudang gagal disimpan.');
                }

                $url = Storage::disk('public')->url($path);
                $photos[] = parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : $path;
            }
        } catch (Throwable $exception) {
            $this->deleteOwnedPhotos($photos);

            throw $exception;
        }

        return $photos;
    }

    /**
     * @return array<int, string>
     */
    private function photoList(mixed $photos): array
    {
        if (is_string($photos)) {
            $photos = json_decode($photos, true);
        }

        if (! is_array($photos)) {
            return [];
        }

        return array_values(array_filter($photos, 'is_string'));
    }

    /** @param array<int, string> $photos */
    private function deleteOwnedPhotos(array $photos): void
    {
        foreach ($photos as $photo) {
            $path = $this->ownedPublicStoragePath($photo);

            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function ownedPublicStoragePath(string $photo): ?string
    {
        if (! filter_var($photo, FILTER_VALIDATE_URL)) {
            return $this->safeRelativePhotoPath($photo);
        }

        $diskUrl = (string) config('filesystems.disks.public.url', '');
        $diskUrlParts = parse_url(rtrim($diskUrl, '/'));
        $photoParts = parse_url($photo);

        if (! is_array($diskUrlParts) || ! is_array($photoParts)
            || ($diskUrlParts['scheme'] ?? null) !== ($photoParts['scheme'] ?? null)
            || strtolower((string) ($diskUrlParts['host'] ?? '')) !== strtolower((string) ($photoParts['host'] ?? ''))
            || ($diskUrlParts['port'] ?? null) !== ($photoParts['port'] ?? null)) {
            return null;
        }

        $basePath = rtrim((string) ($diskUrlParts['path'] ?? ''), '/');
        $photoPath = (string) ($photoParts['path'] ?? '');

        if ($basePath === '' || ! Str::startsWith($photoPath, $basePath.'/')) {
            return null;
        }

        return $this->safeRelativePhotoPath(rawurldecode(substr($photoPath, strlen($basePath) + 1)));
    }

    private function safeRelativePhotoPath(string $path): ?string
    {
        if (! Str::startsWith($path, 'gudang/') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return null;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return $path;
    }
}
