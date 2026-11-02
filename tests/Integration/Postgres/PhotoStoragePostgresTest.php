<?php

namespace Tests\Integration\Postgres;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhotoStoragePostgresTest extends TestCase
{
    public function test_photo_migration_runs_only_in_a_rolled_back_local_postgres_transaction(): void
    {
        if (! getenv('PHOTO_TEST_DB_PASSWORD')) {
            $this->markTestSkipped('Dedicated local PostgreSQL test credentials were not supplied.');
        }
        $this->assertTrue(app()->environment('testing'));
        config(['database.connections.photo_test' => [
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 55432,
            'database' => 'gudang_integration_test', 'username' => 'gudang_test',
            'password' => getenv('PHOTO_TEST_DB_PASSWORD'), 'charset' => 'utf8',
            'prefix' => '', 'search_path' => 'public', 'sslmode' => 'disable',
        ]]);
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection('photo_test');
        try {
            $connection = DB::connection();
            $this->assertSame('gudang_integration_test', $connection->selectOne('select current_database() as name')->name);
            $this->assertFalse(Schema::hasTable('warehouse_photo_uploads'), 'Use an isolated test database without the photo upload table.');
            $connection->beginTransaction();
            try {
                $migration = require database_path('migrations/2026_11_02_083328_create_warehouse_photo_uploads_table.php');
                $migration->up();
                DB::table('warehouse_photo_uploads')->insert([
                    'id' => '00000000-0000-0000-0000-000000000001', 'admin_id' => 1,
                    'staging_key' => 'pending/test.png', 'public_key' => 'gudang/test.png',
                    'public_url' => 'https://photos.example.test/gudang/test.png',
                    'mime' => 'image/png', 'size' => 100, 'expires_at' => now()->addHour(),
                ]);
                $this->assertSame('pending', DB::table('warehouse_photo_uploads')->value('status'));
                $this->assertTrue(Schema::hasColumn('gudang', 'foto'));
                $migration->down();
                $this->assertFalse(Schema::hasTable('warehouse_photo_uploads'));
            } finally {
                $connection->rollBack();
            }
            $this->assertFalse(Schema::hasTable('warehouse_photo_uploads'));
        } finally {
            DB::setDefaultConnection($previous);
            DB::disconnect('photo_test');
        }
    }
}
