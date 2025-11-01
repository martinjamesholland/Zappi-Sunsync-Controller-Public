<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'setup.complete' => \App\Http\Middleware\CheckSetupComplete::class,
            'api.key' => \App\Http\Middleware\CheckApiKey::class,
            'cron.auth' => \App\Http\Middleware\AllowCronMode::class,
        ]);
        
        // Exclude setup routes and cron routes from CSRF verification
        // This is necessary because the .env file might not exist yet
        // and sessions might not be properly configured during initial setup
        // Also exclude cron routes as they use API keys, not sessions
        $middleware->validateCsrfTokens(except: [
            'setup/*',
            'ev-charging/status',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Refresh the energy data mart every fifteen minutes
        $schedule->command('app:refresh-energy-data-mart')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/data-mart-refresh.log'));
        
        // Existing data acquisition can stay on its own cadence if needed
        // $schedule->command('app:update-energy-flow-data')->everyFiveMinutes();
    })
    ->create();
