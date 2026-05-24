<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class CaptchaMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to login route
        if (!$request->is('login') || !$request->isMethod('post')) {
            return $next($request);
        }

        $key = 'login_attempts:' . $request->ip();
        $attempts = Cache::get($key, 0);

        // Show CAPTCHA after 3 failed attempts
        if ($attempts >= 3) {
            $request->validate([
                'captcha' => ['required', 'string', new \App\Rules\CaptchaRule],
            ]);
        }

        return $next($request);
    }
}
