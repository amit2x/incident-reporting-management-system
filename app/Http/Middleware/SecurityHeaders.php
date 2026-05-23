<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Set security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Content Security Policy
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.pusher.com https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net; " .
            "img-src 'self' data: https: blob:; " .
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net https://cdn.jsdelivr.net; " .
            "connect-src 'self' https://*.pusher.com wss://*.pusher.com; " .
            "media-src 'self' blob:; " .
            "frame-src 'none';";



        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
