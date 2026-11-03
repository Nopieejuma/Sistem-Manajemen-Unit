<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\User;
use App\Models\WarehousePhotoUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WarehousePhotoStorage
{
    public const int MaxBytes = 2097152;

    public function enabled(): bool
    {
        return (bool) config('filesystems.warehouse_direct_upload');
    }

    public function readOnly(): bool
    {
        return (bool) config('deployment.container') && ! $this->enabled();
    }

    /** @param array<int, array{mime: string, size: int}> $files
     * @return array<int, array<string, mixed>>
     */
    public function issue(User $admin, ?string $warehouseId, array $files): array
    {
        $stagingBucket = config('filesystems.disks.warehouse_staging.bucket');
        $publicBucket = config('filesystems.disks.warehouse_photos.bucket');
        if (! $stagingBucket || ! $publicBucket || $stagingBucket === $publicBucket) {
            throw new RuntimeException('Bucket sementara privat dan bucket publik harus berbeda.');
        }

        return DB::transaction(function () use ($admin, $warehouseId, $files): array {
            User::query()->whereKey($admin->getKey())->lockForUpdate()->firstOrFail();
            $outstanding = WarehousePhotoUpload::query()->where('admin_id', $admin->getKey())
                ->where('status', 'pending')->where('expires_at', '>', now())->count();
            if ($outstanding + count($files) > 40) {
                throw ValidationException::withMessages(['files' => 'Terlalu banyak upload sementara. Tunggu hingga upload lama kedaluwarsa.']);
            }

            return array_map(function (array $file) use ($admin, $warehouseId): array {
                $id = (string) Str::uuid();
                $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$file['mime']];
                $publicKey = 'gudang/'.Str::uuid().'.'.$extension;
                $url = Storage::disk('warehouse_photos')->url($publicKey);
                if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
                    throw new RuntimeException('URL storage foto production harus HTTPS.');
                }
                $upload = WarehousePhotoUpload::query()->create([
                    'id' => $id, 'admin_id' => $admin->getKey(), 'warehouse_id' => $warehouseId,
                    'staging_key' => 'pending/'.$id.'.'.$extension,
                    'public_key' => $publicKey, 'public_url' => $url,
                    'mime' => $file['mime'], 'size' => $file['size'], 'expires_at' => now()->addHour(),
                ]);
                $signed = Storage::disk('warehouse_staging')->temporaryUploadUrl(
                    $upload->staging_key, now()->addMinutes(5),
                    ['ContentType' => $file['mime'], 'ContentLength' => $file['size']],
                );

                return ['id' => $id, 'url' => $signed['url'], 'headers' => $signed['headers']];
            }, $files);
        });
    }

    /** Must run inside the warehouse transaction.
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    public function attach(array $ids, User $admin, Gudang $warehouse, bool $creating = false): array
    {
        $urls = [];
        sort($ids);
        foreach ($ids as $id) {
            $upload = WarehousePhotoUpload::query()->whereKey($id)->lockForUpdate()->first();
            if (! $upload || (string) $upload->admin_id !== (string) $admin->getKey()
                || $upload->status !== 'pending' || $upload->expires_at->isPast()
                || $upload->warehouse_id !== ($creating ? null : $warehouse->id)) {
                throw ValidationException::withMessages(['photo_uploads' => 'Upload tidak valid, bukan milik Anda, atau sudah kedaluwarsa. Pilih ulang foto.']);
            }
            $this->publishValidated($upload);
            $upload->update(['warehouse_id' => $warehouse->id, 'status' => 'attached']);
            $urls[] = $upload->public_url;
        }

        return $urls;
    }

    private function publishValidated(WarehousePhotoUpload $upload): void
    {
        $disk = Storage::disk('warehouse_staging');
        if (! $disk->exists($upload->staging_key)) {
            throw ValidationException::withMessages(['photo_uploads' => 'Upload foto belum selesai. Silakan pilih ulang foto.']);
        }
        if ($disk->size($upload->staging_key) !== $upload->size || $upload->size > self::MaxBytes) {
            throw ValidationException::withMessages(['photo_uploads' => 'Ukuran file tidak sesuai atau melebihi 2 MB.']);
        }
        $stream = $disk->readStream($upload->staging_key);
        if (! is_resource($stream)) {
            throw new RuntimeException('Foto sementara gagal dibaca.');
        }
        try {
            $bytes = stream_get_contents($stream, self::MaxBytes + 1);
        } finally {
            fclose($stream);
        }
        if ($bytes === false || strlen($bytes) !== $upload->size) {
            throw ValidationException::withMessages(['photo_uploads' => 'Isi file tidak sesuai.']);
        }
        $temporary = tempnam(sys_get_temp_dir(), 'warehouse-photo-');
        if ($temporary === false) {
            throw new RuntimeException('File validasi sementara gagal dibuat.');
        }
        try {
            file_put_contents($temporary, $bytes);
            $file = new UploadedFile($temporary, basename($upload->public_key), null, null, true);
            $validator = Validator::make(['photo_uploads' => $file], [
                'photo_uploads' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            ]);
            $validator->validate();
            if ($file->getMimeType() !== $upload->mime) {
                throw ValidationException::withMessages(['photo_uploads' => 'Tipe file tidak sesuai dengan izin upload.']);
            }
            if (! Storage::disk('warehouse_photos')->put($upload->public_key, $bytes, [
                'ContentType' => $upload->mime, 'CacheControl' => 'public, max-age=31536000, immutable',
            ])) {
                throw new RuntimeException('Foto gagal dipublikasikan.');
            }
        } finally {
            unlink($temporary);
        }
    }

    /** Must run inside the warehouse transaction.
     * @param  array<int, string>  $urls
     */
    public function queueDeletion(Gudang $warehouse, array $urls): void
    {
        if (! $this->enabled() || $urls === []) {
            return;
        }
        WarehousePhotoUpload::query()->where('warehouse_id', $warehouse->id)
            ->whereIn('status', ['attached', 'clean'])->whereIn('public_url', $urls)
            ->update(['status' => 'delete_pending', 'updated_at' => now()]);
    }

    public function cleanup(int $limit = 100): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $ids = WarehousePhotoUpload::query()->where(function (Builder $query): void {
            $query->where('status', 'delete_pending')->orWhere(function (Builder $pending): void {
                $pending->whereIn('status', ['pending', 'canceled'])->where('expires_at', '<', now());
            })->orWhere(function (Builder $attached): void {
                $attached->where('status', 'attached')->where('expires_at', '<', now());
            });
        })->orderBy('updated_at')->limit($limit)->pluck('id');
        $cleaned = 0;
        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($id): void {
                    $upload = WarehousePhotoUpload::query()->whereKey($id)->lockForUpdate()->first();
                    if (! $upload || $upload->status === 'deleted' || $upload->status === 'clean'
                        || ($upload->status !== 'delete_pending' && ! $upload->expires_at->isPast())) {
                        return;
                    }
                    $this->deleteObject('warehouse_staging', $upload->staging_key);
                    if ($upload->status !== 'attached') {
                        if (Gudang::query()->whereJsonContains('foto', $upload->public_url)->exists()) {
                            throw new RuntimeException('Foto masih digunakan; penghapusan dibatalkan.');
                        }
                        $this->deleteObject('warehouse_photos', $upload->public_key);
                        $upload->update(['status' => 'deleted']);
                    } else {
                        $upload->update(['status' => 'clean']);
                    }
                });
                $cleaned++;
            } catch (Throwable) {
                logger()->warning('Pembersihan foto gagal; akan dicoba ulang.', ['upload_id' => $id]);
            }
        }

        return $cleaned;
    }

    private function deleteObject(string $disk, string $key): void
    {
        $storage = Storage::disk($disk);
        if ($storage->exists($key) && ! $storage->delete($key)) {
            throw new RuntimeException('Object gagal dihapus.');
        }
    }
}
