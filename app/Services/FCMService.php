<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
    }

    /**
     * Send push notification to single device
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                    'click_action' => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                ]),
                'priority' => 'high',
                'time_to_live' => 86400,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title,
                ]);
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToMultipleDevices(array $tokens, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                    'click_action' => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                ]),
                'priority' => 'high',
                'time_to_live' => 86400,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('FCM multicast notification sent', [
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                    'total_tokens' => count($tokens),
                ]);
                return true;
            }

            Log::error('FCM multicast notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM multicast notification exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send to topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM topic notification exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Subscribe user to topic
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://iid.googleapis.com/iid/v1/' . $token . '/rel/topics/' . $topic);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM topic subscription exception: ' . $e->getMessage());
            return false;
        }
    }
}