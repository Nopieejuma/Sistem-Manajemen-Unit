param(
    [ValidateSet('Setup', 'Status', 'Test', 'Down')]
    [string] $Action = 'Setup'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $projectRoot '.env.pgsql.testing'
$composePath = Join-Path $projectRoot 'docker-compose.pgsql-testing.yml'
$schemaPath = Join-Path $projectRoot 'database/testing/postgres-schema.sql'
$containerName = 'gudang-integration-test-postgres'
$projectName = 'gudang-integration-test'

function Invoke-DockerCompose {
    param([string[]] $Arguments)

    & docker compose --project-name $projectName --file $composePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Compose operation failed.'
    }
}

function Read-TestCredentials {
    if (-not (Test-Path -LiteralPath $configPath)) {
        $bytes = New-Object byte[] 32
        $randomGenerator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
        $randomGenerator.GetBytes($bytes)
        $randomGenerator.Dispose()
        $password = ([BitConverter]::ToString($bytes)).Replace('-', '')
        $contents = @(
            'POSTGRES_DB=gudang_integration_test'
            'POSTGRES_USER=gudang_test'
            "POSTGRES_PASSWORD=$password"
        ) -join "`n"
        [System.IO.File]::WriteAllText($configPath, $contents + "`n", [System.Text.UTF8Encoding]::new($false))
    }

    $values = @{}
    foreach ($line in Get-Content -LiteralPath $configPath) {
        if ($line -match '^([A-Z_]+)=(.*)$') {
            $values[$Matches[1]] = $Matches[2]
        }
    }

    if ($values.POSTGRES_DB -cne 'gudang_integration_test' -or
        $values.POSTGRES_USER -cne 'gudang_test' -or
        [string]::IsNullOrWhiteSpace($values.POSTGRES_PASSWORD)) {
        throw 'The ignored PostgreSQL testing environment file has unexpected values.'
    }

    return $values
}

function Set-LaravelTestingEnvironment {
    param([hashtable] $Credentials)

    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'pgsql'
    $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = '55432'
    $env:DB_DATABASE = 'gudang_integration_test'
    $env:DB_USERNAME = 'gudang_test'
    $env:DB_PASSWORD = $Credentials.POSTGRES_PASSWORD
    $env:DB_URL = ''
    $env:DATABASE_URL = ''
}

function Assert-SafeLaravelConfiguration {
    if (Test-Path -LiteralPath (Join-Path $projectRoot 'bootstrap/cache/config.php')) {
        throw 'Laravel config cache exists. Clear it before PostgreSQL testing; no database operation was attempted.'
    }

    Push-Location $projectRoot
    try {
        & php 'scripts/pgsql-testing-guard.php'
        if ($LASTEXITCODE -ne 0) {
            throw 'Laravel PostgreSQL testing guard rejected the effective configuration.'
        }
    } finally {
        Pop-Location
    }
}

if ($Action -eq 'Down') {
    Invoke-DockerCompose @('down', '--volumes', '--remove-orphans')
    'PostgreSQL testing container and its disposable database volume were removed.'
    exit 0
}

$credentials = Read-TestCredentials
Set-LaravelTestingEnvironment $credentials
Assert-SafeLaravelConfiguration

if ($Action -eq 'Status') {
    Invoke-DockerCompose @('ps')
    exit 0
}

if ($Action -eq 'Setup') {
    Invoke-DockerCompose @('up', '-d', '--wait')

    $healthDeadline = (Get-Date).AddSeconds(60)
    do {
        $health = & docker inspect --format '{{.State.Health.Status}}' $containerName 2>$null
        if ($LASTEXITCODE -eq 0 -and $health -eq 'healthy') {
            break
        }

        Start-Sleep -Seconds 2
    } while ((Get-Date) -lt $healthDeadline)

    if ($health -ne 'healthy') {
        throw 'PostgreSQL testing container did not become healthy.'
    }

    & docker cp $schemaPath "${containerName}:/tmp/postgres-schema.sql"
    if ($LASTEXITCODE -ne 0) {
        throw 'Could not copy the synthetic testing schema into the test container.'
    }

    & docker exec $containerName sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /tmp/postgres-schema.sql' | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw 'PostgreSQL testing schema setup failed.'
    }

    $verification = & docker exec $containerName psql -At -F '|' -v ON_ERROR_STOP=1 -U 'gudang_test' -d 'gudang_integration_test' -c "SELECT current_database(), current_setting('server_version');"
    if ($LASTEXITCODE -ne 0 -or $null -eq $verification -or $verification -notmatch '^gudang_integration_test\|17\.11') {
        throw 'PostgreSQL testing database verification failed.'
    }

    $portBinding = & docker port $containerName '5432/tcp'
    if ($LASTEXITCODE -ne 0 -or $portBinding -cne '127.0.0.1:55432') {
        throw 'PostgreSQL test port is not bound exclusively to 127.0.0.1:55432.'
    }

    "Container status: healthy"
    'Database: gudang_integration_test'
    'Host binding: 127.0.0.1:55432'
    "PostgreSQL server: $($verification.Split('|')[1])"
    'Schema setup: successful; synthetic schema only, no seed data'
    exit 0
}

Invoke-DockerCompose @('ps')
& php 'vendor/bin/phpunit' '-c' 'phpunit.pgsql.xml' '--display-all-issues'
if ($LASTEXITCODE -ne 0) {
    throw 'PostgreSQL integration smoke test failed.'
}
