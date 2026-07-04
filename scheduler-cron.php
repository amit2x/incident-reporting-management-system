<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Scheduler Bootstrap Script
|--------------------------------------------------------------------------
| This script is intended for shared hosting environments where
| cron jobs execute PHP scripts directly.
|--------------------------------------------------------------------------
*/

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/
$basePath = '/home/u341780581/domains/ddg.nscbiairport.com/irms';
$logDir   = $basePath . '/storage/logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$date = date('Y-m-d');

$heartbeatLog = "{$logDir}/scheduler-heartbeat-{$date}.log";
$successLog   = "{$logDir}/scheduler-success-{$date}.log";
$errorLog     = "{$logDir}/scheduler-error-{$date}.log";

/*
|--------------------------------------------------------------------------
| Logger Function
|--------------------------------------------------------------------------
*/
function writeLog(string $file, string $message): void
{
    file_put_contents(
        $file,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

$startTime = microtime(true);

writeLog($heartbeatLog, 'Scheduler script started.');

try {

    /*
    |--------------------------------------------------------------------------
    | Validate Laravel Installation
    |--------------------------------------------------------------------------
    */
    if (!is_dir($basePath)) {
        throw new Exception("Laravel base path not found: {$basePath}");
    }

    chdir($basePath);

    require_once $basePath . '/vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once $basePath . '/bootstrap/app.php';

    /** @var \Illuminate\Contracts\Console\Kernel $kernel */
    $kernel = $app->make(
        \Illuminate\Contracts\Console\Kernel::class
    );

    /*
    |--------------------------------------------------------------------------
    | Execute Scheduler
    |--------------------------------------------------------------------------
    */
    $input = new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'schedule:run',
    ]);

    $output = new \Symfony\Component\Console\Output\BufferedOutput();

    $status = $kernel->handle(
        $input,
        $output
    );

    $schedulerOutput = trim($output->fetch());

    $executionTime = round(
        microtime(true) - $startTime,
        2
    );

    $memoryUsage = round(
        memory_get_peak_usage(true) / 1024 / 1024,
        2
    );

    writeLog(
        $successLog,
        sprintf(
            "SUCCESS | Status=%d | Time=%ss | Memory=%sMB",
            $status,
            $executionTime,
            $memoryUsage
        )
    );

    if (!empty($schedulerOutput)) {
        writeLog(
            $successLog,
            "Scheduler Output:\n" . $schedulerOutput
        );
    }

    $kernel->terminate($input, $status);

    exit($status);

} catch (\Throwable $e) {

    writeLog(
        $errorLog,
        sprintf(
            "ERROR: %s | File: %s | Line: %d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    writeLog(
        $errorLog,
        "STACK TRACE:\n" . $e->getTraceAsString()
    );

    exit(1);
}