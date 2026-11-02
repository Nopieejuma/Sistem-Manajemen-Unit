<?php

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use App\Models\WarehousePhotoUpload;
use App\Services\WarehousePhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WarehousePhotoUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminSqliteSchema();
        $migration = require database_path('migrations/2026_11_02_083328_create_warehouse_photo_uploads_table.php');
        $migration->up();
    }

    private function storage(): void
    {
        config(['filesystems.warehouse_direct_upload' => true,
            'filesystems.disks.warehouse_staging.bucket' => 'private-test',
            'filesystems.disks.warehouse_photos.bucket' => 'public-test']);
        Storage::fake('warehouse_photos', ['url' => 'https://photos.example.test/public']);
        Storage::fake('warehouse_staging')->buildTemporaryUploadUrlsUsing(
            fn (string $path, \DateTimeInterface $expiration, array $options): array => [
                'url' => 'https://uploads.example.test/'.$path.'?signed=test',
                'headers' => ['Content-Type' => [$options['ContentType']]],
            ],
        );
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['unit_code' => 'A-01', 'blok' => 'A', 'tipe_gudang' => Gudang::WarehouseTypes[0],
            'tinggi' => 8, 'kavling_l' => 10, 'kavling_p' => 12,
            'lt1_l' => 5, 'lt1_p' => 6, 'lt2_l' => 5, 'lt2_p' => 6, 'status' => 'tersedia'], $extra);
    }

    private function warehouse(array $photos = []): Gudang
    {
        return Gudang::query()->create($this->payload(['foto' => $photos]));
    }

    private function staged(User $admin, ?Gudang $warehouse = null, ?string $bytes = null): WarehousePhotoUpload
    {
        $bytes ??= base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a7WQAAAAASUVORK5CYII=');
        $response = $this->actingAs($admin)->postJson(route('admin.warehouse-photo-uploads.store'), [
            'warehouse_id' => $warehouse?->id, 'files' => [['mime' => 'image/png', 'size' => strlen($bytes)]],
        ])->assertOk()->assertJsonStructure(['uploads' => [['id', 'url', 'headers']]]);
        $upload = WarehousePhotoUpload::query()->findOrFail($response->json('uploads.0.id'));
        Storage::disk('warehouse_staging')->put($upload->staging_key, $bytes);

        return $upload;
    }

    public function test_guest_cannot_request_or_cancel_upload(): void
    {
        $this->storage();
        $this->postJson(route('admin.warehouse-photo-uploads.store'), [])->assertUnauthorized();
        $this->deleteJson('/admin/warehouse-photo-uploads/00000000-0000-0000-0000-000000000000')->assertUnauthorized();
        $this->assertDatabaseCount('warehouse_photo_uploads', 0);
    }

    #[TestWith(['image/svg+xml', 10])]
    #[TestWith(['image/png', 2097153])]
    #[TestWith(['image/png', 0])]
    public function test_invalid_metadata_is_rejected(string $mime, int $size): void
    {
        $this->storage();
        $this->actingAs(User::factory()->create())->postJson(route('admin.warehouse-photo-uploads.store'), [
            'files' => [['mime' => $mime, 'size' => $size]],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_photo_uploads', 0);
    }

    public function test_more_than_twenty_uploads_are_rejected(): void
    {
        $this->storage();
        $this->actingAs(User::factory()->create())->postJson(route('admin.warehouse-photo-uploads.store'), [
            'files' => array_fill(0, 21, ['mime' => 'image/png', 'size' => 100]),
        ])->assertUnprocessable()->assertJsonValidationErrors('files');
    }

    public function test_validated_upload_is_attached_and_customer_sees_https_photo(): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create());
        $this->postJson(route('admin.gudang.store'), $this->payload(['photo_uploads' => [$upload->id]]))->assertRedirect();
        $warehouse = Gudang::query()->firstOrFail();
        $this->assertSame([$upload->public_url], $warehouse->foto);
        $this->assertSame('attached', $upload->refresh()->status);
        Storage::disk('warehouse_photos')->assertExists($upload->public_key);
        $this->get(route('customer.gudang.show', $warehouse->id))->assertOk()->assertSee($upload->public_url);
        $this->postJson(route('admin.gudang.store'), $this->payload(['unit_code' => 'A-02', 'photo_uploads' => [$upload->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('photo_uploads');
        $this->assertDatabaseCount('gudang', 1);
    }

    public function test_other_admin_cannot_attach_or_cancel_an_upload(): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create());
        $this->actingAs(User::factory()->create())->deleteJson(route('admin.warehouse-photo-uploads.destroy', $upload))->assertForbidden();
        $this->postJson(route('admin.gudang.store'), $this->payload(['photo_uploads' => [$upload->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('photo_uploads');
        $this->assertDatabaseCount('gudang', 0);
        Storage::disk('warehouse_photos')->assertMissing($upload->public_key);
    }

    #[TestWith(['missing'])]
    #[TestWith(['invalid-content'])]
    #[TestWith(['size'])]
    #[TestWith(['expired'])]
    #[TestWith(['wrong-warehouse'])]
    public function test_invalid_or_wrong_target_cannot_be_published(string $failure): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create(), $failure === 'wrong-warehouse' ? $this->warehouse() : null,
            $failure === 'invalid-content' ? '<?php echo "bad";' : null);
        if ($failure === 'missing') {
            Storage::disk('warehouse_staging')->delete($upload->staging_key);
        }
        if ($failure === 'size') {
            Storage::disk('warehouse_staging')->put($upload->staging_key, 'short');
        }
        if ($failure === 'expired') {
            $upload->update(['expires_at' => now()->subMinute()]);
        }
        $this->postJson(route('admin.gudang.store'), $this->payload(['unit_code' => 'A-02', 'photo_uploads' => [$upload->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('photo_uploads');
        Storage::disk('warehouse_photos')->assertMissing($upload->public_key);
        $this->assertSame('pending', $upload->refresh()->status);
    }

    public function test_edit_limit_includes_retained_photos_and_allows_replacement(): void
    {
        $this->storage();
        $old = array_map(fn (int $n): string => 'https://legacy.example.test/'.$n.'.jpg', range(1, 20));
        $warehouse = $this->warehouse($old);
        $upload = $this->staged(User::factory()->create(), $warehouse);
        $this->putJson(route('admin.gudang.update', $warehouse), $this->payload(['photo_uploads' => [$upload->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('foto');
        $this->assertSame($old, $warehouse->refresh()->foto);
        $this->putJson(route('admin.gudang.update', $warehouse), $this->payload(['photo_uploads' => [$upload->id], 'hapus_foto' => [$old[0]]]))->assertRedirect();
        $this->assertCount(20, $warehouse->refresh()->foto);
        $this->assertContains($upload->public_url, $warehouse->foto);
        $this->assertContains($old[1], $warehouse->foto);
    }

    public function test_removal_is_tracked_and_cleanup_preserves_legacy_and_other_photos(): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create());
        $this->postJson(route('admin.gudang.store'), $this->payload(['photo_uploads' => [$upload->id]]))->assertRedirect();
        $warehouse = Gudang::query()->firstOrFail();
        $warehouse->update(['foto' => [$upload->public_url, 'https://legacy.example.test/photo.jpg']]);
        Storage::disk('warehouse_photos')->put('gudang/unrelated.png', 'untouched');
        $this->putJson(route('admin.gudang.update', $warehouse), $this->payload(['hapus_foto' => [$upload->public_url]]))->assertRedirect();
        $this->assertSame('delete_pending', $upload->refresh()->status);
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        Storage::disk('warehouse_photos')->assertMissing($upload->public_key);
        Storage::disk('warehouse_staging')->assertMissing($upload->staging_key);
        Storage::disk('warehouse_photos')->assertExists('gudang/unrelated.png');
        $this->assertSame(['https://legacy.example.test/photo.jpg'], $warehouse->refresh()->foto);
    }

    public function test_canceled_upload_cannot_attach_and_cleanup_waits_out_signed_url(): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create());
        $this->deleteJson(route('admin.warehouse-photo-uploads.destroy', $upload))->assertOk();
        $this->postJson(route('admin.gudang.store'), $this->payload(['photo_uploads' => [$upload->id]]))->assertUnprocessable();
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        Storage::disk('warehouse_staging')->assertExists($upload->staging_key);
        $this->travel(7)->minutes();
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        Storage::disk('warehouse_staging')->assertMissing($upload->staging_key);
        $this->assertSame('deleted', $upload->refresh()->status);
    }

    public function test_orphan_publication_after_rollback_is_cleaned(): void
    {
        $this->storage();
        $admin = User::factory()->create();
        $upload = $this->staged($admin);
        DB::beginTransaction();
        $warehouse = $this->warehouse();
        app(WarehousePhotoStorage::class)->attach([$upload->id], $admin, $warehouse, true);
        DB::rollBack();
        Storage::disk('warehouse_photos')->assertExists($upload->public_key);
        $this->travel(61)->minutes();
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        Storage::disk('warehouse_photos')->assertMissing($upload->public_key);
        $this->assertSame('deleted', $upload->refresh()->status);
    }

    public function test_attached_cleanup_keeps_public_photo_and_later_deletion_is_retried(): void
    {
        $this->storage();
        $upload = $this->staged(User::factory()->create());
        $this->postJson(route('admin.gudang.store'), $this->payload(['photo_uploads' => [$upload->id]]))->assertRedirect();
        $warehouse = Gudang::query()->firstOrFail();
        $this->travel(61)->minutes();
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        $this->assertSame('clean', $upload->refresh()->status);
        Storage::disk('warehouse_staging')->assertMissing($upload->staging_key);
        Storage::disk('warehouse_photos')->assertExists($upload->public_key);
        $this->deleteJson(route('admin.gudang.destroy', $warehouse))->assertRedirect();
        $this->assertSame('delete_pending', $upload->refresh()->status);
        $disk = Storage::disk('warehouse_photos');
        $unavailable = \Mockery::mock($disk)->makePartial();
        $unavailable->shouldReceive('delete')->once()->andThrow(new \RuntimeException('Simulated storage outage'));
        Storage::set('warehouse_photos', $unavailable);
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        $this->assertSame('delete_pending', $upload->refresh()->status);
        Storage::set('warehouse_photos', $disk);
        $this->artisan('warehouse-photos:cleanup')->assertSuccessful();
        $disk->assertMissing($upload->public_key);
        $this->assertSame('deleted', $upload->refresh()->status);
    }

    public function test_s3_upload_permission_uses_real_signer_without_any_network_request(): void
    {
        config(['filesystems.warehouse_direct_upload' => true,
            'filesystems.disks.warehouse_staging' => [
                'driver' => 's3', 'region' => 'test-region', 'bucket' => 'private-test',
                'endpoint' => 'https://storage.example.test', 'use_path_style_endpoint' => true,
                'key' => 'synthetic-test-key', 'secret' => str_repeat('x', 40),
            ], 'filesystems.disks.warehouse_photos.bucket' => 'public-test']);
        Storage::fake('warehouse_photos', ['url' => 'https://photos.example.test']);
        $response = $this->actingAs(User::factory()->create())->postJson(route('admin.warehouse-photo-uploads.store'), [
            'files' => [['mime' => 'image/png', 'size' => 100]],
        ])->assertOk();
        $url = $response->json('uploads.0.url');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('storage.example.test', parse_url($url, PHP_URL_HOST));
        $this->assertSame('AWS4-HMAC-SHA256', $query['X-Amz-Algorithm']);
        $this->assertLessThanOrEqual(300, (int) $query['X-Amz-Expires']);
        $response->assertDontSee(str_repeat('x', 40));
        Storage::disk('warehouse_photos')->assertDirectoryEmpty('/');
    }

    public function test_local_upload_limit_counts_retained_photos_without_using_s3(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $warehouse = $this->warehouse(array_fill(0, 20, 'gudang/legacy.png'));
        $this->putJson(route('admin.gudang.update', $warehouse), $this->payload(['foto' => [UploadedFile::fake()->image('new.png')]]))
            ->assertUnprocessable()->assertJsonValidationErrors('foto');
        $this->assertCount(20, $warehouse->refresh()->foto);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->postJson(route('admin.warehouse-photo-uploads.store'), [])->assertNotFound();
    }
}
