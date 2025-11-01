<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only require API key if cron_mode is present
        if (!$request->has('cron_mode')) {
            // Not a cron request, skip API key check
            return $next($request);
        }
        
        // Get API key from config
        $configApiKey = config('services.api.key');
        $requestApiKey = $request->query('api_key');
        
        // Check if API key is configured and matches
        if (empty($configApiKey)) {
            return response('API_KEY not configured', 401);
        }
        
        if (empty($requestApiKey) || $requestApiKey !== $configApiKey) {
            return response('Unauthorized: Invalid or missing API_KEY', 401);
        }
        
        return $next($request);
    }
}


