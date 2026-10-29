<?php

use App\Support\PostgresTestingGuard;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

require dirname(__DIR__).'/vendor/autoload.php';

$testingEnvironmentPath = dirname(__DIR__).'/.env.pgsql.testing';

if (! is_file($testingEnvironmentPath)) {
    fwrite(STDERR, "PostgreSQL testing refused: dedicated local test configuration is missing.\n");
    exit(2);
}

$testingEnvironment = [];

foreach (file($testingEnvironmentPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^([A-Z_]+)=(.*)$/', $line, $matches) === 1) {
        $testingEnvironment[$matches[1]] = $matches[2];
    }
}

if (($testingEnvironment['POSTGRES_DB'] ?? null) !== 'gudang_integration_test' ||
    ($testingEnvironment['POSTGRES_USER'] ?? null) !== 'gudang_test' ||
    ! is_string($testingEnvironment['POSTGRES_PASSWORD'] ?? null) ||
    $testingEnvironment['POSTGRES_PASSWORD'] === '' ||
    getenv('DB_PASSWORD') !== $testingEnvironment['POSTGRES_PASSWORD']) {
    fwrite(STDERR, "PostgreSQL testing refused: dedicated credentials are missing or do not match the local test database.\n");
    exit(2);
}

$application = require dirname(__DIR__).'/bootstrap/app.php';

$application->bootstrapWith([
    LoadEnvironmentVariables::class,
]);

if ($application->configurationIsCached()) {
    fwrite(STDERR, "PostgreSQL testing refused: Laravel config cache is present.\n");
    exit(2);
}

$application->bootstrapWith([
    LoadConfiguration::class,
]);

try {
    PostgresTestingGuard::assertSafe(
        $application['config']->get('database'),
        $application->environment(),
        [
            'DB_CONNECTION' => getenv('DB_CONNECTION') ?: null,
            'DB_HOST' => getenv('DB_HOST') ?: null,
            'DB_PORT' => getenv('DB_PORT') ?: null,
            'DB_DATABASE' => getenv('DB_DATABASE') ?: null,
            'DB_USERNAME' => getenv('DB_USERNAME') ?: null,
            'DB_URL' => getenv('DB_URL') ?: null,
            'DATABASE_URL' => getenv('DATABASE_URL') ?: null,
        ],
    );
} catch (RuntimeException $exception) {
    fwrite(STDERR, 'PostgreSQL testing refused: '.$exception->getMessage()."\n");
    exit(2);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    fwrite(STDOUT, "PostgreSQL testing configuration verified.\n");
}
