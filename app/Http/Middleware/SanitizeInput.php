<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value) {
            // Remove potentially dangerous characters
            if (is_string($value)) {
                $value = strip_tags($value, '<p><br><strong><em><ul><ol><li><a>');
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });
        
        $request->merge($input);

        return $next($request);
    }
}