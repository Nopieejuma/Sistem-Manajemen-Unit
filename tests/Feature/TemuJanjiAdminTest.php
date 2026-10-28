<?php

namespace Tests\Feature;

use App\Models\TemuJanji;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TemuJanjiAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminSqliteSchema();
        $this->actingAs(User::factory()->create());
        $this->createWarehouse();
    }

    public function test_admin_can_view_booking_list_and_search_by_code_company_or_pic(): void
    {
        $this->createBooking(['kode_booking' => 'BK-CODE-001']);
        $this->createBooking(['kode_booking' => 'BK-COMPANY-002', 'nama_perusahaan' => 'PT Nusantara']);
        $this->createBooking(['kode_booking' => 'BK-PIC-003', 'nama_pic' => 'Budi Santoso']);

        $this->get(route('temu-janji.index'))->assertOk()->assertSee('BK-CODE-001');
        $this->get(route('temu-janji.index', ['q' => 'COMPANY']))
            ->assertOk()->assertSee('BK-COMPANY-002')->assertDontSee('BK-CODE-001');
        $this->get(route('temu-janji.index', ['q' => 'Nusantara']))
            ->assertOk()->assertSee('BK-COMPANY-002')->assertDontSee('BK-PIC-003');
        $this->get(route('temu-janji.index', ['q' => 'Budi']))
            ->assertOk()->assertSee('BK-PIC-003')->assertDontSee('BK-CODE-001');
    }

    public function test_admin_can_filter_bookings_by_status_and_visit_date(): void
    {
        $this->createBooking(['kode_booking' => 'BK-FILTER-001', 'tanggal_kunjungan' => '2026-11-10', 'status' => 'pending']);
        $this->createBooking(['kode_booking' => 'BK-FILTER-002', 'tanggal_kunjungan' => '2026-11-10', 'status' => 'approved']);
        $this->createBooking(['kode_booking' => 'BK-FILTER-003', 'tanggal_kunjungan' => '2026-11-11', 'status' => 'pending']);

        $this->get(route('temu-janji.index', ['status' => 'approved']))
            ->assertOk()->assertSee('BK-FILTER-002')->assertDontSee('BK-FILTER-001');
        $this->get(route('temu-janji.index', ['tanggal_kunjungan' => '2026-11-10']))
            ->assertOk()->assertSee('BK-FILTER-001')->assertSee('BK-FILTER-002')->assertDontSee('BK-FILTER-003');
    }

    public function test_admin_can_view_booking_details_and_customer_capacity_count(): void
    {
        $booking = $this->createBooking(['status' => 'pending']);
        DB::table('jadwal')->insert([
            'id' => '00000000-0000-4000-8000-000000000010',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'kuota' => 5,
            'status_aktif' => true,
        ]);
        $this->createBooking(['kode_booking' => 'BK-CAP-APPROVED', 'status' => 'approved']);
        $this->createBooking(['kode_booking' => 'BK-CAP-COMPLETED', 'status' => 'completed']);
        $this->createBooking(['kode_booking' => 'BK-CAP-REJECTED', 'status' => 'rejected']);
        $this->createBooking(['kode_booking' => 'BK-CAP-CANCELED', 'status' => 'canceled']);
        $this->createBooking(['kode_booking' => 'BK-CAP-OTHER-TIME', 'jam_mulai' => '10:00:00', 'jam_selesai' => '10:45:00']);

        $this->get(route('temu-janji.show', $booking))
            ->assertOk()
            ->assertSee('PT Contoh Perusahaan')
            ->assertSee('Siti PIC')
            ->assertSee('customer@example.com')
            ->assertSee('081234567890')
            ->assertSee('Unit A-01')
            ->assertSee('2026')
            ->assertSee('09:00–09:45')
            ->assertSee('Mohon siapkan akses loading dock.')
            ->assertSee('3 / 5 booking');
    }

    public function test_guest_cannot_access_booking_list_detail_or_status_actions(): void
    {
        $booking = $this->createBooking();
        auth()->logout();

        $this->get(route('temu-janji.index'))->assertRedirect(route('login', absolute: false));
        $this->get(route('temu-janji.show', $booking))->assertRedirect(route('login', absolute: false));
        $this->post(route('temu-janji.approve', $booking))->assertRedirect(route('login', absolute: false));
    }

    #[TestWith(['pending', 'approve', 'approved'])]
    #[TestWith(['pending', 'reject', 'rejected'])]
    #[TestWith(['pending', 'cancel', 'canceled'])]
    #[TestWith(['approved', 'cancel', 'canceled'])]
    #[TestWith(['approved', 'complete', 'completed'])]
    public function test_admin_can_apply_allowed_status_transitions(string $initialStatus, string $action, string $expectedStatus): void
    {
        $booking = $this->createBooking(['status' => $initialStatus]);

        $this->from(route('temu-janji.show', $booking))
            ->post(route('temu-janji.'.$action, $booking))
            ->assertRedirect(route('temu-janji.show', $booking));

        $this->assertDatabaseHas('temu_janji', ['id' => $booking->id, 'status' => $expectedStatus]);
        $expectedWarehouseStatus = match ($expectedStatus) {
            'approved' => 'sedang di-keep',
            'completed', 'canceled' => 'tersedia',
            default => 'tersedia',
        };
        $this->assertDatabaseHas('gudang', ['id' => $booking->gudang_id, 'status' => $expectedWarehouseStatus]);
    }

    public function test_keep_remains_until_last_approved_booking_is_released(): void
    {
        $firstBooking = $this->createBooking();
        $secondBooking = $this->createBooking();

        $this->post(route('temu-janji.approve', $firstBooking))->assertRedirect();
        $this->post(route('temu-janji.approve', $secondBooking))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $firstBooking->gudang_id, 'status' => 'sedang di-keep']);

        $this->post(route('temu-janji.cancel', $firstBooking))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $firstBooking->gudang_id, 'status' => 'sedang di-keep']);

        $this->post(route('temu-janji.complete', $secondBooking))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $firstBooking->gudang_id, 'status' => 'tersedia']);
    }

    #[TestWith(['tidak tersedia', 'cancel'])]
    #[TestWith(['digunakan', 'cancel'])]
    #[TestWith(['tidak tersedia', 'complete'])]
    #[TestWith(['digunakan', 'complete'])]
    public function test_releasing_booking_does_not_overwrite_operational_warehouse_status(string $warehouseStatus, string $action): void
    {
        DB::table('gudang')->where('id', '00000000-0000-4000-8000-000000000001')->update(['status' => $warehouseStatus]);
        $booking = $this->createBooking(['status' => 'approved']);

        $this->post(route('temu-janji.'.$action, $booking))->assertRedirect();

        $this->assertDatabaseHas('gudang', ['id' => $booking->gudang_id, 'status' => $warehouseStatus]);
    }

    #[TestWith(['tidak tersedia'])]
    #[TestWith(['digunakan'])]
    public function test_approval_is_rejected_for_operational_warehouse_status(string $warehouseStatus): void
    {
        DB::table('gudang')->where('id', '00000000-0000-4000-8000-000000000001')->update(['status' => $warehouseStatus]);
        $booking = $this->createBooking(['status' => 'pending']);

        $this->from(route('temu-janji.show', $booking))
            ->post(route('temu-janji.approve', $booking))
            ->assertRedirect(route('temu-janji.show', $booking))
            ->assertSessionHasErrors('gudang_id');

        $this->assertDatabaseHas('temu_janji', ['id' => $booking->id, 'status' => 'pending']);
        $this->assertDatabaseHas('gudang', ['id' => $booking->gudang_id, 'status' => $warehouseStatus]);
    }

    #[TestWith(['pending', 'complete'])]
    #[TestWith(['approved', 'approve'])]
    #[TestWith(['approved', 'reject'])]
    #[TestWith(['rejected', 'approve'])]
    #[TestWith(['rejected', 'cancel'])]
    #[TestWith(['rejected', 'complete'])]
    #[TestWith(['completed', 'approve'])]
    #[TestWith(['completed', 'reject'])]
    #[TestWith(['completed', 'cancel'])]
    #[TestWith(['completed', 'complete'])]
    #[TestWith(['canceled', 'approve'])]
    #[TestWith(['canceled', 'reject'])]
    #[TestWith(['canceled', 'cancel'])]
    #[TestWith(['canceled', 'complete'])]
    public function test_admin_cannot_apply_disallowed_or_final_status_transitions(string $initialStatus, string $action): void
    {
        $booking = $this->createBooking(['status' => $initialStatus]);

        $this->from(route('temu-janji.show', $booking))
            ->post(route('temu-janji.'.$action, $booking))
            ->assertRedirect(route('temu-janji.show', $booking))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('temu_janji', ['id' => $booking->id, 'status' => $initialStatus]);
    }

    public function test_stale_admin_action_is_rejected_after_another_status_change(): void
    {
        $booking = $this->createBooking(['status' => 'pending']);

        $this->from(route('temu-janji.show', $booking))->post(route('temu-janji.cancel', $booking))->assertRedirect();
        $this->from(route('temu-janji.show', $booking))
            ->post(route('temu-janji.approve', $booking))
            ->assertRedirect(route('temu-janji.show', $booking))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('temu_janji', ['id' => $booking->id, 'status' => 'canceled']);
    }

    public function test_canceling_a_booking_releases_its_capacity(): void
    {
        $booking = $this->createBooking(['status' => 'approved']);
        DB::table('jadwal')->insert([
            'id' => '00000000-0000-4000-8000-000000000010',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'kuota' => 1,
            'status_aktif' => true,
        ]);

        $this->get(route('temu-janji.show', $booking))->assertSee('1 / 1 booking');

        $this->post(route('temu-janji.cancel', $booking))->assertRedirect();

        $this->get(route('temu-janji.show', $booking))->assertSee('0 / 1 booking');
    }

    public function test_booking_list_rejects_unknown_status_filter(): void
    {
        $this->get(route('temu-janji.index', ['status' => 'keep']))->assertSessionHasErrors('status');
    }

    /** @param array<string, mixed> $attributes */
    private function createWarehouse(array $attributes = []): void
    {
        DB::table('gudang')->insert(array_replace([
            'id' => '00000000-0000-4000-8000-000000000001',
            'unit_code' => 'A-01',
            'blok' => 'A',
            'tipe_gudang' => 'Versa',
            'status' => 'tersedia',
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function createBooking(array $attributes = []): TemuJanji
    {
        $data = array_replace([
            'id' => (string) Str::uuid(),
            'kode_booking' => 'BK-'.strtoupper(bin2hex(random_bytes(6))),
            'gudang_id' => '00000000-0000-4000-8000-000000000001',
            'nama_perusahaan' => 'PT Contoh Perusahaan',
            'nama_pic' => 'Siti PIC',
            'email' => 'customer@example.com',
            'no_telepon' => '081234567890',
            'tanggal_kunjungan' => '2026-11-10',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'catatan' => 'Mohon siapkan akses loading dock.',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
        DB::table('temu_janji')->insert($data);

        return TemuJanji::query()->findOrFail($data['id']);
    }
}
