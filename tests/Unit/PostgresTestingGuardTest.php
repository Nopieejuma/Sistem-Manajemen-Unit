<?php

namespace Tests\Unit;

use App\Support\PostgresTestingGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PostgresTestingGuardTest extends TestCase
{
    public function test_it_accepts_only_the_allowlisted_postgres_testing_configuration(): void
    {
        PostgresTestingGuard::assertSafe(
            $this->databaseConfiguration(),
            'testing',
            $this->environment(),
        );

        $this->assertTrue(true);
    }

    public function test_it_rejects_each_non_allowlisted_connection_setting_without_connecting(): void
    {
        $invalidConfigurations = [
            ['default' => 'mysql'],
            ['driver' => 'sqlite'],
            ['host' => 'db.example.net'],
            ['port' => '5432'],
            ['database' => 'production'],
            ['username' => 'production_user'],
            ['url' => 'postgres://external.example.net/database'],
        ];

        foreach ($invalidConfigurations as $invalidConfiguration) {
            $configuration = $this->databaseConfiguration();
            $setting = array_key_first($invalidConfiguration);

            if ($setting === 'default') {
                $configuration['default'] = $invalidConfiguration[$setting];
            } else {
                $configuration['connections']['pgsql'][$setting] = $invalidConfiguration[$setting];
            }

            try {
                PostgresTestingGuard::assertSafe($configuration, 'testing', $this->environment());
                $this->fail("The guard accepted an unsafe {$setting} value.");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_it_rejects_non_testing_runtime_environment_without_connecting(): void
    {
        $this->expectException(RuntimeException::class);

        PostgresTestingGuard::assertSafe($this->databaseConfiguration(), 'production', $this->environment());
    }

    public function test_it_rejects_database_url_overrides_without_connecting(): void
    {
        $environment = $this->environment();
        $environment['DATABASE_URL'] = 'postgres://external.example.net/database';

        $this->expectException(RuntimeException::class);

        PostgresTestingGuard::assertSafe($this->databaseConfiguration(), 'testing', $environment);
    }

    public function test_it_rejects_non_allowlisted_runtime_host_and_database_without_connecting(): void
    {
        foreach (['DB_HOST' => '203.0.113.10', 'DB_DATABASE' => 'production'] as $setting => $value) {
            $environment = $this->environment();
            $environment[$setting] = $value;

            try {
                PostgresTestingGuard::assertSafe($this->databaseConfiguration(), 'testing', $environment);
                $this->fail("The guard accepted an unsafe {$setting} environment value.");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseConfiguration(): array
    {
        return [
            'default' => 'pgsql',
            'connections' => [
                'pgsql' => [
                    'driver' => 'pgsql',
                    'host' => '127.0.0.1',
                    'port' => '55432',
                    'database' => 'gudang_integration_test',
                    'username' => 'gudang_test',
                    'url' => null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function environment(): array
    {
        return [
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '55432',
            'DB_DATABASE' => 'gudang_integration_test',
            'DB_USERNAME' => 'gudang_test',
            'DB_URL' => null,
            'DATABASE_URL' => null,
        ];
    }
}
