<?php

namespace Tests\Integration\Postgres;

use App\Models\Gudang;
use App\Models\TemuJanji;
use App\Models\User;
use App\Support\PostgresTestingGuard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    private const string VisitDate = '2099-12-25';

    private const int AdvisoryLockNamespace = 1296648013;

    private const int AdvisoryLockKey = 4;

    /** @var array<int, string> */
    private array $warehouseIds = [];

    /** @var array<int, string> */
    private array $scheduleIds = [];

    /** @var array<int, int> */
    private array $userIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $lastObservedWorkerActivity = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSafeDatabaseTarget();

        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('gudang_integration_test', $connection->selectOne('SELECT current_database() AS name')->name);
        $this->cleanAbandonedSyntheticFixtures();
    }

    protected function tearDown(): void
    {
        if ($this->warehouseIds !== [] || $this->scheduleIds !== [] || $this->userIds !== []) {
            $this->assertSafeDatabaseTarget();

            DB::transaction(function (): void {
                if ($this->warehouseIds !== []) {
                    DB::table('temu_janji')->whereIn('gudang_id', $this->warehouseIds)->delete();
                }

                if ($this->scheduleIds !== []) {
                    DB::table('jadwal')->whereIn('id', $this->scheduleIds)->delete();
                }

                if ($this->warehouseIds !== []) {
                    DB::table('gudang')->whereIn('id', $this->warehouseIds)->delete();
                }

                if ($this->userIds !== []) {
                    DB::table('user')->whereIn('id', $this->userIds)->delete();
                }
            });
        }

        parent::tearDown();
    }

    public function test_customer_admin_and_customer_lookup_complete_booking_lifecycle(): void
    {
        $warehouse = $this->createWarehouse();
        $schedule = $this->createSchedule(5);
        $this->actingAs($this->createAdmin());

        $this->submitCustomerBooking($warehouse, $schedule, 'lifecycle-pending@example.test')
            ->assertCreated()->assertJsonPath('status', 'pending');
        $approvedBooking = TemuJanji::query()->where('email', 'lifecycle-pending@example.test')->firstOrFail();

        $this->post(route('admin.temu-janji.approve', $approvedBooking))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'sedang di-keep']);
        $this->lookupBooking($approvedBooking, 'Disetujui');

        $this->post(route('admin.temu-janji.complete', $approvedBooking))->assertRedirect();
        $this->assertDatabaseHas('temu_janji', ['id' => $approvedBooking->id, 'status' => 'completed']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
        $this->assertDatabaseMissing('gudang', ['id' => $warehouse->id, 'status' => 'digunakan']);
        $this->lookupBooking($approvedBooking, 'Selesai');

        $this->submitCustomerBooking($warehouse, $schedule, 'lifecycle-rejected@example.test')->assertCreated();
        $rejectedBooking = TemuJanji::query()->where('email', 'lifecycle-rejected@example.test')->firstOrFail();
        $this->post(route('admin.temu-janji.reject', $rejectedBooking))->assertRedirect();
        $this->assertDatabaseHas('temu_janji', ['id' => $rejectedBooking->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
        $this->lookupBooking($rejectedBooking, 'Ditolak');

        $this->submitCustomerBooking($warehouse, $schedule, 'lifecycle-canceled@example.test')->assertCreated();
        $canceledBooking = TemuJanji::query()->where('email', 'lifecycle-canceled@example.test')->firstOrFail();
        $this->post(route('admin.temu-janji.cancel', $canceledBooking))->assertRedirect();
        $this->assertDatabaseHas('temu_janji', ['id' => $canceledBooking->id, 'status' => 'canceled']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
        $this->lookupBooking($canceledBooking, 'Dibatalkan');
    }

    public function test_keep_remains_until_no_approved_booking_remains(): void
    {
        $warehouse = $this->createWarehouse(['status' => 'sedang di-keep']);
        $schedule = $this->createSchedule(3);
        $first = $this->insertBooking($warehouse, $schedule, 'approved');
        $last = $this->insertBooking($warehouse, $schedule, 'approved');
        $this->actingAs($this->createAdmin());

        $this->post(route('admin.temu-janji.cancel', $first))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'sedang di-keep']);

        $this->post(route('admin.temu-janji.complete', $last))->assertRedirect();
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
    }

    public function test_two_customer_processes_cannot_consume_the_last_slot(): void
    {
        $firstWarehouse = $this->createWarehouse();
        $secondWarehouse = $this->createWarehouse();
        $schedule = $this->createSchedule(1);
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->table('jadwal')->where('id', $schedule->id)->lockForUpdate()->firstOrFail();

        $workers = [
            $this->startWorker($this->customerWorkerInput($firstWarehouse, $schedule, 'slot-first@example.test')),
            $this->startWorker($this->customerWorkerInput($secondWarehouse, $schedule, 'slot-second@example.test')),
        ];

        try {
            $waiters = $this->waitForLockWaits($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }

            $results = $this->finishWorkers($workers);
        }

        $this->assertSame(2, $waiters->count(), 'Concurrent customers did not both wait on the slot row: '.json_encode([
            'workers' => $this->lastObservedWorkerActivity,
            'results' => $results,
        ]));
        $this->assertSame(2, $waiters->pluck('pid')->unique()->count());
        $this->assertWorkerConnectionsAreSeparate($results);
        $this->assertSame(1, collect($results)->where('result', 'http')->where('status', 201)->count());
        $this->assertSame(1, collect($results)->where('result', 'validation')->count());

        $this->assertSame(1, DB::table('temu_janji')->where('tanggal_kunjungan', self::VisitDate)
            ->where('jam_mulai', $schedule->jam_mulai)->where('jam_selesai', $schedule->jam_selesai)
            ->whereIn('status', ['pending', 'approved', 'completed'])->count());
    }

    public function test_booking_and_quota_update_serialize_on_the_same_schedule_row(): void
    {
        $firstWarehouse = $this->createWarehouse();
        $secondWarehouse = $this->createWarehouse();
        $schedule = $this->createSchedule(2);
        $this->insertBooking($firstWarehouse, $schedule, 'pending');
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->table('jadwal')->where('id', $schedule->id)->lockForUpdate()->firstOrFail();

        $workers = [
            $this->startWorker([
                'action' => 'schedule_update',
                'schedule_id' => $schedule->id,
                'schedule' => [
                    'jam_mulai' => substr($schedule->jam_mulai, 0, 5),
                    'jam_selesai' => substr($schedule->jam_selesai, 0, 5),
                    'kuota' => 1,
                ],
            ]),
            $this->startWorker($this->customerWorkerInput($secondWarehouse, $schedule, 'quota-race@example.test')),
        ];

        try {
            $waiters = $this->waitForLockWaits($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }

            $results = $this->finishWorkers($workers);
        }

        $this->assertSame(2, $waiters->count(), 'Booking and quota update did not wait on the schedule row: '.json_encode([
            'workers' => $this->lastObservedWorkerActivity,
            'results' => $results,
        ]));
        $this->assertWorkerConnectionsAreSeparate($results);
        $quotaResult = $this->workerResult($results, 'schedule_update');
        $bookingResult = $this->workerResult($results, 'customer_booking');

        $this->assertTrue(
            ($quotaResult['result'] === 'http' && $bookingResult['result'] === 'validation') ||
            ($quotaResult['result'] === 'validation' && $bookingResult['result'] === 'http'),
        );

        $currentSchedule = DB::table('jadwal')->where('id', $schedule->id)->firstOrFail();
        $used = DB::table('temu_janji')->where('tanggal_kunjungan', self::VisitDate)
            ->where('jam_mulai', $schedule->jam_mulai)->where('jam_selesai', $schedule->jam_selesai)
            ->whereIn('status', ['pending', 'approved', 'completed'])->count();
        $this->assertLessThanOrEqual($currentSchedule->kuota, $used);
    }

    public function test_two_simultaneous_approvals_preserve_keep_for_one_warehouse(): void
    {
        $warehouse = $this->createWarehouse();
        $schedule = $this->createSchedule(2);
        $first = $this->insertBooking($warehouse, $schedule, 'pending');
        $second = $this->insertBooking($warehouse, $schedule, 'pending');
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->table('gudang')->where('id', $warehouse->id)->lockForUpdate()->firstOrFail();

        $workers = [
            $this->startWorker(['action' => 'admin_status', 'booking_id' => $first->id, 'transition' => 'approve']),
            $this->startWorker(['action' => 'admin_status', 'booking_id' => $second->id, 'transition' => 'approve']),
        ];

        try {
            $waiters = $this->waitForLockWaits($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }

            $results = $this->finishWorkers($workers);
        }

        $this->assertSame(2, $waiters->count(), 'Approvals did not serialize on the warehouse row: '.json_encode([
            'workers' => $this->lastObservedWorkerActivity,
            'results' => $results,
        ]));
        $this->assertSame(2, $waiters->pluck('pid')->unique()->count());
        $this->assertWorkerConnectionsAreSeparate($results);
        $this->assertSame(2, collect($results)->where('result', 'http')->count());
        $this->assertDatabaseHas('temu_janji', ['id' => $first->id, 'status' => 'approved']);
        $this->assertDatabaseHas('temu_janji', ['id' => $second->id, 'status' => 'approved']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'sedang di-keep']);
    }

    public function test_concurrent_cancel_and_complete_release_keep_after_last_approval(): void
    {
        $warehouse = $this->createWarehouse(['status' => 'sedang di-keep']);
        $schedule = $this->createSchedule(2);
        $cancelBooking = $this->insertBooking($warehouse, $schedule, 'approved');
        $completeBooking = $this->insertBooking($warehouse, $schedule, 'approved');
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->table('gudang')->where('id', $warehouse->id)->lockForUpdate()->firstOrFail();

        $workers = [
            $this->startWorker(['action' => 'admin_status', 'booking_id' => $cancelBooking->id, 'transition' => 'cancel']),
            $this->startWorker(['action' => 'admin_status', 'booking_id' => $completeBooking->id, 'transition' => 'complete']),
        ];

        try {
            $waiters = $this->waitForLockWaits($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }

            $results = $this->finishWorkers($workers);
        }

        $this->assertSame(2, $waiters->count(), 'Cancel and complete did not serialize on the warehouse row: '.json_encode([
            'workers' => $this->lastObservedWorkerActivity,
            'results' => $results,
        ]));
        $this->assertWorkerConnectionsAreSeparate($results);
        $this->assertSame(2, collect($results)->where('result', 'http')->count());
        $this->assertDatabaseHas('temu_janji', ['id' => $cancelBooking->id, 'status' => 'canceled']);
        $this->assertDatabaseHas('temu_janji', ['id' => $completeBooking->id, 'status' => 'completed']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
    }

    public function test_advisory_lock_serializes_overlapping_schedule_creation(): void
    {
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->select('SELECT pg_advisory_xact_lock(?, ?)', [self::AdvisoryLockNamespace, self::AdvisoryLockKey]);

        $workers = [
            $this->startWorker(['action' => 'schedule_store', 'schedule' => ['jam_mulai' => '16:00', 'jam_selesai' => '17:00', 'kuota' => 1, 'status_aktif' => true]]),
            $this->startWorker(['action' => 'schedule_store', 'schedule' => ['jam_mulai' => '16:30', 'jam_selesai' => '17:30', 'kuota' => 1, 'status_aktif' => true]]),
        ];

        try {
            $waiters = $this->waitForLockWaits($workers, 'advisory');
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }

            $results = $this->finishWorkers($workers);
        }

        $this->assertSame(2, $waiters->count(), 'Schedule writes did not wait on the PostgreSQL advisory lock: '.json_encode([
            'workers' => $this->lastObservedWorkerActivity,
            'results' => $results,
        ]));
        $this->assertWorkerConnectionsAreSeparate($results);
        $this->assertSame(1, collect($results)->where('result', 'http')->count());
        $this->assertSame(1, collect($results)->where('result', 'validation')->count());

        $this->scheduleIds = array_merge($this->scheduleIds, DB::table('jadwal')
            ->whereIn('jam_mulai', ['16:00:00', '16:30:00'])->pluck('id')->all());
        $this->assertSame(1, count($this->scheduleIds));
    }

    public function test_customer_booking_rolls_back_after_schedule_row_lock_timeout(): void
    {
        $warehouse = $this->createWarehouse();
        $schedule = $this->createSchedule(1);
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->table('jadwal')->where('id', $schedule->id)->lockForUpdate()->firstOrFail();
        $workers = [$this->startWorker($this->customerWorkerInput($warehouse, $schedule, 'timeout@example.test', 500))];

        try {
            $results = $this->finishWorkers($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }
        }

        $this->assertSame(503, $results[0]['status']);
        $this->assertSame(0, DB::table('temu_janji')->where('email', 'timeout@example.test')->count());
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
    }

    public function test_advisory_lock_timeout_rolls_back_schedule_creation(): void
    {
        $connection = DB::connection();
        $connection->beginTransaction();
        $connection->select('SELECT pg_advisory_xact_lock(?, ?)', [self::AdvisoryLockNamespace, self::AdvisoryLockKey]);
        $workers = [$this->startWorker([
            'action' => 'schedule_store',
            'lock_timeout_ms' => 500,
            'schedule' => ['jam_mulai' => '18:00', 'jam_selesai' => '19:00', 'kuota' => 1, 'status_aktif' => true],
        ])];

        try {
            $results = $this->finishWorkers($workers);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }
        }

        $this->assertSame('exception', $results[0]['result']);
        $this->assertSame('55P03', $results[0]['sql_state']);
        $this->assertSame(0, DB::table('jadwal')->where('jam_mulai', '18:00:00')->count());
    }

    public function test_postgres_detects_a_synthetic_deadlock_and_rolls_back_one_transaction(): void
    {
        $warehouse = $this->createWarehouse();
        $schedule = $this->createSchedule();
        $barrierPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pgsql-deadlock-'.Str::uuid().'.ready';
        $workers = [
            $this->startWorker([
                'action' => 'deadlock_probe', 'barrier_path' => $barrierPath,
                'first_resource' => 'warehouse', 'first_id' => $warehouse->id,
                'second_resource' => 'schedule', 'second_id' => $schedule->id,
            ]),
            $this->startWorker([
                'action' => 'deadlock_probe', 'barrier_path' => $barrierPath,
                'first_resource' => 'schedule', 'first_id' => $schedule->id,
                'second_resource' => 'warehouse', 'second_id' => $warehouse->id,
            ]),
        ];

        try {
            $idleTransactions = $this->waitForIdleTransactions($workers);
        } finally {
            file_put_contents($barrierPath, 'release');
            $results = $this->finishWorkers($workers);

            if (is_file($barrierPath)) {
                unlink($barrierPath);
            }
        }

        $this->assertSame(2, $idleTransactions);
        $this->assertWorkerConnectionsAreSeparate($results);
        $this->assertSame(1, collect($results)->where('result', 'committed')->count());
        $deadlock = collect($results)->firstWhere('result', 'database_error');
        $this->assertNotNull($deadlock);
        $this->assertSame('40P01', $deadlock['sql_state']);
        $this->assertDatabaseHas('gudang', ['id' => $warehouse->id, 'status' => 'tersedia']);
    }

    private function assertSafeDatabaseTarget(): void
    {
        PostgresTestingGuard::assertSafe(config('database'), app()->environment(), [
            'DB_CONNECTION' => getenv('DB_CONNECTION') ?: null,
            'DB_HOST' => getenv('DB_HOST') ?: null,
            'DB_PORT' => getenv('DB_PORT') ?: null,
            'DB_DATABASE' => getenv('DB_DATABASE') ?: null,
            'DB_USERNAME' => getenv('DB_USERNAME') ?: null,
            'DB_URL' => getenv('DB_URL') ?: null,
            'DATABASE_URL' => getenv('DATABASE_URL') ?: null,
        ]);
    }

    private function cleanAbandonedSyntheticFixtures(): void
    {
        $warehouseIds = DB::table('gudang')->where('unit_code', 'like', 'T%')->pluck('id')->all();

        DB::transaction(function () use ($warehouseIds): void {
            if ($warehouseIds !== []) {
                DB::table('temu_janji')->whereIn('gudang_id', $warehouseIds)->delete();
                DB::table('gudang')->whereIn('id', $warehouseIds)->delete();
            }

            DB::table('user')->where('username', 'like', 'test-%')->delete();

            foreach ([
                ['08:00:00', '08:30:00'],
                ['16:00:00', '17:00:00'],
                ['16:30:00', '17:30:00'],
                ['18:00:00', '19:00:00'],
            ] as [$start, $end]) {
                DB::table('jadwal')->where('jam_mulai', $start)->where('jam_selesai', $end)->delete();
            }
        });
    }

    /** @param array<string, mixed> $overrides */
    private function createWarehouse(array $overrides = []): Gudang
    {
        $id = (string) Str::uuid();
        DB::table('gudang')->insert(array_replace([
            'id' => $id,
            'unit_code' => 'T'.Str::upper(Str::random(12)),
            'blok' => 'TEST',
            'tipe_gudang' => 'Versa',
            'luas_kavling' => 100,
            'total_luas_bangunan' => 100,
            'grand_total' => 100,
            'harga_sewa' => 16000000,
            'status' => 'tersedia',
        ], $overrides));
        $this->warehouseIds[] = $id;

        return Gudang::query()->findOrFail($id);
    }

    private function createSchedule(int $quota = 3): object
    {
        $id = (string) Str::uuid();
        DB::table('jadwal')->insert([
            'id' => $id,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '08:30:00',
            'kuota' => $quota,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->scheduleIds[] = $id;

        return DB::table('jadwal')->where('id', $id)->firstOrFail();
    }

    private function createAdmin(): User
    {
        $user = User::query()->create([
            'username' => 'test-'.Str::lower(Str::random(16)),
            'password' => Hash::make(Str::random(40)),
        ]);
        $this->userIds[] = (int) $user->getKey();

        return $user;
    }

    private function insertBooking(Gudang $warehouse, object $schedule, string $status): TemuJanji
    {
        $id = (string) Str::uuid();
        DB::table('temu_janji')->insert([
            'id' => $id,
            'kode_booking' => 'PG-'.Str::upper(Str::random(20)),
            'gudang_id' => $warehouse->id,
            'nama_perusahaan' => 'Perusahaan Test',
            'nama_pic' => 'PIC Test',
            'email' => 'pg-'.Str::lower(Str::random(12)).'@example.test',
            'no_telepon' => '0800000000',
            'tanggal_kunjungan' => self::VisitDate,
            'jam_mulai' => $schedule->jam_mulai,
            'jam_selesai' => $schedule->jam_selesai,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TemuJanji::query()->findOrFail($id);
    }

    private function submitCustomerBooking(Gudang $warehouse, object $schedule, string $email): TestResponse
    {
        return $this->postJson(route('customer.temu-janji.store', ['group' => $warehouse->groupIdentifier()]), [
            'gudang_id' => $warehouse->id,
            'jadwal_id' => $schedule->id,
            'nama_perusahaan' => 'Perusahaan Test',
            'nama_pic' => 'PIC Test',
            'email' => $email,
            'no_telepon' => '0800000000',
            'tanggal_kunjungan' => self::VisitDate,
        ]);
    }

    private function lookupBooking(TemuJanji $booking, string $label): void
    {
        $this->post(route('customer.cek-booking.store'), [
            'kode_booking' => $booking->kode_booking,
            'email' => $booking->email,
        ])->assertOk()->assertSee($label);
    }

    /** @return array<string, mixed> */
    private function customerWorkerInput(Gudang $warehouse, object $schedule, string $email, ?int $timeout = null): array
    {
        $input = [
            'action' => 'customer_booking',
            'group' => $warehouse->groupIdentifier(),
            'booking' => [
                'gudang_id' => $warehouse->id,
                'jadwal_id' => $schedule->id,
                'nama_perusahaan' => 'Perusahaan Paralel',
                'nama_pic' => 'PIC Paralel',
                'email' => $email,
                'no_telepon' => '0800000000',
                'tanggal_kunjungan' => self::VisitDate,
            ],
        ];

        if ($timeout !== null) {
            $input['lock_timeout_ms'] = $timeout;
        }

        return $input;
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function startWorker(array $input): array
    {
        $input['application_name'] = 'pgsql-test-'.Str::uuid();
        $input['ready_path'] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pgsql-worker-'.Str::uuid().'.ready';
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __DIR__.DIRECTORY_SEPARATOR.'ConcurrencyWorker.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
        );

        if (! is_resource($process)) {
            $this->fail('Could not start a PostgreSQL worker process.');
        }

        fwrite($pipes[0], json_encode($input, JSON_THROW_ON_ERROR));
        fclose($pipes[0]);

        return [
            'process' => $process,
            'pipes' => $pipes,
            'application_name' => $input['application_name'],
            'ready_path' => $input['ready_path'],
            'action' => $input['action'],
        ];
    }

    /** @param array<int, array<string, mixed>> $workers
     * @return Collection<int, object>
     */
    private function waitForLockWaits(array $workers, ?string $waitEvent = null): Collection
    {
        $deadline = microtime(true) + 12;
        $observedWaiters = collect();

        do {
            $workerPids = $this->workerBackendPids($workers);
            $activity = $workerPids === [] ? collect() : DB::table('pg_stat_activity')->select([
                'application_name', 'pid', 'state', 'wait_event_type', 'wait_event',
            ])->whereIn('pid', $workerPids)->get();
            $this->lastObservedWorkerActivity = $activity->toArray();
            $query = $activity->where('wait_event_type', 'Lock');

            if ($waitEvent !== null) {
                $query = $query->where('wait_event', $waitEvent);
            }

            $waiting = $query->values();
            $waiting->each(fn (object $worker): mixed => $observedWaiters->put($worker->application_name, $worker));

            if ($observedWaiters->count() === count($workers)) {
                return $observedWaiters->values();
            }

            usleep(25000);
        } while (microtime(true) < $deadline);

        return $observedWaiters->values();
    }

    /** @param array<int, array<string, mixed>> $workers */
    private function waitForIdleTransactions(array $workers): int
    {
        $deadline = microtime(true) + 12;
        $idle = 0;

        do {
            $workerPids = $this->workerBackendPids($workers);
            $idle = $workerPids === [] ? 0 : DB::table('pg_stat_activity')->whereIn('pid', $workerPids)
                ->where('state', 'idle in transaction')->count();

            if ($idle === count($workers)) {
                return $idle;
            }

            usleep(25000);
        } while (microtime(true) < $deadline);

        return $idle;
    }

    /** @param array<int, array<string, mixed>> $workers
     * @return array<int, array<string, mixed>>
     */
    private function finishWorkers(array $workers): array
    {
        $deadline = microtime(true) + 20;
        do {
            $running = 0;
            foreach ($workers as $worker) {
                $running += proc_get_status($worker['process'])['running'] ? 1 : 0;
            }

            if ($running === 0) {
                break;
            }

            usleep(25000);
        } while (microtime(true) < $deadline);

        if ($running > 0) {
            foreach ($workers as $worker) {
                proc_terminate($worker['process']);
            }

            $this->fail('A PostgreSQL worker exceeded the test timeout.');
        }

        $results = [];
        foreach ($workers as $worker) {
            $stdout = stream_get_contents($worker['pipes'][1]);
            $stderr = stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][1]);
            fclose($worker['pipes'][2]);
            proc_close($worker['process']);

            $messages = array_values(array_filter(array_map(
                static fn (string $line): mixed => json_decode($line, true),
                preg_split('/\R/', trim((string) $stdout)) ?: [],
            ), 'is_array'));

            if (count($messages) < 2 || ($messages[0]['event'] ?? null) !== 'ready') {
                $this->fail('A PostgreSQL worker failed before returning its result: '.trim((string) $stderr));
            }

            $results[] = array_merge($messages[1], [
                'action' => $worker['action'],
                'application_name' => $messages[0]['application_name'] ?? $worker['application_name'],
                'os_pid' => $messages[0]['os_pid'] ?? null,
                'db_pid' => $messages[0]['db_pid'] ?? null,
            ]);

            if (is_file($worker['ready_path'])) {
                unlink($worker['ready_path']);
            }
        }

        return $results;
    }

    /** @param array<int, array<string, mixed>> $results */
    private function assertWorkerConnectionsAreSeparate(array $results): void
    {
        $this->assertSame(count($results), collect($results)->pluck('os_pid')->unique()->count());
        $this->assertSame(count($results), collect($results)->pluck('db_pid')->unique()->count());
    }

    /** @param array<int, array<string, mixed>> $results
     * @return array<string, mixed>
     */
    private function workerResult(array $results, string $action): array
    {
        return collect($results)->firstWhere('action', $action)
            ?? throw new \RuntimeException('The PostgreSQL worker result is missing.');
    }

    /** @param array<int, array<string, mixed>> $workers
     * @return array<int, int>
     */
    private function workerBackendPids(array $workers): array
    {
        $pids = [];

        foreach ($workers as $worker) {
            if (! is_file($worker['ready_path'])) {
                continue;
            }

            $message = json_decode((string) file_get_contents($worker['ready_path']), true);
            if (is_array($message) && isset($message['db_pid'])) {
                $pids[] = (int) $message['db_pid'];
            }
        }

        return $pids;
    }
}
