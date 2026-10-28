<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class JadwalCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminSqliteSchema();
        $this->actingAs(User::factory()->create());
        $this->createWarehouse();
    }

    public function test_admin_can_view_schedule_list_and_forms(): void
    {
        $schedule = $this->createSchedule();

        $this->get(route('jadwal.index'))->assertOk()->assertSee('09:00–09:45');
        $this->get(route('jadwal.create'))->assertOk()->assertSee('Tambah Jadwal');
        $this->get(route('jadwal.edit', $schedule))->assertOk()->assertSee('Edit Jadwal');
    }

    public function test_admin_can_create_schedule_for_customer_side(): void
    {
        $this->post(route('jadwal.store'), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 3,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.index', absolute: false));

        $this->assertDatabaseHas('jadwal', [
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'kuota' => 3,
            'status_aktif' => true,
        ]);
    }

    public function test_admin_can_edit_schedule_time_and_quota_without_booking_history(): void
    {
        $schedule = $this->createSchedule(['jam_mulai' => '09:00:00', 'jam_selesai' => '09:45:00']);

        $this->put(route('jadwal.update', $schedule), [
            'jam_mulai' => '10:00',
            'jam_selesai' => '10:45',
            'kuota' => 4,
        ])->assertRedirect(route('jadwal.index', absolute: false));

        $this->assertDatabaseHas('jadwal', [
            'id' => $schedule->id,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '10:45:00',
            'kuota' => 4,
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_a_schedule(): void
    {
        $schedule = $this->createSchedule();

        $this->post(route('jadwal.deactivate', $schedule))->assertRedirect();
        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'status_aktif' => false]);

        $this->post(route('jadwal.activate', $schedule))->assertRedirect();
        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'status_aktif' => true]);
    }

    public function test_deactivation_preserves_existing_booking_history_and_status(): void
    {
        $schedule = $this->createSchedule();
        $booking = $this->createBooking(['status' => 'approved']);

        $this->post(route('jadwal.deactivate', $schedule))->assertRedirect();

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'status_aktif' => false]);
        $this->assertDatabaseHas('temu_janji', ['id' => $booking['id'], 'status' => 'approved']);
        $this->assertDatabaseCount('temu_janji', 1);
    }

    public function test_admin_sees_daily_usage_for_exact_time_pair_and_capacity_statuses(): void
    {
        $this->createSchedule(['kuota' => 3]);
        $this->createBooking(['status' => 'pending']);
        $this->createBooking(['status' => 'approved']);
        $this->createBooking(['status' => 'completed']);
        $this->createBooking(['status' => 'rejected']);
        $this->createBooking(['status' => 'canceled']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-11', 'status' => 'pending']);
        $this->createBooking(['jam_mulai' => '10:00:00', 'jam_selesai' => '10:45:00', 'status' => 'pending']);

        $this->get(route('jadwal.index'))
            ->assertOk()
            ->assertSee('2 tanggal tercatat')
            ->assertSee('3 / 3')
            ->assertSee('1 / 3');
    }

    public function test_end_time_must_be_after_start_time_and_quota_must_be_positive(): void
    {
        $this->from(route('jadwal.create'))->post(route('jadwal.store'), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:00',
            'kuota' => 1,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.create'))
            ->assertSessionHasErrors('jam_selesai');

        $this->from(route('jadwal.create'))->post(route('jadwal.store'), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 0,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.create'))->assertSessionHasErrors('kuota');

        $this->assertDatabaseCount('jadwal', 0);
    }

    public function test_active_schedule_cannot_overlap_another_active_schedule(): void
    {
        $this->createSchedule(['jam_mulai' => '09:00:00', 'jam_selesai' => '10:00:00']);

        $this->from(route('jadwal.create'))->post(route('jadwal.store'), [
            'jam_mulai' => '09:30',
            'jam_selesai' => '10:30',
            'kuota' => 2,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.create'))->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseCount('jadwal', 1);
    }

    public function test_inactive_overlapping_schedule_can_be_added_but_cannot_be_activated(): void
    {
        $this->createSchedule(['jam_mulai' => '09:00:00', 'jam_selesai' => '10:00:00']);

        $this->post(route('jadwal.store'), [
            'jam_mulai' => '09:30',
            'jam_selesai' => '10:30',
            'kuota' => 2,
            'status_aktif' => '0',
        ])->assertRedirect(route('jadwal.index', absolute: false));

        $inactiveSchedule = Jadwal::query()->where('jam_mulai', '09:30:00')->firstOrFail();
        $this->from(route('jadwal.index'))->post(route('jadwal.activate', $inactiveSchedule))
            ->assertRedirect(route('jadwal.index'))->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseHas('jadwal', ['id' => $inactiveSchedule->id, 'status_aktif' => false]);
    }

    public function test_duplicate_pair_of_inactive_schedule_cannot_be_created(): void
    {
        $this->createSchedule(['status_aktif' => false]);

        $this->from(route('jadwal.create'))->post(route('jadwal.store'), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 2,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.create'))->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseCount('jadwal', 1);
    }

    public function test_quota_cannot_be_lower_than_highest_daily_usage(): void
    {
        $schedule = $this->createSchedule(['kuota' => 4]);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'approved']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'completed']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-11', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'rejected']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'canceled']);

        $this->from(route('jadwal.edit', $schedule))->put(route('jadwal.update', $schedule), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 2,
        ])->assertRedirect(route('jadwal.edit', $schedule))->assertSessionHasErrors('kuota');

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'kuota' => 4]);
    }

    public function test_bookings_on_different_dates_are_not_added_together_for_quota(): void
    {
        $schedule = $this->createSchedule(['kuota' => 3]);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-10', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-11', 'status' => 'approved']);

        $this->put(route('jadwal.update', $schedule), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 1,
        ])->assertRedirect(route('jadwal.index', absolute: false));

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'kuota' => 1]);
    }

    public function test_schedule_time_cannot_change_after_any_booking_history(): void
    {
        $schedule = $this->createSchedule();
        $this->createBooking(['status' => 'canceled']);

        $this->from(route('jadwal.edit', $schedule))->put(route('jadwal.update', $schedule), [
            'jam_mulai' => '10:00',
            'jam_selesai' => '10:45',
            'kuota' => 2,
        ])->assertRedirect(route('jadwal.edit', $schedule))->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseHas('jadwal', [
            'id' => $schedule->id,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
        ]);
    }

    public function test_editing_quota_is_still_allowed_when_booking_history_exists(): void
    {
        $schedule = $this->createSchedule(['kuota' => 2]);
        $this->createBooking(['status' => 'canceled']);

        $this->put(route('jadwal.update', $schedule), [
            'jam_mulai' => '09:00',
            'jam_selesai' => '09:45',
            'kuota' => 5,
        ])->assertRedirect(route('jadwal.index', absolute: false));

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'kuota' => 5]);
    }

    #[TestWith(['10:00', '09:00'])]
    #[TestWith(['09:00', '09:30'])]
    public function test_schedule_creation_rejects_invalid_or_conflicting_time_pair(string $start, string $end): void
    {
        $this->createSchedule(['jam_mulai' => '09:00:00', 'jam_selesai' => '10:00:00']);

        $this->from(route('jadwal.create'))->post(route('jadwal.store'), [
            'jam_mulai' => $start,
            'jam_selesai' => $end,
            'kuota' => 1,
            'status_aktif' => '1',
        ])->assertRedirect(route('jadwal.create'))->assertSessionHasErrors();
    }

    /** @param array<string, mixed> $attributes */
    private function createSchedule(array $attributes = []): Jadwal
    {
        $data = array_replace([
            'id' => (string) Str::uuid(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'kuota' => 2,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
        DB::table('jadwal')->insert($data);

        return Jadwal::query()->findOrFail($data['id']);
    }

    /** @param array<string, mixed> $attributes */
    private function createBooking(array $attributes = []): array
    {
        $data = array_replace([
            'id' => (string) Str::uuid(),
            'kode_booking' => 'BK-'.strtoupper(bin2hex(random_bytes(6))),
            'gudang_id' => '00000000-0000-4000-8000-000000000001',
            'nama_perusahaan' => 'PT Jadwal Test',
            'nama_pic' => 'PIC Jadwal',
            'email' => 'jadwal@example.com',
            'no_telepon' => '081234567890',
            'tanggal_kunjungan' => '2026-11-10',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '09:45:00',
            'catatan' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
        DB::table('temu_janji')->insert($data);

        return $data;
    }

    private function createWarehouse(): void
    {
        DB::table('gudang')->insert([
            'id' => '00000000-0000-4000-8000-000000000001',
            'unit_code' => 'A-01',
            'tipe_gudang' => 'Versa',
            'status' => 'tersedia',
        ]);
    }
}
