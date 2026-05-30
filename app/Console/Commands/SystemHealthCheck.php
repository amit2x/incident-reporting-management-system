<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthCheck extends Command
{
    // Matches the scheduler signature exactly
    protected $signature = 'system:health-check';

    protected $description = 'Monitor system health (Disk, DB, and Logs)';

    public function handle(): int
    {
        $isHealthy = true;
        $issues = [];

        // 1. Check Database Connectivity
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $isHealthy = false;
            $issues[] = 'Database connection failed: '.$e->getMessage();
        }

        // 2. Check Disk Space Usage (Crucial for Shared Hosting)
        // Alerts you if free disk space falls below 10%
        $diskFree = disk_free_space(base_path());
        $diskTotal = disk_total_space(base_path());

        if ($diskTotal > 0) {
            $freePercentage = ($diskFree / $diskTotal) * 100;
            if ($freePercentage < 10.0) {
                $isHealthy = false;
                $issues[] = sprintf('Low disk space warning: Only %.2f%% free.', $freePercentage);
            }
        }

        // 3. Evaluate Results
        $timestamp = date('Y-m-d H:i:s');

        if ($isHealthy) {
            $this->info("[{$timestamp}] System health check passed.");

            return Command::SUCCESS;
        }

        // Log issues to storage/logs/laravel.log so you can review them
        foreach ($issues as $issue) {
            Log::error('[System Health Alert] '.$issue);
            $this->error("[{$timestamp}] ".$issue);
        }

        return Command::FAILURE;
    }
}
