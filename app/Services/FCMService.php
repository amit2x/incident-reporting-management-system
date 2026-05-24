<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $messaging;
    protected $isInitialized = false;

    public function __construct()
    {
        try {
            $credentials = config('firebase.projects.app.credentials');

            // IMPORTANT: Resolve the path correctly
            // If path starts with 'storage/', use storage_path()
            // Otherwise use base_path() or treat as absolute
            if (str_starts_with($credentials, 'storage/')) {
                $credentials = storage_path(str_replace('storage/', '', $credentials));
            } elseif (!str_starts_with($credentials, '/')) {
                $credentials = base_path($credentials);
            }

            Log::info('FCM: Attempting to load credentials from: ' . $credentials);

            if (!file_exists($credentials)) {
                Log::error('FCM: Credentials file not found at: ' . $credentials);

                // Try alternative paths
                $alternatives = [
                    storage_path('app/firebase/firebase-adminsdk.json'),
                    base_path('storage/app/firebase/firebase-adminsdk.json'),
                    base_path('firebase-adminsdk.json'),
                    storage_path('firebase/firebase-adminsdk.json'),
                ];

                foreach ($alternatives as $alt) {
                    if (file_exists($alt)) {
                        Log::info('FCM: Found credentials at alternative path: ' . $alt);
                        $credentials = $alt;
                        break;
                    }
                }

                if (!file_exists($credentials)) {
                    $this->messaging = null;
                    return;
                }
            }

            if (!is_readable($credentials)) {
                Log::error('FCM: Credentials file not readable: ' . $credentials);
                $this->messaging = null;
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentials);
            $this->messaging = $factory->createMessaging();
            $this->isInitialized = true;

            Log::info('FCM Service initialized successfully with: ' . basename($credentials));
        } catch (\Exception $e) {
            Log::error('FCM Service initialization failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->messaging = null;
        }
    }

    /**
     * Check if service is ready.
     */
    public function isReady(): bool
    {
        return $this->isInitialized && $this->messaging !== null;
    }

    /**
     * Send push notification to a single device.
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): array
    {
        if (!$this->isReady()) {
            Log::error('FCM: Service not ready to send');
            return ['success' => false, 'error' => 'Service not initialized'];
        }

        if (empty($deviceToken) || strlen($deviceToken) < 50) {
            Log::error('FCM: Invalid token', ['token_length' => strlen($deviceToken)]);
            return ['success' => false, 'error' => 'Invalid token'];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData(array_merge($data, [
                    'click_action' => $data['click_action'] ?? url('/notifications'),
                ]))
                ->withHighestPossiblePriority();

            $result = $this->messaging->send($message);

            Log::info('FCM: Notification sent!', [
                'token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
            ]);

            return ['success' => true, 'message_id' => is_array($result) ? ($result['name'] ?? '') : (string)$result];
        } catch (MessagingException $e) {
            $error = $e->getMessage();

            Log::error('FCM Error: ' . $error);

            // Handle specific errors
            if (str_contains($error, 'UNREGISTERED') || str_contains($error, 'NOT_FOUND')) {
                return ['success' => false, 'error' => 'Token unregistered. Device may have uninstalled app.'];
            }
            if (str_contains($error, 'INVALID_ARGUMENT')) {
                return ['success' => false, 'error' => 'Invalid token format.'];
            }
            if (str_contains($error, 'SENDER_ID_MISMATCH')) {
                return ['success' => false, 'error' => 'Sender ID mismatch. Check Firebase config.'];
            }
            if (str_contains($error, 'PERMISSION_DENIED')) {
                return ['success' => false, 'error' => 'Permission denied. Check service account permissions.'];
            }

            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error('FCM Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send to multiple devices.
     */
    public function sendToMultipleDevices(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        if (!$this->isReady()) {
            return ['success' => false, 'error' => 'Service not initialized'];
        }

        if (empty($deviceTokens)) {
            return ['success' => false, 'error' => 'No tokens provided'];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->withHighestPossiblePriority();

            $result = $this->messaging->sendMulticast($message, $deviceTokens);

            $successCount = $result->successes()->count();
            $failureCount = $result->failures()->count();

            Log::info('FCM Multicast:', [
                'success' => $successCount,
                'failures' => $failureCount,
            ]);

            return [
                'success' => $successCount > 0,
                'sent' => $successCount,
                'failed' => $failureCount,
            ];
        } catch (\Exception $e) {
            Log::error('FCM Multicast Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
