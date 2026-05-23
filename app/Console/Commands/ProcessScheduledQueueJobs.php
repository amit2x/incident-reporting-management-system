<?php
// app/Console/Commands/ProcessScheduledQueueJobs.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessScheduledQueueJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:process-scheduled 
                            {--max-jobs=50 : Maximum number of jobs to process}
                            {--queue=default,notifications,emails,reports : Queues to process}
                            {--timeout=60 : Job timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process queue jobs from database for scheduled tasks (Windows compatible)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $maxJobs = (int) $this->option('max-jobs');
        $queues = explode(',', $this->option('queue'));
        $timeout = (int) $this->option('timeout');
        $processed = 0;
        $failed = 0;

        $this->info("Processing up to {$maxJobs} jobs from queues: " . implode(', ', $queues));
        $this->info("Timeout per job: {$timeout} seconds");
        $this->newLine();

        $startTime = microtime(true);

        while ($processed < $maxJobs) {
            // Get next available job
            $job = DB::table('jobs')
                ->whereIn('queue', $queues)
                ->where('attempts', '<', 3)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$job) {
                $this->info('No more pending jobs in specified queues.');
                break;
            }

            try {
                $this->info("Processing Job #{$job->id} [{$job->queue}]");
                
                // Set timeout
                set_time_limit($timeout);
                
                // Decode and execute job
                $payload = json_decode($job->payload, true);
                
                if (isset($payload['data']['command'])) {
                    $command = unserialize($payload['data']['command']);
                    
                    if (method_exists($command, 'handle')) {
                        // Execute the job
                        $command->handle();
                    }
                }

                // Remove processed job
                DB::table('jobs')->where('id', $job->id)->delete();
                
                $processed++;
                $this->info("✓ Job #{$job->id} processed successfully ({$processed}/{$maxJobs})");
                
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ Job #{$job->id} failed: " . $e->getMessage());
                
                // Update attempts
                DB::table('jobs')->where('id', $job->id)->increment('attempts');
                
                // Move to failed jobs if max attempts reached
                if (($job->attempts + 1) >= 3) {
                    DB::table('failed_jobs')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'connection' => 'database',
                        'queue' => $job->queue,
                        'payload' => $job->payload,
                        'exception' => mb_substr($e->getMessage() . "\n" . $e->getTraceAsString(), 0, 65535),
                        'failed_at' => now(),
                    ]);
                    
                    DB::table('jobs')->where('id', $job->id)->delete();
                    $this->warn("  ↳ Job moved to failed_jobs table");
                } else {
                    $this->warn("  ↳ Retry attempt: " . ($job->attempts + 1) . "/3");
                }

                Log::error('Queue job processing failed', [
                    'job_id' => $job->id,
                    'queue' => $job->queue,
                    'attempt' => $job->attempts + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            // Small delay between jobs to prevent overwhelming
            usleep(100000); // 100ms
        }

        $duration = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        $this->info("──────────────────────────────────────────");
        $this->info(" Queue Processing Summary");
        $this->info("──────────────────────────────────────────");
        $this->info(" Total Processed: {$processed}");
        $this->info(" Failed: {$failed}");
        $this->info(" Duration: {$duration} seconds");
        $this->info("──────────────────────────────────────────");
    }
}