<?php

namespace Tests\Integration\Postgres;

use App\Models\Gudang;
use App\Models\TemuJanji;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StagingDatabaseBootstrapTest extends TestCase
{
    private bool $connected = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('SIP_SCHEMA_TEST_PASSWORD')) {
            $this->markTestSkipped('Requires the dedicated empty local PostgreSQL container.');
        }
        $this->assertTrue(app()->environment('testing'));
        config(['deployment.container' => false, 'database.connections.schema_test' => [
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 5432,
            'database' => 'sip_staging_schema_test', 'username' => 'sip_schema_test',
            'password' => getenv('SIP_SCHEMA_TEST_PASSWORD'), 'charset' => 'utf8',
            'prefix' => '', 'search_path' => 'public', 'sslmode' => 'disable',
        ]]);
        DB::setDefaultConnection('schema_test');
        $connection = DB::connection();
        $this->assertSame('sip_staging_schema_test', $connection->selectOne('select current_database() as name')->name);
        $this->assertSame([], Schema::getTableListing(), 'Refusing to migrate a database that already contains tables.');
        $connection->beginTransaction();
        $this->connected = true;
    }

    protected function tearDown(): void
    {
        if ($this->connected) {
            while (DB::connection('schema_test')->transactionLevel() > 0) {
                DB::connection('schema_test')->rollBack();
            }
            DB::setDefaultConnection('sqlite');
            DB::disconnect('schema_test');
        }
        parent::tearDown();
    }

    private function migrateEmptyDatabase(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'schema_test', '--force' => true]));
    }

    private function expectedDatabase(): array
    {
        return ['username' => 'preview_admin', '--expect-host' => '127.0.0.1',
            '--expect-database' => 'sip_staging_schema_test', '--expect-user' => 'sip_schema_test'];
    }

    private function createAdmin(): string
    {
        $password = bin2hex(random_bytes(16)).'Aa9!';
        $this->app['env'] = 'staging';
        $this->artisan('staging:create-admin', $this->expectedDatabase())
            ->expectsQuestion('Password Admin staging', $password)
            ->expectsQuestion('Ulangi password Admin staging', $password)->assertSuccessful();
        $this->app['env'] = 'testing';

        return $password;
    }

    private function warehouseData(): array
    {
        return ['unit_code' => 'A-01', 'blok' => 'A', 'tipe_gudang' => 'Versa', 'tinggi' => 8,
            'kavling_l' => 10, 'kavling_p' => 12, 'lt1_l' => 5, 'lt1_p' => 6,
            'lt2_l' => 5, 'lt2_p' => 6, 'status' => 'tersedia', 'harga_sewa' => 50000000];
    }

    public function test_complete_migration_chain_builds_empty_postgres_and_runs_twice_safely(): void
    {
        $this->migrateEmptyDatabase();
        foreach (['user', 'gudang', 'jadwal', 'temu_janji', 'sessions', 'cache', 'cache_locks', 'warehouse_photo_uploads', 'jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertTrue(Schema::hasColumn('temu_janji', 'jenis_barang_disimpan'));
        $this->assertSame('user', (new User)->getTable());
        $this->assertDatabaseCount('user', 0);
        $this->migrateEmptyDatabase();
        $this->assertDatabaseCount('gudang', 0);
        Cache::store('database')->put('bootstrap-test', 'ready', 60);
        $this->assertSame('ready', Cache::store('database')->get('bootstrap-test'));
    }

    public function test_admin_and_customer_flow_works_on_migration_generated_schema(): void
    {
        $this->migrateEmptyDatabase();
        $password = $this->createAdmin();
        $admin = User::query()->sole();
        $this->assertNotSame($password, $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check($password, $admin->password));
        config(['session.driver' => 'database']);
        $this->post('/admin/login', ['username' => 'preview_admin', 'password' => $password])->assertRedirect();
        $this->get('/admin/dashboard')->assertOk();
        $this->post('/admin/gudang', $this->warehouseData())->assertRedirect();
        $warehouse = Gudang::query()->sole();
        $this->post('/admin/jadwal', ['jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'kuota' => 2, 'status_aktif' => 1])->assertRedirect();
        $schedule = DB::table('jadwal')->sole();
        $this->post('/admin/logout')->assertRedirect();
        $this->get('/')->assertOk();
        $response = $this->postJson(route('customer.temu-janji.store', $warehouse->id), [
            'gudang_id' => $warehouse->id, 'jadwal_id' => $schedule->id,
            'nama_perusahaan' => 'Staging Test', 'nama_pic' => 'Test PIC', 'email' => 'test@example.test',
            'no_telepon' => '0800000000', 'tanggal_kunjungan' => now('Asia/Jakarta')->addDay()->toDateString(),
            'jenis_barang_disimpan' => 'Sparepart pengujian',
        ])->assertCreated()->assertJsonPath('status', 'pending');
        $booking = TemuJanji::query()->sole();
        $this->assertSame($warehouse->id, $booking->gudang->id);
        $this->assertSame($booking->id, $warehouse->temuJanji()->sole()->id);
        $this->assertSame('Sparepart pengujian', $booking->jenis_barang_disimpan);
        $this->get($response->json('bukti_booking_url'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post('/admin/login', ['username' => 'preview_admin', 'password' => $password])->assertRedirect();
        $this->get(route('admin.temu-janji.show', $booking))->assertOk()->assertSee('Sparepart pengujian');
        $this->post(route('admin.temu-janji.approve', $booking))->assertRedirect();
        $this->assertSame('approved', $booking->refresh()->status);
        $this->assertSame('sedang di-keep', $warehouse->refresh()->status);
        $this->assertGreaterThan(0, DB::table('sessions')->count());
    }

    public function test_bootstrap_preserves_legacy_native_enums_rows_and_photo_json(): void
    {
        $sql = file_get_contents(database_path('testing/postgres-schema.sql'));
        $sql = preg_replace('/^\s*(?:BEGIN|COMMIT);\s*$/m', '', $sql);
        DB::unprepared($sql);
        $warehouse = Gudang::query()->create(array_merge($this->warehouseData(), ['foto' => ['gudang/legacy.jpg']]));
        $before = DB::table('gudang')->get()->toJson();
        $migration = require database_path('migrations/2026_11_02_094648_create_missing_warehouse_application_tables.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, DB::table('gudang')->get()->toJson());
        $this->assertTrue(Schema::hasColumn('temu_janji', 'jenis_barang_disimpan'));
        $this->assertSame(['gudang/legacy.jpg'], $warehouse->refresh()->foto);
        $type = DB::selectOne("select data_type, udt_name from information_schema.columns where table_schema = 'public' and table_name = 'gudang' and column_name = 'status'");
        $this->assertSame('gudang_status_enum', $type->udt_name);
        try {
            $migration->down();
            $this->fail('Unsafe rollback must be refused.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('forward-only', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('gudang')->get()->toJson());
    }

    #[TestWith(['production'])]
    #[TestWith(['wrong-host'])]
    #[TestWith(['business-data'])]
    #[TestWith(['non-interactive'])]
    #[TestWith(['missing-target'])]
    public function test_provisioning_refuses_wrong_environment_target_or_existing_data(string $reason): void
    {
        $this->migrateEmptyDatabase();
        $this->app['env'] = $reason === 'production' ? 'production' : 'staging';
        $options = $this->expectedDatabase();
        if ($reason === 'wrong-host') {
            $options['--expect-host'] = 'wrong.example.test';
        }
        if ($reason === 'business-data') {
            Gudang::query()->create($this->warehouseData());
        }
        if ($reason === 'non-interactive') {
            $options['--no-interaction'] = true;
        }
        if ($reason === 'missing-target') {
            unset($options['--expect-host']);
        }
        $this->artisan('staging:create-admin', $options)->assertFailed();
        $this->assertDatabaseCount('user', 0);
    }

    public function test_provisioning_never_overwrites_an_existing_admin(): void
    {
        $this->migrateEmptyDatabase();
        $this->createAdmin();
        $before = DB::table('user')->get()->toJson();
        $this->app['env'] = 'staging';
        $this->artisan('staging:create-admin', $this->expectedDatabase())->assertFailed();
        $this->assertSame($before, DB::table('user')->get()->toJson());
    }

    #[TestWith(['short', 'short'])]
    #[TestWith(['Strong-Test-Password-123', 'different'])]
    public function test_invalid_password_does_not_create_account(string $password, string $confirmation): void
    {
        $this->migrateEmptyDatabase();
        $this->app['env'] = 'staging';
        $this->artisan('staging:create-admin', $this->expectedDatabase())
            ->expectsQuestion('Password Admin staging', $password)
            ->expectsQuestion('Ulangi password Admin staging', $confirmation)->assertFailed();
        $this->assertDatabaseCount('user', 0);
    }

    private function rejectsStatement(string $statement, string $sqlState): void
    {
        try {
            DB::transaction(fn () => DB::statement($statement));
            $this->fail('Invalid database write was accepted.');
        } catch (QueryException $exception) {
            $this->assertSame($sqlState, $exception->getCode());
        }
    }

    public function test_postgres_enforces_schedule_status_unique_and_booking_foreign_key_constraints(): void
    {
        $this->migrateEmptyDatabase();
        $warehouse = Gudang::query()->create($this->warehouseData());
        $this->rejectsStatement("UPDATE gudang SET status = 'invalid'", '23514');
        $this->rejectsStatement("UPDATE gudang SET tipe_gudang = 'invalid'", '23514');
        $id = (string) Str::uuid();
        $this->rejectsStatement("INSERT INTO jadwal (id,jam_mulai,jam_selesai,kuota) VALUES ('$id','09:00','10:00',0)", '23514');
        $this->rejectsStatement("INSERT INTO jadwal (id,jam_mulai,jam_selesai) VALUES ('$id','10:00','09:00')", '23514');
        DB::table('jadwal')->insert(['id' => $id, 'jam_mulai' => '09:00', 'jam_selesai' => '10:00']);
        $otherId = (string) Str::uuid();
        $this->rejectsStatement("INSERT INTO jadwal (id,jam_mulai,jam_selesai) VALUES ('$otherId','09:00','10:00')", '23505');
        $booking = ['id' => (string) Str::uuid(), 'kode_booking' => 'BK-TEST', 'gudang_id' => $warehouse->id,
            'nama_perusahaan' => 'Test', 'nama_pic' => 'PIC', 'email' => 'test@example.test', 'no_telepon' => '0800',
            'tanggal_kunjungan' => '2030-01-01', 'jam_mulai' => '09:00', 'jam_selesai' => '10:00'];
        DB::table('temu_janji')->insert($booking);
        $this->assertSame('pending', DB::table('temu_janji')->value('status'));
        $this->rejectsStatement("UPDATE temu_janji SET gudang_id = '$otherId'", '23503');
        $this->rejectsStatement("DELETE FROM gudang WHERE id = '$warehouse->id'", '23503');
    }
}
