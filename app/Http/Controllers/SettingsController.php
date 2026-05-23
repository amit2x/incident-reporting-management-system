<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show settings page.
     */
    public function index()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    /**
     * Update user preferences.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences.dark_mode' => 'nullable|boolean',
            'preferences.email_notifications' => 'nullable|boolean',
            'preferences.push_notifications' => 'nullable|boolean',
            'preferences.language' => 'nullable|string|in:en,hi',
        ]);

        $user = Auth::user();
        $preferences = $user->preferences ?? [];

        $preferences['dark_mode'] = $request->boolean('preferences.dark_mode');
        $preferences['email_notifications'] = $request->boolean('preferences.email_notifications');
        $preferences['push_notifications'] = $request->boolean('preferences.push_notifications');
        $preferences['language'] = $validated['preferences']['language'] ?? 'en';

        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Notification settings (Admin only).
     */
    public function notificationSettings()
    {
        $this->authorize('manage-notification-settings');
        return view('admin.settings.notifications');
    }

    /**
     * Update notification settings (Admin only).
     */
    public function updateNotificationSettings(Request $request)
    {
        $this->authorize('manage-notification-settings');

        $validated = $request->validate([
            'fcm_server_key' => 'nullable|string',
            'mail_from_address' => 'nullable|email',
        ]);

        foreach ($validated as $key => $value) {
            if ($value) {
                setEnvValue(strtoupper($key), $value);
            }
        }

        return back()->with('success', 'Notification settings updated.');
    }

    /**
     * System settings (Admin only).
     */
    public function systemSettings()
    {
        $this->authorize('manage-settings');
        return view('admin.settings.system');
    }

    /**
     * Update system settings (Admin only).
     */
    public function updateSystemSettings(Request $request)
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'app_name' => 'required|string|max:50',
            'max_file_size' => 'required|integer|min:1',
            'sla_default_minutes' => 'required|integer|min:1',
            'auto_escalation_breaches' => 'required|integer|min:1',
        ]);

        foreach ($validated as $key => $value) {
            setEnvValue(strtoupper($key), $value);
        }

        return back()->with('success', 'System settings updated.');
    }
}

/**
 * Helper function to update .env values
 */
if (!function_exists('setEnvValue')) {
    function setEnvValue($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            if (str_contains($content, $key . '=')) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $content
                );
            } else {
                $content .= "\n{$key}={$value}";
            }

            file_put_contents($path, $content);
        }
    }
}
