<?php

namespace App\Support;

use RuntimeException;

class PostgresTestingGuard
{
    /**
     * @param  array<string, mixed>  $databaseConfiguration
     * @param  array<string, string|null>  $environment
     */
    public static function assertSafe(array $databaseConfiguration, string $applicationEnvironment, array $environment): void
    {
        $connection = $databaseConfiguration['connections']['pgsql'] ?? [];

        $expectedConfiguration = [
            'default' => ['value' => $databaseConfiguration['default'] ?? null, 'expected' => 'pgsql'],
            'driver' => ['value' => $connection['driver'] ?? null, 'expected' => 'pgsql'],
            'host' => ['value' => $connection['host'] ?? null, 'expected' => '127.0.0.1'],
            'port' => ['value' => (string) ($connection['port'] ?? ''), 'expected' => '55432'],
            'database' => ['value' => $connection['database'] ?? null, 'expected' => 'gudang_integration_test'],
            'username' => ['value' => $connection['username'] ?? null, 'expected' => 'gudang_test'],
        ];

        if ($applicationEnvironment !== 'testing') {
            throw new RuntimeException('The application must run in testing environment.');
        }

        foreach ($expectedConfiguration as $setting => $comparison) {
            if ($comparison['value'] !== $comparison['expected']) {
                throw new RuntimeException("The effective PostgreSQL testing {$setting} is not allowlisted.");
            }
        }

        if (self::hasValue($connection['url'] ?? null)) {
            throw new RuntimeException('A database URL cannot override the PostgreSQL testing connection.');
        }

        $expectedEnvironment = [
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '55432',
            'DB_DATABASE' => 'gudang_integration_test',
            'DB_USERNAME' => 'gudang_test',
        ];

        foreach ($expectedEnvironment as $setting => $expected) {
            if (($environment[$setting] ?? null) !== $expected) {
                throw new RuntimeException("The PostgreSQL testing {$setting} environment value is not allowlisted.");
            }
        }

        if (self::hasValue($environment['DB_URL'] ?? null) || self::hasValue($environment['DATABASE_URL'] ?? null)) {
            throw new RuntimeException('Database URL environment overrides are not allowed for PostgreSQL testing.');
        }
    }

    private static function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
