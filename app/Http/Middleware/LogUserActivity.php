<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivityLog;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            UserActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'page_visit',
                'model_type' => null,
                'model_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        }

        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (auth()->check() && $request->method() !== 'GET') {
            UserActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'api_request',
                'model_type' => null,
                'model_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        }
    }
}