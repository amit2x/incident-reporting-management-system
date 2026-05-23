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

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $title;
    protected string $body;
    protected array $data;
    
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60];

    public function __construct(User $user, string $title, string $body, array $data = [])
    {
        $this->user = $user;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->onQueue('notifications');
    }

    public function handle(FCMService $fcmService): void
    {
        if (empty($this->user->fcm_token)) {
            Log::info('User has no FCM token, skipping push notification', [
                'user_id' => $this->user->id,
            ]);
            return;
        }

        $success = $fcmService->sendToDevice(
            $this->user->fcm_token,
            $this->title,
            $this->body,
            $this->data
        );

        if (!$success) {
            Log::warning('Push notification failed, will retry', [
                'user_id' => $this->user->id,
                'attempt' => $this->attempts(),
            ]);
            
            // Release back to queue for retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}