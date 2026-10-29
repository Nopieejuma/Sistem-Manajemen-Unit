<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminSqliteSchema();
        $this->actingAs(User::factory()->create());
        $this->createWarehouse('00000000-0000-4000-8000-000000000001', 'A-01');
    }

    public function test_dashboard_shows_total_warehouse_count_and_four_status_distribution(): void
    {
        $this->createWarehouse('00000000-0000-4000-8000-000000000002', 'A-02', 'tersedia');
        $this->createWarehouse('00000000-0000-4000-8000-000000000003', 'A-03', 'sedang di-keep');
        $this->createWarehouse('00000000-0000-4000-8000-000000000004', 'A-04', 'tidak tersedia');
        $this->createWarehouse('00000000-0000-4000-8000-000000000005', 'A-05', 'tidak tersedia');
        $this->createWarehouse('00000000-0000-4000-8000-000000000006', 'A-06', 'digunakan');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalWarehouses', 6)
            ->assertViewHas('warehouseCounts', [
                'tersedia' => 2,
                'sedang di-keep' => 1,
                'tidak tersedia' => 2,
                'digunakan' => 1,
            ])
            ->assertViewHas('warehouseChartLabels', ['Tersedia', 'Sedang di-keep', 'Tidak tersedia', 'Digunakan'])
            ->assertViewHas('warehouseChartValues', [2, 1, 2, 1])
            ->assertViewHas('warehousePercentages', [
                'tersedia' => 33.3,
                'sedang di-keep' => 16.7,
                'tidak tersedia' => 33.3,
                'digunakan' => 16.7,
            ]);
    }

    public function test_dashboard_summarizes_all_booking_statuses(): void
    {
        foreach (['pending', 'approved', 'rejected', 'completed', 'canceled', 'pending', 'approved'] as $status) {
            $this->createBooking(['status' => $status]);
        }

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('bookingCounts', [
                'pending' => 2,
                'approved' => 2,
                'rejected' => 1,
                'completed' => 1,
                'canceled' => 1,
            ]);
    }

    public function test_active_appointment_counts_use_jakarta_today_and_monday_to_sunday_week_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 17:30:00', 'UTC'));
        $this->createBooking(['tanggal_kunjungan' => '2026-11-16', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-16', 'status' => 'approved']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-22', 'status' => 'approved']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-15', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-23', 'status' => 'pending']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-16', 'status' => 'completed']);
        $this->createBooking(['tanggal_kunjungan' => '2026-11-16', 'status' => 'rejected']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('activeAppointmentsToday', 2)
            ->assertViewHas('activeAppointmentsThisWeek', 3);
    }

    public function test_recent_bookings_are_ordered_by_created_at_and_eager_load_the_warehouse(): void
    {
        $bookingCodes = [];

        for ($number = 1; $number <= 6; $number++) {
            $bookingCodes[] = 'BK-RECENT-'.$number;
            $this->createBooking([
                'kode_booking' => 'BK-RECENT-'.$number,
                'created_at' => Carbon::parse('2026-11-01 09:00:00', 'UTC')->addMinutes($number)->toDateTimeString(),
            ]);
        }
        $this->createBooking([
            'kode_booking' => 'BK-RECENT-NULL-DATE',
            'created_at' => null,
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('recentBookings', function ($bookings) use ($bookingCodes): bool {
                return $bookings->count() === 5
                    && $bookings->pluck('kode_booking')->all() === array_reverse(array_slice($bookingCodes, 1))
                    && $bookings->every(fn ($booking): bool => $booking->relationLoaded('gudang')
                        && $booking->gudang?->unit_code === 'A-01');
            });
    }

    public function test_dashboard_handles_empty_tables_with_zero_values_and_empty_booking_message(): void
    {
        DB::table('temu_janji')->delete();
        DB::table('gudang')->delete();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada data janji temu.')
            ->assertViewHas('totalWarehouses', 0)
            ->assertViewHas('warehouseCounts', [
                'tersedia' => 0,
                'sedang di-keep' => 0,
                'tidak tersedia' => 0,
                'digunakan' => 0,
            ])
            ->assertViewHas('warehousePercentages', [
                'tersedia' => 0.0,
                'sedang di-keep' => 0.0,
                'tidak tersedia' => 0.0,
                'digunakan' => 0.0,
            ])
            ->assertViewHas('warehouseChartValues', [0, 0, 0, 0])
            ->assertViewHas('bookingCounts', [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'completed' => 0,
                'canceled' => 0,
            ])
            ->assertViewHas('activeAppointmentsToday', 0)
            ->assertViewHas('activeAppointmentsThisWeek', 0)
            ->assertViewHas('recentBookings', fn ($bookings): bool => $bookings->isEmpty());
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        auth()->logout();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login', absolute: false));
    }

    private function createWarehouse(string $id, string $unitCode, string $status = 'tersedia'): void
    {
        DB::table('gudang')->insert([
            'id' => $id,
            'unit_code' => $unitCode,
            'blok' => 'A',
            'tipe_gudang' => 'Versa',
            'status' => $status,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createBooking(array $attributes = []): void
    {
        DB::table('temu_janji')->insert(array_replace([
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
            'catatan' => null,
            'status' => 'pending',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $attributes));
    }
}
