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
        $response->headers->remove('X-Powered-By');

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        $response->headers->set('Origin-Agent-Cluster', '?1');

        // Only set HSTS in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy - FIXED: Added Firebase domains
        $csp = "default-src 'self'; ".
            // Scripts
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' ".
                'https://*.gstatic.com '.
                'https://cdn.jsdelivr.net '.
                'https://*.firebaseio.com; '.
            // Workers (needed for Firebase service worker)
            "worker-src 'self' blob: ".
                'https://*.gstatic.com; '.
            // Styles
            "style-src 'self' 'unsafe-inline' ".
                'https://fonts.googleapis.com '.
                'https://cdn.jsdelivr.net '.
                'https://*.gstatic.com; '.
            // Images
            "img-src 'self' data: https: blob:; ".
            // Fonts
            "font-src 'self' ".
                'https://fonts.gstatic.com '.
                'https://cdn.jsdelivr.net '.
                'https://*.gstatic.com; '.
            // Connections - FIXED: Added all Firebase domains
            "connect-src 'self' ".
                'https://*.firebaseio.com '.
                'https://*.googleapis.com '.
                'https://fcmregistrations.googleapis.com '.
                'https://firebaseinstallations.googleapis.com '.
                'https://fcm.googleapis.com '.
                'https://identitytoolkit.googleapis.com '.
                'https://securetoken.googleapis.com '.
                'https://*.google.com '.
                'https://*.gstatic.com '.
                'wss://*.firebaseio.com; '.
            // Media
            "media-src 'self' blob:; ".
            // Frames (allow none by default)
            "frame-src 'none'; ".
            "base-uri 'self'; ".
            "form-action 'self'; ".
            "object-src 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
