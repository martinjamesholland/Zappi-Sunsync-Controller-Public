<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowCronMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If cron_mode is present, we'll handle auth in CheckApiKey middleware instead
        // This middleware just passes the request through
        if ($request->has('cron_mode')) {
            // Cron request - skip normal auth
            return $next($request);
        }
        
        // Normal request - require authentication
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        return $next($request);
    }
}

