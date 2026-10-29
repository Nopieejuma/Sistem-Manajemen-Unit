<?php

namespace Tests\Integration\Postgres;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TestingDatabaseSmokeTest extends TestCase
{
    public function test_it_connects_only_to_the_isolated_postgres_test_database_and_finds_the_expected_schema(): void
    {
        $connection = DB::connection();
        $database = $connection->selectOne('SELECT current_database() AS database_name, inet_server_port() AS server_port');

        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('gudang_integration_test', $database->database_name);
        $this->assertSame(5432, (int) $database->server_port);

        foreach (['user', 'gudang', 'jadwal', 'temu_janji'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected PostgreSQL test table [{$table}] to exist.");
        }

        $typeLabels = $connection->select(<<<'SQL'
            SELECT types.typname, labels.enumlabel
            FROM pg_type AS types
            INNER JOIN pg_enum AS labels ON labels.enumtypid = types.oid
            WHERE types.typname IN ('gudang_tipe_enum', 'gudang_status_enum', 'temu_janji_status_enum')
            ORDER BY types.typname, labels.enumsortorder
        SQL);

        $labelsByType = [];
        foreach ($typeLabels as $typeLabel) {
            $labelsByType[$typeLabel->typname][] = $typeLabel->enumlabel;
        }

        $this->assertSame(['Versa', 'Heksa', 'MAXI 24', 'VERTA 56'], $labelsByType['gudang_tipe_enum']);
        $this->assertSame(['tersedia', 'sedang di-keep', 'tidak tersedia', 'digunakan'], $labelsByType['gudang_status_enum']);
        $this->assertSame(['pending', 'approved', 'rejected', 'completed', 'canceled'], $labelsByType['temu_janji_status_enum']);
    }
}
