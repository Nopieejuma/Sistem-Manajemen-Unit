<?php

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GudangCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminSqliteSchema();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_view_gudang_index_and_create_form(): void
    {
        $this->get(route('admin.gudang.index'))->assertOk();
        $this->get(route('admin.gudang.create'))->assertOk();
    }

    public function test_admin_can_create_gudang(): void
    {
        $this->post('/admin/gudang', $this->validGudangData())->assertRedirect(route('admin.gudang.index', absolute: false));

        $this->assertDatabaseHas('gudang', [
            'unit_code' => 'A-01',
            'luas_kavling' => 120,
            'grand_total' => 82,
        ]);
    }

    public function test_admin_can_edit_and_update_gudang(): void
    {
        $gudang = Gudang::create($this->validGudangData());

        $this->get(route('admin.gudang.edit', $gudang->id))->assertOk();

        $data = $this->validGudangData();
        $data['unit_code'] = 'A-02';
        $data['harga_sewa'] = 75000000;

        $this->put(route('admin.gudang.update', $gudang->id), $data)
            ->assertRedirect(route('admin.gudang.index', absolute: false));

        $this->assertDatabaseHas('gudang', [
            'id' => $gudang->id,
            'unit_code' => 'A-02',
            'harga_sewa' => 75000000,
        ]);
    }

    public function test_admin_can_delete_gudang(): void
    {
        $gudang = Gudang::create($this->validGudangData());

        $this->delete(route('admin.gudang.destroy', $gudang->id))
            ->assertRedirect(route('admin.gudang.index', absolute: false));

        $this->assertDatabaseMissing('gudang', ['id' => $gudang->id]);
    }

    public function test_gudang_detail_route_is_not_registered(): void
    {
        $this->get('/admin/gudang/00000000-0000-4000-8000-000000000000')->assertMethodNotAllowed();
    }

    /**
     * @return array<string, mixed>
     */
    private function validGudangData(): array
    {
        return [
            'unit_code' => 'A-01',
            'blok' => 'A',
            'tipe_gudang' => 'VERTA 56',
            'tinggi' => 8.5,
            'kavling_l' => 10,
            'kavling_p' => 12,
            'lt1_l' => 5,
            'lt1_p' => 6,
            'lt2_l' => 5,
            'lt2_p' => 6,
            'extra_m2' => 10,
            'pos_l' => 3,
            'pos_p' => 4,
            'harga_sewa' => 50000000,
            'fasilitas' => ['CCTV 24 Jam'],
            'status' => 'tersedia',
        ];
    }

    public function test_gudang_creation_validates_enum_code_price_dimensions_facilities_and_status(): void
    {
        $invalid = $this->validGudangData();
        $invalid['unit_code'] = str_repeat('U', 21);
        $invalid['tipe_gudang'] = 'Tipe Tidak Ada';
        $invalid['harga_sewa'] = -1;
        $invalid['tinggi'] = -1;
        $invalid['fasilitas'] = ['Fasilitas Tidak Ada'];
        $invalid['status'] = 'sedang di-keep';

        $this->from(route('admin.gudang.create'))
            ->post(route('admin.gudang.store'), $invalid)
            ->assertRedirect(route('admin.gudang.create'))
            ->assertSessionHasErrors(['unit_code', 'tipe_gudang', 'harga_sewa', 'tinggi', 'fasilitas.0', 'status']);
    }

    public function test_gudang_unit_code_must_be_unique(): void
    {
        Gudang::create($this->validGudangData());

        $this->from(route('admin.gudang.create'))
            ->post(route('admin.gudang.store'), $this->validGudangData())
            ->assertRedirect(route('admin.gudang.create'))
            ->assertSessionHasErrors('unit_code');
    }

    public function test_admin_can_set_manual_warehouse_statuses(): void
    {
        $gudang = Gudang::create($this->validGudangData());

        foreach (['tidak tersedia', 'digunakan', 'tersedia'] as $status) {
            $this->put(route('admin.gudang.update', $gudang->id), array_merge($this->validGudangData(), ['status' => $status]))
                ->assertRedirect(route('admin.gudang.index', absolute: false));

            $this->assertDatabaseHas('gudang', ['id' => $gudang->id, 'status' => $status]);
        }
    }

    public function test_admin_cannot_change_status_while_approved_booking_exists(): void
    {
        $gudang = Gudang::create($this->validGudangData());
        $this->insertBooking($gudang->id, 'approved');

        $this->from(route('admin.gudang.edit', $gudang->id))
            ->put(route('admin.gudang.update', $gudang->id), array_merge($this->validGudangData(), ['status' => 'digunakan']))
            ->assertRedirect(route('admin.gudang.edit', $gudang->id))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('gudang', ['id' => $gudang->id, 'status' => 'tersedia']);
    }

    public function test_admin_can_upload_and_delete_only_selected_owned_photo(): void
    {
        Storage::fake('public');
        $gudang = Gudang::create(array_merge($this->validGudangData(), ['foto' => ['gudang/keep.jpg', 'gudang/remove.jpg']]));
        Storage::disk('public')->put('gudang/keep.jpg', 'keep');
        Storage::disk('public')->put('gudang/remove.jpg', 'remove');

        $data = array_merge($this->validGudangData(), [
            'foto' => [UploadedFile::fake()->image('new.jpg')],
            'hapus_foto' => ['gudang/remove.jpg'],
        ]);

        $this->put(route('admin.gudang.update', $gudang->id), $data)
            ->assertRedirect(route('admin.gudang.index', absolute: false));

        $gudang->refresh();
        $this->assertCount(2, $gudang->foto);
        $this->assertContains('gudang/keep.jpg', $gudang->foto);
        $this->assertNotContains('gudang/remove.jpg', $gudang->foto);
        Storage::disk('public')->assertExists('gudang/keep.jpg');
        Storage::disk('public')->assertMissing('gudang/remove.jpg');
        Storage::disk('public')->assertExists($gudang->foto[1]);
    }

    public function test_admin_cannot_delete_a_photo_not_attached_to_warehouse(): void
    {
        Storage::fake('public');
        $gudang = Gudang::create(array_merge($this->validGudangData(), ['foto' => ['gudang/keep.jpg']]));
        Storage::disk('public')->put('gudang/keep.jpg', 'keep');
        Storage::disk('public')->put('gudang/other.jpg', 'other');

        $this->from(route('admin.gudang.edit', $gudang->id))
            ->put(route('admin.gudang.update', $gudang->id), array_merge($this->validGudangData(), ['hapus_foto' => ['gudang/other.jpg']]))
            ->assertRedirect(route('admin.gudang.edit', $gudang->id))
            ->assertSessionHasErrors('hapus_foto');

        Storage::disk('public')->assertExists('gudang/keep.jpg');
        Storage::disk('public')->assertExists('gudang/other.jpg');
    }

    public function test_admin_cannot_delete_warehouse_with_booking_history_or_its_photos(): void
    {
        Storage::fake('public');
        $gudang = Gudang::create(array_merge($this->validGudangData(), ['foto' => ['gudang/history.jpg']]));
        Storage::disk('public')->put('gudang/history.jpg', 'photo');
        $this->insertBooking($gudang->id, 'rejected');

        $this->from(route('admin.gudang.index'))
            ->delete(route('admin.gudang.destroy', $gudang->id))
            ->assertRedirect(route('admin.gudang.index'))
            ->assertSessionHasErrors('gudang');

        $this->assertDatabaseHas('gudang', ['id' => $gudang->id]);
        Storage::disk('public')->assertExists('gudang/history.jpg');
    }

    public function test_deleting_warehouse_removes_its_owned_photo_after_database_delete(): void
    {
        Storage::fake('public');
        $gudang = Gudang::create(array_merge($this->validGudangData(), ['foto' => ['gudang/orphan.jpg', 'https://customer.example/image.jpg']]));
        Storage::disk('public')->put('gudang/orphan.jpg', 'photo');

        $this->delete(route('admin.gudang.destroy', $gudang->id))->assertRedirect(route('admin.gudang.index', absolute: false));

        $this->assertDatabaseMissing('gudang', ['id' => $gudang->id]);
        Storage::disk('public')->assertMissing('gudang/orphan.jpg');
    }

    public function test_admin_photo_urls_support_relative_paths_and_https_urls(): void
    {
        $gudang = Gudang::create(array_merge($this->validGudangData(), [
            'foto' => ['gudang/photo.jpg', 'https://cdn.example.test/photo.jpg'],
        ]));

        $this->get(route('admin.gudang.index'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('gudang/photo.jpg'))
            ->assertSee('https://cdn.example.test/photo.jpg');
        $this->assertSame('https://cdn.example.test/photo.jpg', $gudang->photoUrl('https://cdn.example.test/photo.jpg'));
    }

    private function insertBooking(string $warehouseId, string $status): void
    {
        DB::table('temu_janji')->insert([
            'id' => (string) Str::uuid(),
            'kode_booking' => 'BK-'.strtoupper(bin2hex(random_bytes(5))),
            'gudang_id' => $warehouseId,
            'nama_perusahaan' => 'PT Test',
            'nama_pic' => 'PIC Test',
            'email' => 'test@example.com',
            'no_telepon' => '08123456789',
            'tanggal_kunjungan' => '2026-11-10',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'status' => $status,
        ]);
    }
}
