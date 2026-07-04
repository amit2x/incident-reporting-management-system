<?php

// 1. Force error reporting ON so crashes aren't silent
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/storage/logs/cron-crash.log');
error_reporting(E_ALL);

// 2. Heartbeat check to confirm the file is hit
file_put_contents(__DIR__.'/storage/logs/cron-test.log', "[" . date('Y-m-d H:i:s') . "] Hostinger triggered scheduler-cron.php\n", FILE_APPEND);

// 3. Set explicit working directory to Laravel root folder
$basePath = '/home/u341780581/domains/ddg.nscbiairport.com/irms';
chdir($basePath);

try {
    require $basePath.'/vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once $basePath.'/bootstrap/app.php';

    // FIX: Bind the Console Kernel to properly bootstrap core dependencies like 'files'
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    
    // This handles the bootstrap process safely instead of calling $app->boot()
    $status = $kernel->handle(
        $input = new \Symfony\Component\Console\Input\ArgvInput(['artisan', 'schedule:run']),
        new \Symfony\Component\Console\Output\ConsoleOutput
    );

    $logFile = $basePath.'/storage/logs/laravel-scheduler.log';
    file_put_contents(
        $logFile, 
        "[".date('Y-m-d H:i:s')."] Laravel Scheduler Engine Executed Successfully with status: " . $status . "\n", 
        FILE_APPEND
    );

    $kernel->terminate($input, $status);

} catch (\Throwable $e) {
    file_put_contents(
        __DIR__.'/storage/logs/cron-crash.log',
        '['.date('Y-m-d H:i:s').'] CRASH: '. $e->getMessage()."\n".$e->getTraceAsString()."\n\n",
        FILE_APPEND
    );
}

