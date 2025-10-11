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
        ]);
        
        // Exclude setup routes from CSRF verification
        // This is necessary because the .env file might not exist yet
        // and sessions might not be properly configured during initial setup
        $middleware->validateCsrfTokens(except: [
            'setup/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Update energy flow data every 5 minutes
        $schedule->command('app:update-energy-flow-data')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/energy-flow-data.log'));
    })
    ->create();
