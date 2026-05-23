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

    public function __construct(array $tokens, string $title, string $body, array $data = [])
    {
        $this->tokens = $tokens;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    public function handle(FCMService $fcmService): void
    {
        if (empty($this->tokens)) {
            Log::info('No FCM tokens provided for bulk notification');
            return;
        }

        // FCM supports up to 1000 tokens per request
        $chunks = array_chunk($this->tokens, 1000);

        foreach ($chunks as $chunk) {
            $success = $fcmService->sendToMultipleDevices(
                $chunk,
                $this->title,
                $this->body,
                $this->data
            );

            if (!$success) {
                Log::warning('Bulk push notification failed for chunk', [
                    'chunk_size' => count($chunk),
                    'attempt' => $this->attempts(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk push notification job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}