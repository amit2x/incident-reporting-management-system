<?php
// routes/console.php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Register custom commands
Artisan::command('system:health-check', function () {
    $this->info('Running system health check...');
    
    // Check database connection
    try {
        \DB::connection()->getPdo();
        $this->info('✓ Database connection: OK');
    } catch (\Exception $e) {
        $this->error('✗ Database connection: FAILED - ' . $e->getMessage());
        \Log::error('Health check: Database connection failed', ['error' => $e->getMessage()]);
    }
    
    // Check storage permissions
    if (is_writable(storage_path())) {
        $this->info('✓ Storage permissions: OK');
    } else {
        $this->error('✗ Storage permissions: FAILED');
    }
    
    // Check queue status
    $pendingJobs = \DB::table('jobs')->count();
    $failedJobs = \DB::table('failed_jobs')->count();
    $this->info("✓ Queue status: {$pendingJobs} pending, {$failedJobs} failed");
    
    // Check disk space
    $freeSpace = disk_free_space(storage_path());
    $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);
    if ($freeSpaceGB < 1) {
        $this->error("✗ Disk space: {$freeSpaceGB} GB (CRITICAL)");
    } else {
        $this->info("✓ Disk space: {$freeSpaceGB} GB free");
    }
    
    $this->info('Health check completed.');
})->purpose('Check system health status')->daily();

// Quick queue status command
Artisan::command('queue:status', function () {
    $this->table(
        ['Queue', 'Pending Jobs', 'Failed Jobs'],
        [
            ['notifications', \DB::table('jobs')->where('queue', 'notifications')->count(), \DB::table('failed_jobs')->where('queue', 'notifications')->count()],
            ['emails', \DB::table('jobs')->where('queue', 'emails')->count(), \DB::table('failed_jobs')->where('queue', 'emails')->count()],
            ['reports', \DB::table('jobs')->where('queue', 'reports')->count(), \DB::table('failed_jobs')->where('queue', 'reports')->count()],
            ['default', \DB::table('jobs')->where('queue', 'default')->count(), \DB::table('failed_jobs')->where('queue', 'default')->count()],
        ]
    );
})->purpose('Display queue status');

// Retry all failed jobs
Artisan::command('queue:retry-all', function () {
    $count = \DB::table('failed_jobs')->count();
    if ($count > 0) {
        $this->call('queue:retry', ['id' => 'all']);
        $this->info("Retried {$count} failed jobs.");
    } else {
        $this->info('No failed jobs to retry.');
    }
})->purpose('Retry all failed queue jobs');