<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueueWorker extends Command
{
    protected $signature = 'queue:process 
                            {--queue=default,notifications,emails,reports : The queue to process}
                            {--timeout=60 : Worker timeout in seconds}
                            {--tries=3 : Max retry attempts}
                            {--sleep=3 : Sleep between jobs}';

    protected $description = 'Process database queue jobs';

    public function handle()
    {
        $queue = $this->option('queue');
        $timeout = $this->option('timeout');
        $tries = $this->option('tries');
        $sleep = $this->option('sleep');

        $this->info("Starting queue worker for: {$queue}");
        $this->info("Timeout: {$timeout}s | Tries: {$tries} | Sleep: {$sleep}s");

        // Run the queue worker using database connection
        Artisan::call('queue:work', [
            '--queue' => $queue,
            '--timeout' => $timeout,
            '--tries' => $tries,
            '--sleep' => $sleep,
            '--stop-when-empty' => true,
        ]);

        $this->info('Queue processing completed.');
    }
}