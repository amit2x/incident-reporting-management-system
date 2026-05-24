<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFCMNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $title;
    protected string $body;
    protected array $data;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $title, string $body, array $data = [])
    {
        $this->user = $user;
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
        // Skip if user has no FCM token
        if (!$this->user->fcm_token) {
            Log::info('FCM Job: User has no token, skipping', ['user_id' => $this->user->id]);
            return;
        }

        // Check user preferences
        if (isset($this->user->preferences['push_notifications']) &&
            !$this->user->preferences['push_notifications']) {
            Log::info('FCM Job: User disabled push notifications', ['user_id' => $this->user->id]);
            return;
        }

        $result = $fcmService->sendToDevice(
            $this->user->fcm_token,
            $this->title,
            $this->body,
            $this->data
        );

        if (!$result['success']) {
            Log::warning('FCM Job: Failed to send', [
                'user_id' => $this->user->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            // If token is unregistered, clear it
            if (str_contains($result['error'] ?? '', 'unregistered')) {
                $this->user->update(['fcm_token' => null]);
                Log::info('FCM Job: Cleared unregistered token', ['user_id' => $this->user->id]);
            }

            // Retry if not a permanent failure
            if ($this->attempts() < $this->tries) {
                $this->release(30); // Retry after 30 seconds
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FCM Job Failed Permanently:', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
