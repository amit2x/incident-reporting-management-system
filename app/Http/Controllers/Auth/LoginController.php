<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CaptchaRule;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';
    protected $maxAttempts = 5;
    protected $decayMinutes = 2;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        // Check if CAPTCHA should be shown
        $showCaptcha = $this->shouldShowCaptcha();

        return view('auth.login', compact('showCaptcha'));
    }

    /**
     * Validate the user login request.
     */
    protected function validateLogin(Request $request)
    {
        $rules = [
            $this->username() => 'required|email',
            'password' => 'required|string',
        ];

        // Add CAPTCHA validation if needed
        if ($this->shouldShowCaptcha()) {
            $rules['captcha'] = ['required', 'string', new CaptchaRule];
        }

        $request->validate($rules, [
            'captcha.required' => 'Please enter the CAPTCHA code.',
        ]);
    }

    /**
     * Handle a failed login attempt.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        // Increment failed attempts
        $key = 'login_attempts:' . $request->ip();
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, now()->addHours(2));

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Handle a successful login.
     */
    protected function authenticated(Request $request, $user)
    {
        // Reset failed attempts on successful login
        $key = 'login_attempts:' . $request->ip();
        Cache::forget($key);

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Log activity
        \App\Models\UserActivityLog::log('login', 'User', $user->id);
    }

    /**
     * Check if CAPTCHA should be shown.
     */
    protected function shouldShowCaptcha(): bool
    {
        $key = 'login_attempts:' . request()->ip();
        $attempts = Cache::get($key, 0);

        return $attempts >= 3;
    }

    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        return strtolower($request->input($this->username())) . '|' . $request->ip();
    }

    /**
     * Refresh CAPTCHA via AJAX.
     */
    public function refreshCaptcha()
    {
        return response()->json([
            'success' => true,
            'captcha_url' => captcha_src('math'),
            'captcha_key' => captcha_key(),
        ]);
    }
}
