<?php

use App\Http\Controllers\Customer\TemuJanjiController as CustomerBookingController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\TemuJanjiController as AdminBookingController;
use App\Models\Jadwal;
use App\Models\TemuJanji;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 3).'/vendor/autoload.php';
require dirname(__DIR__, 3).'/tests/pgsql-bootstrap.php';

$application = require dirname(__DIR__, 3).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$connection = DB::connection();
$applicationName = (string) ($input['application_name'] ?? '');

if (! preg_match('/^pgsql-test-[a-f0-9-]{36}$/', $applicationName)) {
    fwrite(STDERR, "Invalid PostgreSQL test worker name.\n");
    exit(2);
}

$connection->selectOne('SELECT set_config(?, ?, false)', ['application_name', $applicationName]);
$backendPid = (int) $connection->selectOne('SELECT pg_backend_pid() AS pid')->pid;
$readyMessage = [
    'event' => 'ready',
    'os_pid' => getmypid(),
    'db_pid' => $backendPid,
    'application_name' => $applicationName,
];

if (isset($input['ready_path'])) {
    file_put_contents((string) $input['ready_path'], json_encode($readyMessage, JSON_THROW_ON_ERROR));
}

if (isset($input['lock_timeout_ms'])) {
    $lockTimeoutMilliseconds = filter_var($input['lock_timeout_ms'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 100, 'max_range' => 10000],
    ]);

    if ($lockTimeoutMilliseconds === false) {
        fwrite(STDERR, "Invalid lock timeout for PostgreSQL test worker.\n");
        exit(2);
    }

    $connection->statement("SET lock_timeout TO '{$lockTimeoutMilliseconds}ms'");
}

$emit = static function (array $message): void {
    fwrite(STDOUT, json_encode($message, JSON_THROW_ON_ERROR)."\n");
    fflush(STDOUT);
};

$resourceLocker = static function (string $resource, string $id) use ($connection): void {
    if ($resource === 'warehouse') {
        $connection->table('gudang')->where('id', $id)->lockForUpdate()->firstOrFail();

        return;
    }

    if ($resource === 'schedule') {
        $connection->table('jadwal')->where('id', $id)->lockForUpdate()->firstOrFail();

        return;
    }

    throw new RuntimeException('Unsupported test lock resource.');
};

if (($input['action'] ?? null) === 'deadlock_probe') {
    $connection->beginTransaction();
    $resourceLocker((string) $input['first_resource'], (string) $input['first_id']);
    $emit($readyMessage);

    $barrierPath = (string) ($input['barrier_path'] ?? '');
    $deadline = microtime(true) + 15;

    while (! is_file($barrierPath) && microtime(true) < $deadline) {
        usleep(10000);
    }

    if (! is_file($barrierPath)) {
        $connection->rollBack();
        $emit(['result' => 'barrier_timeout']);
        exit(1);
    }

    try {
        $resourceLocker((string) $input['second_resource'], (string) $input['second_id']);
        $connection->commit();
        $emit(['result' => 'committed']);
    } catch (QueryException $exception) {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $emit(['result' => 'database_error', 'sql_state' => $sqlState]);
    }

    exit(0);
}

$emit($readyMessage);

try {
    $action = (string) ($input['action'] ?? '');

    if ($action === 'customer_booking') {
        $request = Request::create('/', 'POST', $input['booking']);
        $response = app(CustomerBookingController::class)->store($request, (string) $input['group']);

        if ($response instanceof JsonResponse) {
            $emit(['result' => 'http', 'status' => $response->getStatusCode(), 'body' => $response->getData(true)]);
        } else {
            $emit(['result' => 'http', 'status' => $response->getStatusCode()]);
        }
    } elseif ($action === 'admin_status') {
        $booking = TemuJanji::query()->findOrFail((string) $input['booking_id']);
        $controller = app(AdminBookingController::class);
        $response = $controller->{(string) $input['transition']}($booking);
        $emit(['result' => 'http', 'status' => $response->getStatusCode()]);
    } elseif ($action === 'schedule_store') {
        $request = Request::create('/', 'POST', $input['schedule']);
        $response = app(JadwalController::class)->store($request);
        $emit(['result' => 'http', 'status' => $response->getStatusCode()]);
    } elseif ($action === 'schedule_update') {
        $schedule = Jadwal::query()->findOrFail((string) $input['schedule_id']);
        $request = Request::create('/', 'PUT', $input['schedule']);
        $response = app(JadwalController::class)->update($request, $schedule);
        $emit(['result' => 'http', 'status' => $response->getStatusCode()]);
    } else {
        throw new RuntimeException('Unsupported PostgreSQL test worker action.');
    }
} catch (ValidationException $exception) {
    $emit(['result' => 'validation', 'errors' => array_keys($exception->errors())]);
} catch (Throwable $exception) {
    $sqlState = $exception instanceof QueryException
        ? (string) ($exception->errorInfo[0] ?? $exception->getCode())
        : null;

    if ($connection->transactionLevel() > 0) {
        $connection->rollBack($connection->transactionLevel());
    }

    $emit([
        'result' => 'exception',
        'type' => $exception::class,
        'sql_state' => $sqlState,
    ]);
}
