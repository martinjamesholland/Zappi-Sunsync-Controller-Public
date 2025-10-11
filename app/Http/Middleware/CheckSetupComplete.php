<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if we're already on setup pages
        if ($request->is('setup*')) {
            return $next($request);
        }

        // Check if .env file exists
        if (!file_exists(base_path('.env'))) {
            return redirect()->route('setup.index');
        }

        // Check if APP_KEY and API_KEY are set
        if (empty(config('app.key')) || empty(config('services.api.key'))) {
            return redirect()->route('setup.app-key');
        }

        // Check if database is configured
        if (!$this->isDatabaseConfigured()) {
            return redirect()->route('setup.database');
        }

        // Check if Zappi credentials are set
        if (empty(config('myenergi.serial')) || empty(config('myenergi.password'))) {
            return redirect()->route('setup.zappi');
        }

        // Check if SunSync credentials are set
        if (empty(config('services.sunsync.username')) || empty(config('services.sunsync.password'))) {
            return redirect()->route('setup.sunsync');
        }

        // All checks passed, proceed with request
        return $next($request);
    }

    /**
     * Check if database is configured and has tables
     */
    private function isDatabaseConfigured(): bool
    {
        $connection = config('database.default');
        
        // Check if database configuration exists in .env
        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            
            // If using the default path and file doesn't exist, not configured
            if ($dbPath === database_path('database.sqlite') && !file_exists($dbPath)) {
                return false;
            }
            
            // If DB_DATABASE is not set in .env, not configured
            if (empty(env('DB_DATABASE'))) {
                return false;
            }
        } else {
            // For MySQL/MariaDB/PostgreSQL, check if credentials are set
            if (empty(env('DB_HOST')) || empty(env('DB_DATABASE')) || empty(env('DB_USERNAME'))) {
                return false;
            }
        }
        
        try {
            // Try to connect to database
            DB::connection()->getPdo();
            
            // Check if migrations table exists (indicating migrations have been run)
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
            } elseif ($connection === 'mysql' || $connection === 'mariadb') {
                $tables = DB::select("SHOW TABLES LIKE 'migrations'");
            } elseif ($connection === 'pgsql') {
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE tablename = 'migrations'");
            } else {
                // For other database types, assume configured if connection works
                return true;
            }
            
            return count($tables) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}

