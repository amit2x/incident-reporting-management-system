<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status !== 'active') {
            Auth::logout();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact administrator.'
                ], 403);
            }
            
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been suspended.']);
        }

        return $next($request);
    }
}