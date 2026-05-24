<?php

namespace App\Jobs;

use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected array $data;

    public $tries = 2;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(array $tokens, string $title, string $body, array $data = [])
    {
        $this->tokens = $tokens;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(FCMService $fcmService): void
    {
        if (empty($this->tokens)) {
            Log::info('Bulk Push Job: No tokens provided');
            return;
        }

        Log::info('Bulk Push Job: Sending to ' . count($this->tokens) . ' devices', [
            'title' => $this->title,
            'token_count' => count($this->tokens),
        ]);

        // FCM supports up to 500 tokens per multicast request
        $chunks = array_chunk($this->tokens, 500);

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($chunks as $chunk) {
            $result = $fcmService->sendToMultipleDevices(
                $chunk,
                $this->title,
                $this->body,
                $this->data
            );

            if ($result['success']) {
                $totalSent += ($result['sent'] ?? 0);
                $totalFailed += ($result['failed'] ?? 0);
            } else {
                Log::warning('Bulk Push Job: Chunk failed', [
                    'chunk_size' => count($chunk),
                    'error' => $result['error'] ?? 'Unknown',
                ]);
                $totalFailed += count($chunk);
            }
        }

        Log::info('Bulk Push Job: Completed', [
            'sent' => $totalSent,
            'failed' => $totalFailed,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk Push Job Failed:', [
            'error' => $exception->getMessage(),
            'token_count' => count($this->tokens),
        ]);
    }
}
