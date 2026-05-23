<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'rate_limit:' . ($request->user()?->id ?: $request->ip());
        
        $limiter = RateLimiter::attempt(
            $key,
            $perMinute = 60,
            function() {
                // This callback is executed when request is allowed
            }
        );

        if (!$limiter) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.'
                ], 429);
            }
            
            abort(429, 'Too many requests. Please try again later.');
        }

        return $next($request);
    }
}